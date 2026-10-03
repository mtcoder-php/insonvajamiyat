<?php

namespace App\Services\Ai;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Jobs\ProcessAiRequest;
use App\Models\AiRequest;
use App\Models\PromptTemplate;
use App\Models\Translation;
use App\Models\User;
use App\Support\DocxWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * AI Studio: so'rov yaratish (navbatga qo'yish), bajarish (ProcessAiRequest job ichida)
 * va natija bilan ishlash (Proofreader takliflarini qabul/rad etish).
 *
 * AI natijasi hech qachon avtomatik qo'llanmaydi — foydalanuvchi har taklifni o'zi tasdiqlaydi (TZ 6).
 */
class AiStudioService
{
    public const MIN_CHARS = 20;

    public const ISSUE_TYPES = ['spelling', 'grammar', 'punctuation', 'style', 'terminology'];

    public function __construct(
        private readonly AiSettings $settings,
        private readonly TokenBudget $budget,
        private readonly PromptLibrary $prompts,
        private readonly AnthropicClient $client,
    ) {}

    /**
     * @param  array<int, string>  $checks  Proofreader: spelling, style, terminology
     *
     * @throws ValidationException
     */
    public function submit(
        User $user,
        AiRequestType $type,
        string $text,
        string $source,
        ?string $target = null,
        array $checks = [],
        ?int $articleId = null,
    ): AiRequest {
        if (! $this->settings->ready()) {
            throw ValidationException::withMessages([
                'text' => self::t('AI xizmati hozircha sozlanmagan. Administratorga murojaat qiling.'),
            ]);
        }

        $text = self::normalize($text);
        $length = mb_strlen($text);

        if ($length < self::MIN_CHARS) {
            throw ValidationException::withMessages(['text' => self::t('Matn juda qisqa (kamida :min belgi).', ['min' => (string) self::MIN_CHARS])]);
        }

        if ($length > $this->settings->maxInputChars()) {
            throw ValidationException::withMessages(['text' => self::t('Matn juda uzun: :length belgi (ko\'pi bilan :max).', [
                'length' => number_format($length, 0, '.', ' '),
                'max' => number_format($this->settings->maxInputChars(), 0, '.', ' '),
            ])]);
        }

        if ($type === AiRequestType::Translation && ($target === null || $target === $source)) {
            throw ValidationException::withMessages(['target_language' => self::t('Tarjima tili manba tildan farq qilishi kerak.')]);
        }

        $chunks = $type === AiRequestType::Analysis ? 1 : count(TextChunker::split($text, self::chunkSize()));
        $this->budget->ensureCanSpend($user, $type === AiRequestType::Analysis ? mb_substr($text, 0, self::analysisChars()) : $text, $type, $chunks);

        $template = $this->prompts->forType($type);
        $checks = array_values(array_intersect(array_keys(PromptLibrary::CHECKS), $checks));

        $request = new AiRequest([
            'user_id' => $user->id,
            'article_id' => $articleId,
            'type' => $type,
            'model' => $template->model ?? $this->settings->model(),
            'source_language' => $source,
            'target_language' => $type === AiRequestType::Translation ? $target : null,
            'input_text' => $text,
        ]);
        $request->forceFill([
            'status' => AiRequestStatus::Queued,
            'provider' => 'anthropic',
            'prompt_template_id' => $template->exists ? $template->id : null,
            'chunks_total' => $chunks,
            'result' => $type === AiRequestType::SpellCheck ? ['checks' => $checks ?: array_keys(PromptLibrary::CHECKS)] : null,
        ])->save();

        ProcessAiRequest::dispatch($request->id)->onQueue(self::str(config('ai.queue', 'default')) ?: 'default');

        return $request;
    }

    /**
     * Navbatdagi so'rovni bajarish (job ichidan chaqiriladi).
     */
    public function process(AiRequest $request): void
    {
        if ($request->status->isFinished()) {
            return;
        }

        $request->forceFill([
            'status' => AiRequestStatus::Processing,
            'started_at' => now(),
            'chunks_completed' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ])->save();

        try {
            $template = $this->prompts->forType($request->type);

            if ($request->type === AiRequestType::SpellCheck) {
                $this->runProofreader($request, $template);
            } elseif ($request->type === AiRequestType::Translation) {
                $this->runTranslator($request, $template);
            } else {
                $this->runAnalytics($request, $template);
            }

            $request->forceFill([
                'status' => AiRequestStatus::Completed,
                'completed_at' => now(),
                'cost_usd' => $this->cost($request),
            ])->save();
        } catch (AiException $e) {
            $this->fail($request, $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->fail($request, self::t("So'rovni bajarishda kutilmagan xato yuz berdi. Qayta urinib ko'ring."));
        }
    }

    public function fail(AiRequest $request, string $message): void
    {
        $request->forceFill([
            'status' => AiRequestStatus::Failed,
            'error_message' => mb_substr($message, 0, 1000),
            'completed_at' => now(),
            'cost_usd' => $this->cost($request),
        ])->save();
    }

    /**
     * Proofreader: foydalanuvchi qarorlari (qabul / rad) va yakuniy matn.
     *
     * @param  array<string, string>  $decisions  issue id => accepted|rejected|pending
     */
    public function saveProofread(AiRequest $request, array $decisions): string
    {
        $result = $request->result ?? [];
        $issues = self::issues($request);

        foreach ($issues as &$issue) {
            $decision = $decisions[$issue['id']] ?? null;

            if (in_array($decision, ['accepted', 'rejected', 'pending'], true)) {
                $issue['status'] = $decision;
            }
        }
        unset($issue);

        $final = self::apply($request->input_text, $issues);

        $request->forceFill([
            'output_text' => $final,
            'result' => [...$result, 'issues' => $issues, 'saved_at' => now()->toIso8601String()],
        ])->save();

        return $final;
    }

    /**
     * Asl matnni bo'laklarga ajratish: oddiy matn va takliflar (frontend shu bo'yicha chizadi).
     *
     * @return array<int, array{text: string, issue: string|null}>
     */
    public static function segments(AiRequest $request): array
    {
        $text = $request->input_text;
        $segments = [];
        $cursor = 0;

        foreach (self::issues($request) as $issue) {
            if ($issue['offset'] > $cursor) {
                $segments[] = ['text' => mb_substr($text, $cursor, $issue['offset'] - $cursor), 'issue' => null];
            }

            $segments[] = ['text' => mb_substr($text, $issue['offset'], $issue['length']), 'issue' => $issue['id']];
            $cursor = $issue['offset'] + $issue['length'];
        }

        if ($cursor < mb_strlen($text)) {
            $segments[] = ['text' => mb_substr($text, $cursor), 'issue' => null];
        }

        return $segments;
    }

    /**
     * @return array<int, array{id: string, original: string, suggestion: string, type: string, reason: string, offset: int, length: int, status: string}>
     */
    public static function issues(AiRequest $request): array
    {
        $issues = $request->result['issues'] ?? [];

        if (! is_array($issues)) {
            return [];
        }

        $clean = [];

        foreach ($issues as $issue) {
            if (! is_array($issue) || ! isset($issue['id'], $issue['offset'], $issue['length'])) {
                continue;
            }

            $clean[] = [
                'id' => self::str($issue['id']),
                'original' => self::str($issue['original'] ?? ''),
                'suggestion' => self::str($issue['suggestion'] ?? ''),
                'type' => self::str($issue['type'] ?? 'style'),
                'reason' => self::str($issue['reason'] ?? ''),
                'offset' => is_numeric($issue['offset']) ? (int) $issue['offset'] : 0,
                'length' => is_numeric($issue['length']) ? (int) $issue['length'] : 0,
                'status' => self::str($issue['status'] ?? 'pending'),
            ];
        }

        usort($clean, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        return $clean;
    }

    /**
     * Qabul qilingan takliflarni asl matnga qo'llash.
     *
     * @param  array<int, array{id: string, original: string, suggestion: string, type: string, reason: string, offset: int, length: int, status: string}>  $issues
     */
    public static function apply(string $text, array $issues): string
    {
        $accepted = array_filter($issues, fn (array $issue): bool => $issue['status'] === 'accepted');
        usort($accepted, fn (array $a, array $b): int => $b['offset'] <=> $a['offset']);

        foreach ($accepted as $issue) {
            $text = mb_substr($text, 0, $issue['offset']).$issue['suggestion'].mb_substr($text, $issue['offset'] + $issue['length']);
        }

        return $text;
    }

    /** Tahrirlangan matn (saqlangan bo'lsa — o'sha, aks holda qabul qilingan takliflar bilan) Word sifatida */
    public function proofreadDocx(AiRequest $request): BinaryFileResponse
    {
        $text = $request->output_text ?? self::apply($request->input_text, self::issues($request));
        $title = self::title($request->input_text);
        $path = DocxWriter::write($title, $text, self::t('Tahrirlangan matn · AI Proofreader'));
        $name = Str::slug(Str::ascii(Str::limit($title, 60, ''))) ?: 'matn';

        return response()
            ->download($path, "{$name}-tahrirlangan.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend();
    }

    public static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = (string) preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    private function runProofreader(AiRequest $request, PromptTemplate $template): void
    {
        $checks = $request->result['checks'] ?? array_keys(PromptLibrary::CHECKS);
        $checks = is_array($checks) ? $checks : [];
        $labels = array_values(array_intersect_key(PromptLibrary::CHECKS, array_flip(array_filter($checks, 'is_string'))));

        $system = PromptLibrary::render($template->system_prompt, [
            'source_language' => PromptLibrary::languageName($request->source_language),
            'target_language' => '',
            'checks' => $labels !== [] ? implode('; ', $labels) : implode('; ', PromptLibrary::CHECKS),
        ]);

        $issues = [];
        $scores = [];
        $summaries = [];

        foreach (TextChunker::split($request->input_text, self::chunkSize()) as $index => $chunk) {
            $completion = $this->call($request, $template, $system, $chunk['text']);
            $data = JsonExtractor::decode($completion->text);

            if ($data === null) {
                throw new AiException(self::t("AI javobini o'qib bo'lmadi. Qayta urinib ko'ring."));
            }

            $issues = [...$issues, ...$this->locate($chunk['text'], $chunk['offset'], $data['issues'] ?? [], $index)];

            if (is_numeric($data['score'] ?? null)) {
                $scores[] = ['score' => max(0, min(100, (int) $data['score'])), 'weight' => mb_strlen($chunk['text'])];
            }

            if (is_string($data['summary'] ?? null) && trim($data['summary']) !== '') {
                $summaries[] = trim($data['summary']);
            }

            $this->progress($request, $completion);
        }

        $weight = array_sum(array_column($scores, 'weight'));
        $score = $weight > 0
            ? (int) round(array_sum(array_map(fn (array $s): float => $s['score'] * $s['weight'], $scores)) / $weight)
            : null;

        $request->forceFill([
            'output_text' => null,
            'result' => [
                ...($request->result ?? []),
                'issues' => $issues,
                'score' => $score,
                'summary' => implode(' ', array_slice($summaries, 0, 3)),
            ],
        ])->save();
    }

    private function runTranslator(AiRequest $request, PromptTemplate $template): void
    {
        $system = PromptLibrary::render($template->system_prompt, [
            'source_language' => PromptLibrary::languageName($request->source_language),
            'target_language' => PromptLibrary::languageName($request->target_language),
            'checks' => '',
        ]);

        $parts = [];
        $truncated = false;

        foreach (TextChunker::split($request->input_text, self::chunkSize()) as $chunk) {
            $completion = $this->call($request, $template, $system, $chunk['text']);
            $parts[] = trim($completion->text);
            $truncated = $truncated || $completion->stopReason === 'max_tokens';
            $this->progress($request, $completion);
        }

        $output = trim(implode("\n\n", $parts));

        if ($output === '') {
            throw new AiException(self::t("AI bo'sh javob qaytardi. Qayta urinib ko'ring."));
        }

        DB::transaction(function () use ($request, $output, $truncated): void {
            $translation = Translation::query()->create([
                'user_id' => $request->user_id,
                'article_id' => $request->article_id,
                'title' => self::title($request->input_text),
                'source_language' => $request->source_language,
                'target_language' => (string) $request->target_language,
                'source_text' => $request->input_text,
            ]);

            $translation->versions()->create([
                'version' => 1,
                'content' => $output,
                'is_ai_generated' => true,
                'ai_request_id' => $request->id,
                'created_by' => $request->user_id,
            ]);

            $request->forceFill([
                'output_text' => $output,
                'result' => ['translation' => $translation->uuid, 'truncated' => $truncated],
            ])->save();
        });
    }

    private function runAnalytics(AiRequest $request, PromptTemplate $template): void
    {
        $system = PromptLibrary::render($template->system_prompt, [
            'source_language' => PromptLibrary::languageName($request->source_language),
            'target_language' => '',
            'checks' => '',
        ]);

        $text = mb_substr($request->input_text, 0, self::analysisChars());
        $completion = $this->call($request, $template, $system, $text);
        $data = JsonExtractor::decode($completion->text);

        if ($data === null) {
            throw new AiException(self::t("AI javobini o'qib bo'lmadi. Qayta urinib ko'ring."));
        }

        $metrics = [];

        foreach (['academic_style', 'clarity', 'structure', 'terminology', 'coherence'] as $key) {
            $value = is_array($data['metrics'] ?? null) ? ($data['metrics'][$key] ?? null) : null;
            $metrics[$key] = is_numeric($value) ? max(0, min(100, (int) $value)) : null;
        }

        $this->progress($request, $completion);

        $request->forceFill([
            'result' => [
                'score' => is_numeric($data['score'] ?? null) ? max(0, min(100, (int) $data['score'])) : null,
                'metrics' => $metrics,
                'strengths' => self::strings($data['strengths'] ?? []),
                'weaknesses' => self::strings($data['weaknesses'] ?? []),
                'recommendations' => self::strings($data['recommendations'] ?? []),
                'summary' => self::str($data['summary'] ?? ''),
                'analysed_chars' => mb_strlen($text),
            ],
        ])->save();
    }

    private function call(AiRequest $request, PromptTemplate $template, string $system, string $text): AiCompletion
    {
        $prompt = PromptLibrary::render($template->user_prompt_template ?: '{text}', ['text' => $text]);

        if (! str_contains($template->user_prompt_template ?? '{text}', '{text}')) {
            $prompt .= "\n\n".$text;
        }

        return $this->client->complete(
            $system,
            $prompt,
            $template->model ?? $request->model,
            max(256, $template->max_tokens),
            is_numeric($template->temperature) ? (float) $template->temperature : null,
        );
    }

    private function progress(AiRequest $request, AiCompletion $completion): void
    {
        $request->forceFill([
            'model' => $completion->model ?: $request->model,
            'input_tokens' => $request->input_tokens + $completion->inputTokens,
            'output_tokens' => $request->output_tokens + $completion->outputTokens,
            'chunks_completed' => $request->chunks_completed + 1,
        ])->save();
    }

    /**
     * Model qaytargan takliflarni matndagi aniq o'rniga joylashtirish.
     * Topilmagan, o'zgarishsiz yoki ustma-ust tushgan takliflar tashlab yuboriladi.
     *
     * @return array<int, array{id: string, original: string, suggestion: string, type: string, reason: string, offset: int, length: int, status: string}>
     */
    private function locate(string $chunk, int $base, mixed $raw, int $chunkIndex): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $placed = [];
        $located = [];
        $cursor = 0;

        foreach (array_values($raw) as $i => $item) {
            if (! is_array($item)) {
                continue;
            }

            $original = self::str($item['original'] ?? '');
            $suggestion = self::str($item['suggestion'] ?? '');

            if (trim($original) === '' || $original === $suggestion) {
                continue;
            }

            $position = mb_strpos($chunk, $original, $cursor);
            $position = $position === false ? mb_strpos($chunk, $original) : $position;

            if ($position === false) {
                continue;
            }

            $length = mb_strlen($original);

            foreach ($placed as $other) {
                if ($position < $other['end'] && $position + $length > $other['start']) {
                    continue 2;
                }
            }

            $type = self::str($item['type'] ?? '');
            $placed[] = ['start' => $position, 'end' => $position + $length];
            $located[] = [
                'id' => $chunkIndex.'-'.$i,
                'original' => $original,
                'suggestion' => $suggestion,
                'type' => in_array($type, self::ISSUE_TYPES, true) ? $type : 'style',
                'reason' => mb_substr(self::str($item['reason'] ?? ''), 0, 200),
                'offset' => $base + $position,
                'length' => $length,
                'status' => 'pending',
            ];
            $cursor = $position + $length;
        }

        return $located;
    }

    private function cost(AiRequest $request): float
    {
        $input = (float) config('ai.pricing.input_per_mtok', 0);
        $output = (float) config('ai.pricing.output_per_mtok', 0);

        return round(($request->input_tokens * $input + $request->output_tokens * $output) / 1_000_000, 6);
    }

    public static function title(string $text): string
    {
        $line = trim((string) strtok($text, "\n"));

        return mb_strlen($line) > 80 ? mb_substr($line, 0, 77).'…' : ($line !== '' ? $line : 'Matn');
    }

    private static function chunkSize(): int
    {
        return max(1_000, (int) config('ai.chunk_chars', 5_000));
    }

    private static function analysisChars(): int
    {
        return max(1_000, (int) config('ai.analysis_chars', 12_000));
    }

    /**
     * @return array<int, string>
     */
    private static function strings(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_slice(array_filter(
            array_map(fn (mixed $item): string => trim(self::str($item)), $items),
            fn (string $item): bool => $item !== '',
        ), 0, 8));
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }
}

<?php

namespace App\Services\Ai;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Models\AiRequest;
use App\Models\Translation;
use App\Models\TranslationVersion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * AI Studio sahifasi uchun ma'lumotlar: so'rovlar ro'yxati, tanlangan so'rov natijasi, statistika.
 * $routes — route nomlari prefiksi (admin.ai yoki keyinchalik cabinet.ai), bitta frontend ikkala joyda ishlaydi.
 */
class AiStudioPresenter
{
    public function __construct(private readonly string $routes = 'admin.ai') {}

    public static function for(string $routes): self
    {
        return new self($routes);
    }

    /**
     * @return array<string, mixed>
     */
    public function item(AiRequest $request, bool $withUser = false): array
    {
        $total = max(1, $request->chunks_total);

        return [
            'uuid' => $request->uuid,
            'type' => $request->type->value,
            'typeLabel' => $request->type->label(),
            'studio' => $request->type->studioName(),
            'status' => $request->status->value,
            'statusLabel' => $request->status->label(),
            'languages' => $request->target_language !== null
                ? mb_strtoupper($request->source_language).' → '.mb_strtoupper($request->target_language)
                : mb_strtoupper($request->source_language),
            'title' => AiStudioService::title($request->input_text),
            'chars' => mb_strlen($request->input_text),
            'tokens' => $request->totalTokens(),
            'progress' => $request->status === AiRequestStatus::Completed
                ? 100
                : (int) floor($request->chunks_completed / $total * 100),
            'score' => is_numeric($request->result['score'] ?? null) ? (int) $request->result['score'] : null,
            'createdAt' => $request->created_at?->toIso8601String(),
            'url' => route($this->routes.'.index', ['tab' => self::tab($request->type), 'request' => $request->uuid]),
            'user' => $withUser ? $request->user->name : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(AiRequest $request, User $viewer): array
    {
        $data = [
            ...$this->item($request),
            'input' => $request->input_text,
            'error' => $request->error_message,
            'chunksTotal' => $request->chunks_total,
            'chunksCompleted' => $request->chunks_completed,
            'finished' => $request->status->isFinished(),
            'own' => $request->user_id === $viewer->id,
        ];

        if ($request->status !== AiRequestStatus::Completed) {
            return $data;
        }

        return [...$data, ...match ($request->type) {
            AiRequestType::SpellCheck => $this->proofread($request),
            AiRequestType::Translation => $this->translation($request),
            AiRequestType::Analysis => ['analysis' => $request->result],
        }];
    }

    /**
     * @return array<string, mixed>
     */
    private function proofread(AiRequest $request): array
    {
        $issues = AiStudioService::issues($request);
        $counts = array_count_values(array_column($issues, 'type'));

        return [
            'proofread' => [
                'segments' => AiStudioService::segments($request),
                'issues' => $issues,
                'counts' => [
                    'errors' => ($counts['spelling'] ?? 0) + ($counts['grammar'] ?? 0) + ($counts['punctuation'] ?? 0),
                    'suggestions' => ($counts['style'] ?? 0) + ($counts['terminology'] ?? 0),
                    'total' => count($issues),
                ],
                'score' => is_numeric($request->result['score'] ?? null) ? (int) $request->result['score'] : null,
                'summary' => is_string($request->result['summary'] ?? null) ? $request->result['summary'] : '',
                'savedAt' => is_string($request->result['saved_at'] ?? null) ? $request->result['saved_at'] : null,
                'finalText' => $request->output_text,
                'saveUrl' => route($this->routes.'.requests.proofread', $request->uuid),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function translation(AiRequest $request): array
    {
        $uuid = $request->result['translation'] ?? null;
        $translation = is_string($uuid)
            ? Translation::query()->where('uuid', $uuid)->with(['versions.author'])->first()
            : null;

        if ($translation === null) {
            return ['translation' => null];
        }

        return [
            'translation' => [
                'uuid' => $translation->uuid,
                'title' => $translation->title,
                'source' => PromptLibrary::languageLabel($translation->source_language),
                'target' => PromptLibrary::languageLabel($translation->target_language),
                'truncated' => (bool) ($request->result['truncated'] ?? false),
                'versions' => $translation->versions->map(fn (TranslationVersion $v): array => [
                    'version' => $v->version,
                    'content' => $v->content,
                    'isAi' => $v->is_ai_generated,
                    'author' => $v->is_ai_generated ? 'AI' : ($v->author->name ?? '—'),
                    'createdAt' => $v->created_at?->toIso8601String(),
                    'downloadUrl' => route($this->routes.'.translations.download', [$translation->uuid, 'version' => $v->version]),
                ])->values()->all(),
                'saveUrl' => route($this->routes.'.translations.versions', $translation->uuid),
            ],
        ];
    }

    /**
     * @param  Builder<AiRequest>  $scope
     * @return LengthAwarePaginator<int, AiRequest>
     */
    public static function history(Builder $scope, ?string $type, int $perPage = 10): LengthAwarePaginator
    {
        return $scope
            ->when($type !== null && AiRequestType::tryFrom($type) !== null, fn (Builder $q) => $q->where('type', $type))
            ->with('user:id,name')
            ->latest('id')
            ->paginate($perPage, ['*'], 'hpage')
            ->withQueryString();
    }

    /**
     * Statistika (oxirgi N kun): jami, muvaffaqiyatli, xato, tokenlar, o'rtacha ishlash vaqti.
     *
     * @param  Builder<AiRequest>  $scope
     * @return array<string, int|float|string>
     */
    public static function stats(Builder $scope, int $days = 30): array
    {
        $from = now()->subDays($days)->startOfDay();
        $base = (clone $scope)->where('created_at', '>=', $from);

        $total = (clone $base)->count();
        $completed = (clone $base)->where('status', AiRequestStatus::Completed->value)->count();
        $failed = (clone $base)->where('status', AiRequestStatus::Failed->value)->count();
        $tokens = (int) (clone $base)->sum('input_tokens') + (int) (clone $base)->sum('output_tokens');

        $durations = (clone $base)
            ->where('status', AiRequestStatus::Completed->value)
            ->whereNotNull('started_at')
            ->latest('id')
            ->limit(500)
            ->get(['started_at', 'completed_at'])
            ->map(fn (AiRequest $r): int => $r->started_at !== null && $r->completed_at !== null
                ? max(0, (int) $r->started_at->diffInSeconds($r->completed_at))
                : 0)
            ->all();

        return [
            'from' => $from->toDateString(),
            'to' => now()->toDateString(),
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'completedPct' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'failedPct' => $total > 0 ? (int) round($failed / $total * 100) : 0,
            'tokens' => $tokens,
            'avgSeconds' => $durations !== [] ? (int) round(array_sum($durations) / count($durations)) : 0,
        ];
    }

    public static function tab(AiRequestType $type): string
    {
        return match ($type) {
            AiRequestType::SpellCheck => 'proofreader',
            AiRequestType::Translation => 'translator',
            AiRequestType::Analysis => 'analytics',
        };
    }
}

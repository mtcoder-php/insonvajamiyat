<?php

namespace App\Services\Production;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleFileService;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use App\Services\Messages\ArticleMessageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nashrga tayyorlash (admin publisher page.png):
 *
 *   Qabul qilindi → maketga olish (InProduction, texnik xodim biriktiriladi) →
 *   yakuniy PDF (article_files.type = final_pdf) → muallif korrekturani tasdiqlaydi →
 *   DOI, UDK, plagiat, jurnal soni va sahifalar → nashr oldidan tekshiruv →
 *   bosh muharrir tasdig'i → nashr (7-bosqich).
 *
 * articles.production_checklist (JSON):
 *   format_ok          — texnik xodim: maqola jurnal formatiga mos
 *   proof_file         — muallif javob bergan yakuniy PDF (uuid)
 *   author_approved_at — muallif korrekturani tasdiqlagan vaqt
 *   author_changes     — muallifning tuzatish so'rovi (oxirgisi), author_changes_at
 *
 * Yangi PDF yuklansa yoki meta ma'lumot o'zgarsa bosh muharrir tasdig'i bekor bo'ladi;
 * muallif roziligi faqat o'zi ko'rgan PDF uchun amal qiladi.
 */
class ProductionService
{
    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly ArticleFileService $files,
        private readonly ArticleMessageService $messages,
        private readonly AuditLogger $audit,
    ) {}

    public static function plagiarismMax(): float
    {
        return config()->float('journal.plagiarism_max', 20.0);
    }

    /** Maketga olish: Qabul qilindi → Nashrga tayyorlanmoqda */
    public function start(Article $article, User $user): void
    {
        $this->ensureStatus($article, [ArticleStatus::Accepted]);

        DB::transaction(function () use ($article, $user): void {
            if ($article->layout_editor_id === null) {
                $article->layout_editor_id = $user->id;
            }

            $this->workflow->transition(
                $article,
                ArticleStatus::InProduction,
                $user,
                __('Maqola nashrga tayyorlanmoqda: maketlash va korrektura bosqichi.'),
            );
        });
    }

    /** Maketdan qaytarish: Nashrga tayyorlanmoqda → Qabul qilindi (muallifga ko'rinmaydi) */
    public function cancel(Article $article, User $user, ?string $reason): void
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);

        DB::transaction(function () use ($article, $user, $reason): void {
            $this->resetApproval($article);
            $this->workflow->transition($article, ArticleStatus::Accepted, $user, $reason, visibleToAuthor: false);
        });
    }

    public function uploadFinalPdf(Article $article, UploadedFile $pdf, User $user): ArticleFile
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);

        $file = DB::transaction(function () use ($article, $pdf, $user): ArticleFile {
            $file = $this->files->store($article, $pdf, ArticleFileType::FinalPdf, $user);

            $this->resetApproval($article);
            $this->setChecklist($article, ['author_approved_at' => null, 'proof_file' => null]);

            $this->audit->log(AuditEvent::FinalPdfUploaded, $article, [
                'file' => $file->original_name,
                'size' => $file->size,
            ], actor: $user);

            return $file;
        });

        $article->submitter->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PROOF,
            __('Korrektura tayyor: yakuniy PDF ni tekshiring'),
            __("Maqolangizning maketlangan varianti tayyor. Kabinetda PDF ni ko'rib chiqing va tasdiqlang yoki tuzatishlarni yozing."),
        ));

        return $file;
    }

    /**
     * @param  array{doi: string|null, udc: string|null, plagiarism_percent: float|null, issue_id: int|null, page_from: int|null, page_to: int|null}  $data
     */
    public function updateMetadata(Article $article, array $data): void
    {
        $this->ensureStatus($article, [ArticleStatus::Accepted, ArticleStatus::InProduction]);

        DB::transaction(function () use ($article, $data): void {
            $article->forceFill([
                'doi' => $data['doi'],
                'udc' => $data['udc'],
                'plagiarism_percent' => $data['plagiarism_percent'],
                'pages_count' => $data['page_from'] !== null && $data['page_to'] !== null
                    ? $data['page_to'] - $data['page_from'] + 1
                    : $article->pages_count,
            ]);

            if ($data['issue_id'] === null) {
                $article->placement()->delete();
            } else {
                $issue = JournalIssue::query()->findOrFail($data['issue_id']);
                $placement = $article->placement()->first();

                // Boshqa songa o'tkazilsa — yangi sonning oxiriga qo'yiladi
                $position = $placement !== null && $placement->journal_issue_id === $issue->id
                    ? $placement->position
                    : (int) IssueArticle::query()->where('journal_issue_id', $issue->id)->max('position') + 1;

                IssueArticle::query()->updateOrCreate(
                    ['article_id' => $article->id],
                    [
                        'journal_issue_id' => $issue->id,
                        'page_from' => $data['page_from'],
                        'page_to' => $data['page_to'],
                        'position' => $position,
                    ],
                );
            }

            $this->resetApproval($article);
            $article->save();
        });
    }

    public function setFormat(Article $article, bool $ok): void
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);
        $this->setChecklist($article, ['format_ok' => $ok]);

        if (! $ok) {
            $this->resetApproval($article);
            $article->save();
        }
    }

    /** Bosh muharrir tasdig'i — faqat barcha tekshiruvlar o'tganda */
    public function approve(Article $article, User $chief): void
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);

        if (! $this->ready($article)) {
            throw ValidationException::withMessages([
                'production' => __('Nashr oldidan tekshiruvning barcha bandlari bajarilmagan.'),
            ]);
        }

        $article->forceFill([
            'chief_editor_approved_by' => $chief->id,
            'chief_editor_approved_at' => now(),
        ])->save();

        $this->audit->log(AuditEvent::ProductionApproved, $article, actor: $chief);

        $article->submitter->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::DECISION,
            __('Maqolangiz nashrga tayyor'),
            __("Bosh muharrir maqolangizni nashrga tasdiqladi. Jurnal soni chop etilgach maqola saytda e'lon qilinadi."),
        ));
    }

    public function revokeApproval(Article $article): void
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);
        $this->resetApproval($article);
        $article->save();

        $this->audit->log(AuditEvent::ProductionApprovalRevoked, $article);
    }

    /** Muallif yakuniy PDF ni (korrekturani) tasdiqlaydi */
    public function authorApprove(Article $article, User $author): void
    {
        $file = $this->ensureProof($article, $author);

        $this->setChecklist($article, [
            'proof_file' => $file->uuid,
            'author_approved_at' => now()->toIso8601String(),
            'author_changes' => null,
            'author_changes_at' => null,
        ]);

        $article->layoutEditor?->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PROOF,
            __('Muallif korrekturani tasdiqladi'),
            null,
            toStaff: true,
        ));
    }

    /** Muallif korrektura bo'yicha tuzatish so'raydi (yozishmaga ham tushadi) */
    public function authorRequestChanges(Article $article, User $author, string $comment): void
    {
        $file = $this->ensureProof($article, $author);

        $this->setChecklist($article, [
            'proof_file' => $file->uuid,
            'author_approved_at' => null,
            'author_changes' => $comment,
            'author_changes_at' => now()->toIso8601String(),
        ]);
        $this->resetApproval($article);
        $article->save();

        $this->messages->send($article, $author, self::t("Korrektura (yakuniy PDF) bo'yicha tuzatishlar:")."\n".$comment);

        $article->layoutEditor?->notify(new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PROOF,
            __("Muallif korrektura bo'yicha tuzatish so'radi"),
            $comment,
            toStaff: true,
        ));
    }

    public function finalPdf(Article $article): ?ArticleFile
    {
        return $article->files()
            ->where('type', ArticleFileType::FinalPdf->value)
            ->latest('id')
            ->first();
    }

    /** Muallif joriy yakuniy PDF ni tasdiqlaganmi */
    public function authorApproved(Article $article, ?ArticleFile $finalPdf = null): bool
    {
        $finalPdf ??= $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];

        return $finalPdf !== null
            && ($checklist['proof_file'] ?? null) === $finalPdf->uuid
            && ! empty($checklist['author_approved_at']);
    }

    /**
     * Nashr oldidan tekshiruv bandlari.
     *
     * @return array<int, array{key: string, label: string, ok: bool, hint: string|null}>
     */
    public function checks(Article $article): array
    {
        $article->loadMissing(['authors']);
        $finalPdf = $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];
        $placement = $article->placement()->with('issue')->first();
        $plagiarism = $article->plagiarism_percent !== null ? (float) $article->plagiarism_percent : null;
        $max = self::plagiarismMax();
        $keywords = $article->getTranslation('keywords', $article->language, false);

        $metadata = filled($article->title)
            && filled($article->abstract)
            && is_array($keywords) && $keywords !== []
            && filled($article->udc)
            && $article->authors->isNotEmpty();

        return [
            [
                'key' => 'final_pdf',
                'label' => self::t('Yakuniy PDF yuklangan'),
                'ok' => $finalPdf !== null,
                'hint' => $finalPdf?->original_name,
            ],
            [
                'key' => 'format',
                'label' => self::t('Maqola formatga mos'),
                'ok' => ($checklist['format_ok'] ?? false) === true,
                'hint' => null,
            ],
            [
                'key' => 'plagiarism',
                'label' => $plagiarism !== null
                    ? self::t('Plagiat tekshiruvi (:percent%)', ['percent' => rtrim(rtrim(number_format($plagiarism, 2, '.', ''), '0'), '.')])
                    : self::t('Plagiat tekshiruvi'),
                'ok' => $plagiarism !== null && $plagiarism <= $max,
                'hint' => self::t('Ruxsat etilgan chegara: :max%', ['max' => $max]),
            ],
            [
                'key' => 'metadata',
                'label' => self::t("Meta ma'lumotlar to'ldirilgan"),
                'ok' => $metadata,
                'hint' => self::t("Sarlavha, annotatsiya, kalit so'zlar, UDK, mualliflar"),
            ],
            [
                'key' => 'doi',
                'label' => self::t('DOI tayyor'),
                'ok' => filled($article->doi),
                'hint' => $article->doi,
            ],
            [
                'key' => 'issue',
                'label' => self::t('Jurnal soniga biriktirilgan'),
                'ok' => $placement !== null && $placement->pages() !== null,
                'hint' => $placement !== null
                    ? $placement->issue->label.($placement->pages() !== null ? ', '.$placement->pages().'-betlar' : '')
                    : null,
            ],
            [
                'key' => 'author',
                'label' => self::t('Muallif roziligi olingan'),
                'ok' => $this->authorApproved($article, $finalPdf),
                'hint' => ($checklist['author_changes'] ?? null) !== null && ! $this->authorApproved($article, $finalPdf)
                    ? self::t("Muallif tuzatish so'ragan")
                    : null,
            ],
        ];
    }

    public function ready(Article $article): bool
    {
        return collect($this->checks($article))->every(fn (array $check): bool => $check['ok']);
    }

    /**
     * Nashr jarayoni bosqichlari (stepper).
     *
     * @return array<int, array{key: string, label: string, state: string, date: string|null}>
     */
    public function steps(Article $article): array
    {
        $finalPdf = $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];
        $issueAssigned = $article->placement()->exists();
        $started = in_array($article->status, [ArticleStatus::InProduction, ArticleStatus::Published], true);
        $published = $article->status === ArticleStatus::Published;
        $authorApproved = $this->authorApproved($article, $finalPdf);
        $startedAt = $article->statusHistories()
            ->where('to_status', ArticleStatus::InProduction->value)
            ->latest('id')
            ->first()?->created_at->toIso8601String();

        $raw = [
            ['accepted', self::t('Qabul qilindi'), true, $article->accepted_at?->toIso8601String()],
            ['layout', self::t('Maket'), $started, $startedAt],
            ['pdf', self::t('Yakuniy PDF'), $finalPdf !== null, $finalPdf?->created_at?->toIso8601String()],
            ['proof', self::t('Muallif tasdig\'i'), $authorApproved, $authorApproved && is_string($checklist['author_approved_at'] ?? null) ? $checklist['author_approved_at'] : null],
            ['issue', self::t('Jurnal soni'), $issueAssigned, null],
            ['approval', self::t('Bosh muharrir'), $article->chief_editor_approved_at !== null, $article->chief_editor_approved_at?->toIso8601String()],
            ['publish', self::t('Nashr'), $published, $article->published_at?->toIso8601String()],
        ];

        $steps = [];
        $currentFound = false;

        foreach ($raw as [$key, $label, $done, $date]) {
            $state = $done ? 'done' : ($currentFound ? 'todo' : 'current');
            $currentFound = $currentFound || ! $done;

            $steps[] = [
                'key' => $key,
                'label' => $label,
                'state' => $state,
                'date' => $done ? $date : null,
            ];
        }

        return $steps;
    }

    private function ensureProof(Article $article, User $author): ArticleFile
    {
        abort_unless($article->submitter_id === $author->id, 403);
        $this->ensureStatus($article, [ArticleStatus::InProduction]);

        $file = $this->finalPdf($article);

        if ($file === null) {
            throw ValidationException::withMessages(['proof' => __('Yakuniy PDF hali yuklanmagan.')]);
        }

        return $file;
    }

    /**
     * __() natijasini satrga keltirish (qat'iy tipli massivlar uchun).
     *
     * @param  array<string, mixed>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }

    private function resetApproval(Article $article): void
    {
        $article->forceFill(['chief_editor_approved_by' => null, 'chief_editor_approved_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function setChecklist(Article $article, array $values): void
    {
        $article->production_checklist = [...($article->production_checklist ?? []), ...$values];
        $article->save();
    }

    /**
     * @param  array<int, ArticleStatus>  $allowed
     */
    private function ensureStatus(Article $article, array $allowed): void
    {
        if (! in_array($article->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'production' => __("Maqolaning hozirgi holatida («:status») bu amalni bajarib bo'lmaydi.", [
                    'status' => $article->status->label(),
                ]),
            ]);
        }
    }
}

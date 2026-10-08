<?php

namespace App\Services\Production;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleFileService;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use App\Services\Issues\IssueService;
use App\Services\Messages\ArticleMessageService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
 *   proof_due_at       — korrektura muddati (yakuniy PDF yuklanganda: config journal.proof.deadline_days)
 *   proof_reminded_at, proof_overdue_notified_at — eslatmalar yuborilgan vaqt (app:proof-reminders)
 *   author_waived_file, author_waived_at, author_waived_by, author_waived_reason —
 *                        muddat o'tgach bosh muharrir muallif roziligisiz tasdiqlagan (sababi bilan)
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
        private readonly IssueService $issues,
    ) {}

    /** Korrektura uchun beriladigan muddat (kun) */
    public static function proofDeadlineDays(): int
    {
        return max(1, config()->integer('journal.proof.deadline_days', 5));
    }

    /** Muddat tugashidan necha soat oldin muallifga eslatma yuboriladi */
    public static function proofReminderHours(): int
    {
        return max(1, config()->integer('journal.proof.reminder_hours', 24));
    }

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

            // Betlar soni PDF dan: maqola hajmi va sondagi oraliq avtomatik yangilanadi
            if ($file->page_count !== null) {
                $article->pages_count = $file->page_count;
                $placement = $article->placement()->first();

                if ($placement !== null && $placement->page_from !== null) {
                    $placement->forceFill(['page_to' => $placement->page_from + $file->page_count - 1])->save();
                }
            }

            $this->resetApproval($article);
            $this->setChecklist($article, [
                'author_approved_at' => null,
                'proof_file' => null,
                'proof_due_at' => now()->addDays(self::proofDeadlineDays())->toIso8601String(),
                'proof_reminded_at' => null,
                'proof_overdue_notified_at' => null,
                'author_waived_file' => null,
                'author_waived_at' => null,
                'author_waived_by' => null,
                'author_waived_reason' => null,
            ]);

            $this->audit->log(AuditEvent::FinalPdfUploaded, $article, [
                'file' => $file->original_name,
                'size' => $file->size,
            ], actor: $user);

            return $file;
        });

        // Hajm o'zgargan bo'lsa — sondagi keyingi maqolalarning sahifalari ham suriladi
        $issue = $article->placement()->with('issue')->first()?->issue;

        if ($issue !== null && $file->page_count !== null) {
            $this->issues->repaginateIfComplete($issue);
        }

        $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PROOF,
            __('Korrektura tayyor: yakuniy PDF ni tekshiring'),
            __("Maqolangizning maketlangan varianti tayyor. Kabinetda PDF ni ko'rib chiqing va :date gacha tasdiqlang yoki maketdagi xatolarni yozing.", [
                'date' => now()->addDays(self::proofDeadlineDays())->format('d.m.Y H:i'),
            ]),
        ));

        return $file;
    }

    /**
     * @param  array{doi: string|null, udc: string|null, plagiarism_percent: float|null, issue_id: int|null, page_from: int|null, page_to: int|null}  $data
     */
    public function updateMetadata(Article $article, array $data): void
    {
        $this->ensureStatus($article, [ArticleStatus::Accepted, ArticleStatus::InProduction]);

        $pdfPages = $this->finalPdf($article)?->page_count;

        // PDF dagi betlar soni ma'lum bo'lsa — oxirgi bet avtomatik (qo'lda kiritilgani e'tiborsiz)
        if ($pdfPages !== null && $data['page_from'] !== null) {
            $data['page_to'] = $data['page_from'] + $pdfPages - 1;
        }

        DB::transaction(function () use ($article, $data, $pdfPages): void {
            $article->forceFill([
                'doi' => $data['doi'],
                'udc' => $data['udc'],
                'plagiarism_percent' => $data['plagiarism_percent'],
                'pages_count' => $pdfPages ?? ($data['page_from'] !== null && $data['page_to'] !== null
                    ? $data['page_to'] - $data['page_from'] + 1
                    : $article->pages_count),
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

        $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
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
            'author_waived_file' => null,
            'author_waived_at' => null,
            'author_waived_by' => null,
            'author_waived_reason' => null,
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

    /** Joriy yakuniy PDF bo'yicha bosh muharrir muallif roziligisiz tasdiqlaganmi */
    public function authorWaived(Article $article, ?ArticleFile $finalPdf = null): bool
    {
        $finalPdf ??= $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];

        return $finalPdf !== null
            && ($checklist['author_waived_file'] ?? null) === $finalPdf->uuid
            && ! empty($checklist['author_waived_at']);
    }

    /** Korrektura bosqichi yopilgan: muallif tasdiqlagan yoki tahririyat qarori bilan o'tkazilgan */
    public function proofResolved(Article $article, ?ArticleFile $finalPdf = null): bool
    {
        $finalPdf ??= $this->finalPdf($article);

        return $this->authorApproved($article, $finalPdf) || $this->authorWaived($article, $finalPdf);
    }

    /** Muallif joriy PDF bo'yicha tuzatish so'ragan (yangi PDF kutilmoqda) */
    public function proofChangesRequested(Article $article, ?ArticleFile $finalPdf = null): bool
    {
        $finalPdf ??= $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];

        return $finalPdf !== null
            && ($checklist['proof_file'] ?? null) === $finalPdf->uuid
            && is_string($checklist['author_changes'] ?? null)
            && ! $this->authorApproved($article, $finalPdf);
    }

    /**
     * Korrektura muddati. Funksiya qo'shilishidan oldin yuklangan PDF uchun —
     * PDF yuklangan vaqtdan hisoblanadi.
     */
    public function proofDueAt(Article $article, ?ArticleFile $finalPdf = null): ?CarbonImmutable
    {
        $finalPdf ??= $this->finalPdf($article);

        if ($finalPdf === null) {
            return null;
        }

        $due = $article->production_checklist['proof_due_at'] ?? null;

        if (is_string($due) && $due !== '') {
            return CarbonImmutable::parse($due);
        }

        return $finalPdf->created_at !== null
            ? CarbonImmutable::instance($finalPdf->created_at)->addDays(self::proofDeadlineDays())
            : null;
    }

    /**
     * Korrektura holati (admin va kabinet uchun).
     *
     * state: none (PDF yo'q) | pending (muallif javobi kutilmoqda) | overdue (muddat o'tgan) |
     *        changes (muallif tuzatish so'ragan) | approved | waived
     *
     * @return array{state: string, dueAt: string|null, overdue: bool, waived: array{at: string|null, by: string|null, reason: string|null}|null}
     */
    public function proofState(Article $article, ?ArticleFile $finalPdf = null): array
    {
        $finalPdf ??= $this->finalPdf($article);
        $checklist = $article->production_checklist ?? [];
        $due = $this->proofDueAt($article, $finalPdf);
        $overdue = $due !== null && now()->greaterThan($due);

        $state = match (true) {
            $finalPdf === null => 'none',
            $this->authorApproved($article, $finalPdf) => 'approved',
            $this->authorWaived($article, $finalPdf) => 'waived',
            $this->proofChangesRequested($article, $finalPdf) => 'changes',
            $overdue => 'overdue',
            default => 'pending',
        };

        return [
            'state' => $state,
            'dueAt' => $due?->toIso8601String(),
            'overdue' => $state === 'overdue',
            'waived' => $state === 'waived' ? [
                'at' => is_string($checklist['author_waived_at'] ?? null) ? $checklist['author_waived_at'] : null,
                'by' => is_string($checklist['author_waived_by'] ?? null) ? $checklist['author_waived_by'] : null,
                'reason' => is_string($checklist['author_waived_reason'] ?? null) ? $checklist['author_waived_reason'] : null,
            ] : null,
        ];
    }

    /** Bosh muharrir muallif roziligisiz tasdiqlay oladimi (muddat o'tgan, javob yo'q) */
    public function canWaiveAuthor(Article $article, ?ArticleFile $finalPdf = null): bool
    {
        return $article->status === ArticleStatus::InProduction
            && $this->proofState($article, $finalPdf)['state'] === 'overdue';
    }

    /**
     * Korrektura muddati o'tdi, muallif javob bermadi — bosh muharrir qarori bilan
     * muallif roziligi bandini yopish. Sabab majburiy; muallifga xabar va audit yoziladi.
     */
    public function waiveAuthorApproval(Article $article, User $chief, string $reason): void
    {
        $this->ensureStatus($article, [ArticleStatus::InProduction]);
        $finalPdf = $this->finalPdf($article);

        if (! $this->canWaiveAuthor($article, $finalPdf) || $finalPdf === null) {
            throw ValidationException::withMessages([
                'reason' => __("Muallif roziligisiz tasdiqlash faqat korrektura muddati o'tgan va muallif javob bermagan holatda mumkin."),
            ]);
        }

        DB::transaction(function () use ($article, $chief, $reason, $finalPdf): void {
            $this->setChecklist($article, [
                'author_waived_file' => $finalPdf->uuid,
                'author_waived_at' => now()->toIso8601String(),
                'author_waived_by' => $chief->name,
                'author_waived_reason' => $reason,
            ]);

            $this->audit->log(AuditEvent::ProofApprovalWaived, $article, [
                'file' => $finalPdf->original_name,
                'due_at' => $this->proofDueAt($article, $finalPdf),
                'reason' => $reason,
            ], actor: $chief);
        });

        $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PROOF,
            __('Korrektura tahririyat qarori bilan tasdiqlandi'),
            $reason,
        ));
    }

    /**
     * Korrektura eslatmalari (har soatda: app:proof-reminders).
     * Muddat tugashiga oz qolganda — muallifga, muddat o'tganda — maketchi va bosh muharrirlarga.
     *
     * @return array{reminded: int, overdue: int}
     */
    public function sendProofReminders(): array
    {
        $counts = ['reminded' => 0, 'overdue' => 0];
        $now = now();

        $articles = Article::query()
            ->where('status', ArticleStatus::InProduction->value)
            ->with(['submitter', 'layoutEditor'])
            ->get();

        foreach ($articles as $article) {
            $finalPdf = $this->finalPdf($article);
            $state = $this->proofState($article, $finalPdf);
            $checklist = $article->production_checklist ?? [];
            $due = $state['dueAt'] !== null ? CarbonImmutable::parse($state['dueAt']) : null;

            if ($due === null) {
                continue;
            }

            if ($state['state'] === 'pending'
                && empty($checklist['proof_reminded_at'])
                && $now->greaterThanOrEqualTo($due->subHours(self::proofReminderHours()))) {
                $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
                    $article,
                    ArticleUpdateNotification::PROOF,
                    __('Eslatma: korrektura muddati tugayapti'),
                    self::t("Yakuniy PDF ni :date gacha tasdiqlang yoki maketdagi xatolarni yozing. Javob bo'lmasa, tahririyat maqolani o'z qarori bilan nashrga yuborishi mumkin.", [
                        'date' => $due->format('d.m.Y H:i'),
                    ]),
                ));
                $this->setChecklist($article, ['proof_reminded_at' => $now->toIso8601String()]);
                $counts['reminded']++;
            }

            if ($state['state'] === 'overdue' && empty($checklist['proof_overdue_notified_at'])) {
                foreach ($this->proofStaff($article) as $staff) {
                    $staff->notify(new ArticleUpdateNotification(
                        $article,
                        ArticleUpdateNotification::PROOF,
                        __("Korrektura muddati o'tdi: muallif javob bermadi"),
                        self::t('Bosh muharrir muallifga yana murojaat qilishi yoki nashr jarayonida «Muallifsiz tasdiqlash» orqali davom ettirishi mumkin.'),
                        toStaff: true,
                    ));
                }

                $this->setChecklist($article, ['proof_overdue_notified_at' => $now->toIso8601String()]);
                $counts['overdue']++;
            }
        }

        return $counts;
    }

    /**
     * Muddat o'tgani haqida xabar oladigan xodimlar: maketchi va bosh muharrirlar.
     *
     * @return Collection<int, User>
     */
    private function proofStaff(Article $article): Collection
    {
        $chiefs = User::query()
            ->where('is_blocked', false)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::ChiefEditor->value))
            ->get();

        return collect([$article->layoutEditor])
            ->merge($chiefs)
            ->filter(fn (?User $user): bool => $user !== null)
            ->unique('id')
            ->values();
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
                'ok' => $this->proofResolved($article, $finalPdf),
                'hint' => $this->proofHint($article, $finalPdf),
            ],
        ];
    }

    private function proofHint(Article $article, ?ArticleFile $finalPdf): ?string
    {
        $state = $this->proofState($article, $finalPdf);
        $due = $state['dueAt'] !== null ? CarbonImmutable::parse($state['dueAt'])->format('d.m.Y H:i') : null;

        return match ($state['state']) {
            'changes' => self::t("Muallif tuzatish so'ragan — yangi PDF yuklang"),
            'waived' => self::t('Tahririyat qarori bilan (muallif muddatida javob bermadi)'),
            'overdue' => self::t("Muddat o'tgan (:date) — muallif javob bermadi", ['date' => $due]),
            'pending' => $due !== null ? self::t('Javob muddati: :date', ['date' => $due]) : null,
            default => null,
        };
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
        $authorApproved = $this->proofResolved($article, $finalPdf);
        $proofDate = $checklist['author_approved_at'] ?? $checklist['author_waived_at'] ?? null;
        $startedAt = $article->statusHistories()
            ->where('to_status', ArticleStatus::InProduction->value)
            ->latest('id')
            ->first()?->created_at->toIso8601String();

        $raw = [
            ['accepted', self::t('Qabul qilindi'), true, $article->accepted_at?->toIso8601String()],
            ['layout', self::t('Maket'), $started, $startedAt],
            ['pdf', self::t('Yakuniy PDF'), $finalPdf !== null, $finalPdf?->created_at?->toIso8601String()],
            ['proof', self::t('Muallif tasdig\'i'), $authorApproved, $authorApproved && is_string($proofDate) ? $proofDate : null],
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

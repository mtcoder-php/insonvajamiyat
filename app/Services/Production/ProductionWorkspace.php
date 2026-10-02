<?php

namespace App\Services\Production;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleNote;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use App\Services\Publishing\PublishService;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * "Nashr jarayoni" sahifalari uchun ma'lumotlar: navbatlar, statistika, maqola kartasi.
 */
class ProductionWorkspace
{
    public const PER_PAGE = 15;

    /** Navbat → holatlar (approved — tasdiqlangan, published — nashr etilgan) */
    public const TABS = ['new', 'production', 'approved', 'published', 'all'];

    public function __construct(
        private readonly ProductionService $production,
        private readonly PublishService $publisher,
    ) {}

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $counts = [];

        foreach (self::TABS as $tab) {
            $counts[$tab] = $this->query($tab)->count();
        }

        return $counts;
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, hint: string}>
     */
    public function stats(): array
    {
        $inProduction = Article::query()->where('status', ArticleStatus::InProduction->value);

        return [
            ['key' => 'new', 'label' => 'Maketga olinishi kutilmoqda', 'value' => $this->query('new')->count(), 'hint' => 'Qabul qilingan maqolalar'],
            ['key' => 'layout', 'label' => 'Maketlanmoqda', 'value' => (clone $inProduction)->whereNull('chief_editor_approved_at')->count(), 'hint' => 'PDF, korrektura, tekshiruv'],
            ['key' => 'approved', 'label' => 'Nashrga tayyor', 'value' => (clone $inProduction)->whereNotNull('chief_editor_approved_at')->count(), 'hint' => 'Bosh muharrir tasdiqlagan'],
            ['key' => 'published', 'label' => 'Shu oy nashr etildi', 'value' => Article::query()->where('status', ArticleStatus::Published->value)->where('published_at', '>=', now()->startOfMonth())->count(), 'hint' => now()->translatedFormat('F Y')],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Article>
     */
    public function list(string $tab, ?string $search): LengthAwarePaginator
    {
        return $this->query($tab)
            ->when($search !== null && $search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('title->'.app()->getLocale(), 'like', '%'.$search.'%')
                ->orWhere('title->uz', 'like', '%'.$search.'%')
                ->orWhere('doi', 'like', '%'.$search.'%')))
            ->with(['submitter', 'placement.issue', 'subject'])
            ->orderByRaw("case status when 'accepted' then 0 when 'in_production' then 1 else 2 end")
            ->latest('accepted_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function listItem(Article $article): array
    {
        $checks = $article->status === ArticleStatus::InProduction ? $this->production->checks($article) : [];

        return [
            'uuid' => $article->uuid,
            'code' => EditorialWorkspace::code($article),
            'title' => $article->title,
            'author' => $article->submitter->name,
            'subject' => $article->subject?->name,
            'status' => $article->status->value,
            'statusGroup' => $article->status->group(),
            'statusLabel' => $article->status->label(),
            'issue' => $article->placement?->issue->label,
            'pages' => $article->placement?->pages(),
            'doi' => $article->doi,
            'approved' => $article->chief_editor_approved_at !== null,
            'progress' => [
                'done' => collect($checks)->where('ok', true)->count(),
                'total' => count($checks),
            ],
            'acceptedAt' => $article->accepted_at?->toIso8601String(),
            'url' => route('admin.production.show', $article->uuid),
        ];
    }

    /**
     * Maqola kartasi (admin publisher page.png).
     *
     * @return array<string, mixed>
     */
    public function detail(Article $article, User $user): array
    {
        $article->load(['submitter', 'subject', 'articleType', 'authors', 'files', 'notes.user', 'layoutEditor', 'chiefApprover', 'placement.issue']);
        $article->placement?->issue->loadCount('articles');

        $finalPdf = $this->production->finalPdf($article);
        $checks = $this->production->checks($article);
        $ready = collect($checks)->every(fn (array $c): bool => $c['ok']);
        $checklist = $article->production_checklist ?? [];
        $status = $article->status;
        $inProduction = $status === ArticleStatus::InProduction;
        $canManage = $user->can(PermissionName::ProductionManage->value);
        $canApprove = $user->can(PermissionName::IssuesPublish->value);
        $placement = $article->placement;
        $issue = $placement?->issue;
        $keywords = $article->getTranslation('keywords', $article->language, false);
        $previewFile = $finalPdf ?? $article->files
            ->filter(fn (ArticleFile $f): bool => $f->extension() === 'pdf' && in_array($f->type, [ArticleFileType::Revision, ArticleFileType::Manuscript], true))
            ->sortByDesc('id')
            ->first();

        return [
            'uuid' => $article->uuid,
            'code' => EditorialWorkspace::code($article),
            'title' => $article->title,
            'status' => $status->value,
            'statusGroup' => $status->group(),
            'statusLabel' => $status->label(),
            'type' => $article->articleType->name,
            'subject' => $article->subject?->name,
            'language' => $article->language,
            'abstract' => $article->getTranslation('abstract', $article->language, true),
            'keywords' => is_array($keywords) ? array_values(array_filter($keywords, 'is_string')) : [],
            'doi' => $article->doi,
            'udc' => $article->udc,
            'pagesCount' => $article->pages_count,
            'plagiarism' => $article->plagiarism_percent !== null ? (float) $article->plagiarism_percent : null,
            'plagiarismMax' => ProductionService::plagiarismMax(),
            'acceptedAt' => $article->accepted_at?->toIso8601String(),
            'submitter' => $article->submitter->name,
            'authors' => $article->authors->map(fn (ArticleAuthor $a): array => [
                'name' => $a->full_name,
                'organization' => $a->organization,
                'isCorresponding' => $a->is_corresponding,
            ])->all(),
            'issue' => $placement !== null ? [
                'id' => $placement->issue->id,
                'label' => $placement->issue->label,
                'year' => $placement->issue->year,
                'volume' => $placement->issue->volume,
                'number' => $placement->issue->number,
                'status' => $placement->issue->status->value,
                'statusLabel' => $placement->issue->status->label(),
                'publishedAt' => $placement->issue->published_at?->toIso8601String(),
                'articlesCount' => (int) $placement->issue->getAttribute('articles_count'),
                'pageFrom' => $placement->page_from,
                'pageTo' => $placement->page_to,
                'position' => $placement->position,
            ] : null,
            'files' => $article->files->sortByDesc('id')->values()->map(fn (ArticleFile $file): array => [
                'uuid' => $file->uuid,
                'name' => $file->original_name,
                'type' => $file->type->value,
                'typeLabel' => $file->type->label(),
                'extension' => $file->extension(),
                'size' => $file->size,
                'uploadedAt' => $file->created_at?->toIso8601String(),
                'viewUrl' => $file->extension() === 'pdf' ? route('admin.production.files', [$article->uuid, $file->uuid]) : null,
                'downloadUrl' => route('admin.production.files', [$article->uuid, $file->uuid, 'download' => 1]),
            ])->all(),
            'coverUrl' => MediaUrl::from($article->cover_image_path),
            'finalPdf' => $finalPdf !== null ? [
                'name' => $finalPdf->original_name,
                'size' => $finalPdf->size,
                'uploadedAt' => $finalPdf->created_at?->toIso8601String(),
            ] : null,
            'preview' => $previewFile !== null ? [
                'name' => $previewFile->original_name,
                'isFinal' => $finalPdf !== null,
                'url' => route('admin.production.files', [$article->uuid, $previewFile->uuid]),
                'downloadUrl' => route('admin.production.files', [$article->uuid, $previewFile->uuid, 'download' => 1]),
            ] : null,
            'checks' => $checks,
            'ready' => $ready,
            'steps' => $this->production->steps($article),
            'production' => [
                'formatOk' => ($checklist['format_ok'] ?? false) === true,
                'authorApproved' => $this->production->authorApproved($article, $finalPdf),
                'proof' => $this->production->proofState($article, $finalPdf),
                'authorApprovedAt' => is_string($checklist['author_approved_at'] ?? null) ? $checklist['author_approved_at'] : null,
                'authorChanges' => is_string($checklist['author_changes'] ?? null) ? $checklist['author_changes'] : null,
                'authorChangesAt' => is_string($checklist['author_changes_at'] ?? null) ? $checklist['author_changes_at'] : null,
                'layoutEditor' => $article->layoutEditor?->name,
                'approvedBy' => $article->chiefApprover?->name,
                'approvedAt' => $article->chief_editor_approved_at?->toIso8601String(),
            ],
            'notes' => $article->notes->map(fn (ArticleNote $note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'avatar' => $note->user->avatarUrl(),
                'role' => null,
                'mine' => $note->user_id === $user->id,
                'createdAt' => $note->created_at->toIso8601String(),
            ])->all(),
            'form' => [
                'doi' => $article->doi ?? $this->suggestDoi($article),
                'udc' => $article->udc,
                'plagiarism_percent' => $article->plagiarism_percent !== null ? (float) $article->plagiarism_percent : null,
                'issue_id' => $issue?->id,
                'page_from' => $placement?->page_from,
                'page_to' => $placement?->page_to,
            ],
            'can' => [
                'start' => $canManage && $status === ArticleStatus::Accepted,
                'manage' => $canManage && $inProduction,
                'edit' => $canManage && in_array($status, [ArticleStatus::Accepted, ArticleStatus::InProduction], true),
                'approve' => $canApprove && $inProduction && $ready && $article->chief_editor_approved_at === null,
                'revoke' => $canApprove && $inProduction && $article->chief_editor_approved_at !== null,
                'waiveProof' => $canApprove && $this->production->canWaiveAuthor($article, $finalPdf),
                'cancel' => $canManage && $inProduction,
                'cover' => $canManage && in_array($status, [ArticleStatus::Accepted, ArticleStatus::InProduction, ArticleStatus::Published], true),
                // Chop etilgan songa keyin qo'shilgan maqola — alohida chop etiladi
                'publish' => $canApprove && $inProduction && $this->publisher->canPublishArticle($article),
                'note' => true,
            ],
            'urls' => [
                'index' => route('admin.production.index'),
                'start' => route('admin.production.start', $article->uuid),
                'upload' => route('admin.production.final-pdf', $article->uuid),
                'metadata' => route('admin.production.metadata', $article->uuid),
                'format' => route('admin.production.format', $article->uuid),
                'approve' => route('admin.production.approve', $article->uuid),
                'revoke' => route('admin.production.revoke', $article->uuid),
                'waiveProof' => route('admin.production.waive-proof', $article->uuid),
                'cancel' => route('admin.production.cancel', $article->uuid),
                'cover' => route('admin.production.cover', $article->uuid),
                'notes' => route('admin.production.notes', $article->uuid),
                'publish' => route('admin.production.publish', $article->uuid),
                'public' => $article->isPublished() ? route('articles.show', (string) $article->slug) : null,
            ],
        ];
    }

    /**
     * Songa biriktirish uchun jurnal sonlari (yangilari birinchi).
     *
     * @return array<int, array{id: int, label: string, status: string, statusLabel: string}>
     */
    public function issues(): array
    {
        return JournalIssue::query()
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->limit(30)
            ->get()
            ->map(fn (JournalIssue $issue): array => [
                'id' => $issue->id,
                'label' => $issue->label,
                'status' => $issue->status->value,
                'statusLabel' => $issue->status->label(),
            ])
            ->all();
    }

    /** DOI taklifi: {prefix}/insonvajamiyat.{yil}.{id} (prefiks sozlanmagan bo'lsa — null) */
    public function suggestDoi(Article $article): ?string
    {
        $prefix = config('journal.doi_prefix');

        if (! is_string($prefix) || $prefix === '') {
            return null;
        }

        $year = ($article->accepted_at ?? $article->created_at)->year ?? (int) now()->year;

        return sprintf('%s/insonvajamiyat.%d.%04d', rtrim($prefix, '/'), $year, $article->id);
    }

    /**
     * @return Builder<Article>
     */
    private function query(string $tab): Builder
    {
        $query = Article::query();

        return match ($tab) {
            'new' => $query->where('status', ArticleStatus::Accepted->value),
            'production' => $query->where('status', ArticleStatus::InProduction->value)->whereNull('chief_editor_approved_at'),
            'approved' => $query->where('status', ArticleStatus::InProduction->value)->whereNotNull('chief_editor_approved_at'),
            'published' => $query->where('status', ArticleStatus::Published->value),
            default => $query->whereIn('status', [ArticleStatus::Accepted->value, ArticleStatus::InProduction->value, ArticleStatus::Published->value]),
        };
    }
}

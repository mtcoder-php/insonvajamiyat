<?php

namespace App\Services\Issues;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use App\Services\Publishing\PublishService;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * "Jurnallar" bo'limi uchun ma'lumotlar: sonlar ro'yxati, son kartasi, bo'sh maqolalar.
 */
class IssueWorkspace
{
    public function __construct(private readonly PublishService $publisher) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?int $year, ?string $status): array
    {
        return JournalIssue::query()
            ->when($year !== null, fn (Builder $q) => $q->where('year', $year))
            ->when($status !== null, fn (Builder $q) => $q->where('status', $status))
            ->withCount('articles')
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->get()
            ->map(function (JournalIssue $issue): array {
                $placements = IssueArticle::query()->where('journal_issue_id', $issue->id)->with('article')->get();

                return [
                    'slug' => $issue->slug,
                    'label' => $issue->label,
                    'title' => $issue->title,
                    'year' => $issue->year,
                    'volume' => $issue->volume,
                    'number' => $issue->number,
                    'doi' => $issue->doi,
                    'status' => $issue->status->value,
                    'statusLabel' => $issue->status->label(),
                    'publishedAt' => $issue->published_at?->toIso8601String(),
                    'coverUrl' => $this->coverUrl($issue),
                    'articles' => (int) $issue->getAttribute('articles_count'),
                    'ready' => $placements->filter(fn (IssueArticle $p): bool => $this->isReady($p->article))->count(),
                    'pages' => (int) $placements->max('page_to'),
                    'hasPdf' => filled($issue->full_pdf_path),
                    'url' => route('admin.issues.show', $issue->slug),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, hint: string}>
     */
    public function stats(): array
    {
        return [
            ['key' => 'total', 'label' => 'Jami sonlar', 'value' => JournalIssue::query()->count(), 'hint' => 'Barcha yillar'],
            ['key' => 'draft', 'label' => 'Shakllantirilmoqda', 'value' => JournalIssue::query()->where('status', IssueStatus::Draft->value)->count(), 'hint' => 'Qoralama sonlar'],
            ['key' => 'published', 'label' => 'Chop etilgan', 'value' => JournalIssue::query()->where('status', IssueStatus::Published->value)->count(), 'hint' => 'Saytda ko\'rinadi'],
            ['key' => 'waiting', 'label' => 'Songa biriktirilmagan', 'value' => $this->availableQuery()->count(), 'hint' => 'Qabul qilingan maqolalar'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function years(): array
    {
        return JournalIssue::query()->distinct()->orderByDesc('year')->pluck('year')->map(fn (mixed $y): int => (int) $y)->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(JournalIssue $issue, User $user): array
    {
        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with(['article.authors', 'article.subject', 'article.files'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $canManage = $user->can(PermissionName::IssuesManage->value);

        $articles = $placements->map(function (IssueArticle $p) use ($issue, $canManage): array {
            $article = $p->article;
            $section = self::section($p);
            $editable = in_array($article->status, IssueService::PLACEABLE, true);

            return [
                'id' => $article->id,
                'uuid' => $article->uuid,
                'position' => $p->position,
                'code' => EditorialWorkspace::code($article),
                'title' => $article->title,
                'authors' => $article->authors->map(fn (ArticleAuthor $a): string => $a->full_name)->implode(', '),
                'subject' => $article->subject?->name,
                'status' => $article->status->value,
                'statusGroup' => $article->status->group(),
                'statusLabel' => $article->status->label(),
                'section' => $section,
                'pageFrom' => $p->page_from,
                'pageTo' => $p->page_to,
                'pagesCount' => $article->pages_count,
                'doi' => $article->doi,
                'approved' => $article->chief_editor_approved_at !== null,
                'ready' => $this->isReady($article),
                'hasFinalPdf' => $article->files->contains(fn (ArticleFile $f): bool => $f->type === ArticleFileType::FinalPdf),
                'editable' => $canManage && $editable,
                'productionUrl' => route('admin.production.show', $article->uuid),
                'urls' => [
                    'update' => route('admin.issues.articles.update', [$issue->slug, $article->uuid]),
                    'destroy' => route('admin.issues.articles.destroy', [$issue->slug, $article->uuid]),
                ],
            ];
        })->all();

        $isDraft = $issue->status === IssueStatus::Draft;
        $problems = $isDraft ? $this->publisher->issueProblems($issue) : [];
        $total = count($articles);
        $ready = collect($articles)->where('ready', true)->count();
        $withPages = collect($articles)->filter(fn (array $a): bool => $a['pageFrom'] !== null && $a['pageTo'] !== null)->count();

        return [
            'slug' => $issue->slug,
            'label' => $issue->label,
            'year' => $issue->year,
            'volume' => $issue->volume,
            'number' => $issue->number,
            'doi' => $issue->doi,
            'title' => $issue->getTranslation('title', app()->getLocale(), false) ?: null,
            'description' => $issue->getTranslation('description', app()->getLocale(), false) ?: null,
            'status' => $issue->status->value,
            'statusLabel' => $issue->status->label(),
            'publishedAt' => $issue->published_at?->toIso8601String(),
            'createdAt' => $issue->created_at?->toIso8601String(),
            'coverUrl' => $this->coverUrl($issue),
            'hasOwnCover' => filled($issue->cover_image_path),
            'files' => [
                'pdf' => $this->file($issue->full_pdf_path),
                'toc' => $this->file($issue->toc_file_path),
            ],
            'articles' => $articles,
            'problems' => $problems,
            'publicUrl' => $isDraft ? null : route('issues.show', $issue->slug),
            'summary' => [
                'total' => $total,
                'ready' => $ready,
                'withPages' => $withPages,
                'pages' => (int) $placements->max('page_to'),
                'complete' => $total > 0 && $ready === $total && $withPages === $total,
            ],
            'can' => [
                'manage' => $canManage,
                'delete' => $canManage && $issue->status === IssueStatus::Draft && $total === 0,
                'publish' => $isDraft && $problems === [] && $user->can(PermissionName::IssuesPublish->value),
            ],
            'urls' => [
                'index' => route('admin.issues.index'),
                'update' => route('admin.issues.update', $issue->slug),
                'destroy' => route('admin.issues.destroy', $issue->slug),
                'files' => route('admin.issues.files.store', $issue->slug),
                'fileDestroy' => route('admin.issues.files.destroy', [$issue->slug, '__type__']),
                'attach' => route('admin.issues.articles.store', $issue->slug),
                'reorder' => route('admin.issues.articles.reorder', $issue->slug),
                'paginate' => route('admin.issues.articles.paginate', $issue->slug),
                'toc' => route('admin.issues.toc', $issue->slug),
                'publish' => route('admin.issues.publish', $issue->slug),
            ],
        ];
    }

    /**
     * Songa biriktirilmagan, qabul qilingan / nashrga tayyorlanayotgan maqolalar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function available(): array
    {
        return $this->availableQuery()
            ->with('submitter')
            ->latest('accepted_at')
            ->limit(100)
            ->get()
            ->map(fn (Article $article): array => [
                'id' => $article->id,
                'code' => EditorialWorkspace::code($article),
                'title' => $article->title,
                'author' => $article->submitter->name,
                'statusLabel' => $article->status->label(),
                'statusGroup' => $article->status->group(),
                'approved' => $article->chief_editor_approved_at !== null,
                'pagesCount' => $article->pages_count,
            ])
            ->all();
    }

    /**
     * Mundarija: ruknlar bo'yicha guruhlangan maqolalar (chop etish sahifasi uchun).
     *
     * @return array<int, array{section: string|null, articles: array<int, array{title: string, authors: string, pages: string|null}>}>
     */
    public function toc(JournalIssue $issue): array
    {
        $groups = [];

        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with('article.authors')
            ->orderBy('position')
            ->get();

        foreach ($placements as $p) {
            $section = self::section($p);
            $last = array_key_last($groups);

            if ($last === null || $groups[$last]['section'] !== $section) {
                $groups[] = ['section' => $section, 'articles' => []];
                $last = array_key_last($groups);
            }

            $groups[$last]['articles'][] = [
                'title' => $p->article->title,
                'authors' => $p->article->authors->map(fn (ArticleAuthor $a): string => $a->full_name)->implode(', '),
                'pages' => $p->pages(),
            ];
        }

        return $groups;
    }

    /** Rukn nomi joriy tilda (bo'lmasa — birinchi mavjud til) */
    public static function section(IssueArticle $placement): ?string
    {
        $sections = $placement->section;

        if (! is_array($sections) || $sections === []) {
            return null;
        }

        $value = $sections[app()->getLocale()] ?? reset($sections);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function coverUrl(JournalIssue $issue): ?string
    {
        return MediaUrl::from($issue->cover_image_path)
            ?? MediaUrl::publicAsset(config('journal.default_issue_cover'));
    }

    /** Nashrga tayyor: bosh muharrir tasdiqlagan yoki allaqachon nashr etilgan */
    private function isReady(Article $article): bool
    {
        return $article->status === ArticleStatus::Published
            || ($article->status === ArticleStatus::InProduction && $article->chief_editor_approved_at !== null);
    }

    /**
     * @return array{url: string|null, name: string, size: int|null}|null
     */
    private function file(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        return [
            'url' => MediaUrl::from($path),
            'name' => basename($path),
            'size' => Storage::disk(MediaUrl::DISK)->exists($path) ? Storage::disk(MediaUrl::DISK)->size($path) : null,
        ];
    }

    /**
     * @return Builder<Article>
     */
    private function availableQuery(): Builder
    {
        return Article::query()
            ->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, IssueService::PLACEABLE))
            ->whereDoesntHave('placement');
    }
}

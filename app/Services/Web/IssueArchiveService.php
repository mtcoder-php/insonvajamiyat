<?php

namespace App\Services\Web;

use App\Enums\ArticleFileType;
use App\Http\Resources\Web\ArticleCardResource;
use App\Http\Resources\Web\IssueCardResource;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Services\Issues\IssueWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Jurnal sonlari arxivi (web jurnal sonlari.png) va son sahifasi.
 * Faqat chop etilgan sonlar va ulardagi nashr etilgan maqolalar.
 */
class IssueArchiveService
{
    public const LATEST = 4;

    /**
     * Yillar bo'yicha daraxt: yil → sonlar.
     *
     * @return array<int, array{year: int, count: int, issues: array<int, array{slug: string, label: string, url: string, publishedAt: string|null}>}>
     */
    public function tree(): array
    {
        return $this->published()
            ->get()
            ->groupBy('year')
            ->map(fn (Collection $issues, int|string $year): array => [
                'year' => (int) $year,
                'count' => $issues->count(),
                'issues' => $issues->map(fn (JournalIssue $i): array => [
                    'slug' => $i->slug,
                    'label' => $i->label,
                    'url' => route('issues.show', $i->slug),
                    'publishedAt' => $i->published_at?->toIso8601String(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(): array
    {
        return $this->cards($this->published()->limit(self::LATEST)->get());
    }

    /**
     * Tanlangan yil sonlari.
     *
     * @return array<int, array<string, mixed>>
     */
    public function year(int $year, string $sort): array
    {
        $query = $this->published()->where('year', $year);

        if ($sort === 'oldest') {
            $query->reorder()->orderBy('number');
        }

        return $this->cards($query->get());
    }

    /**
     * Son sahifasi: ruknlar bo'yicha mundarija, statistika, qo'shni sonlar.
     *
     * @return array<string, mixed>
     */
    public function show(JournalIssue $issue): array
    {
        $issue->loadCount(['articles' => fn (Builder $q) => $q->published()]);

        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->whereHas('article', fn (Builder $q) => $q->published())
            ->with(['article.authors', 'article.subject', 'article.files' => fn ($q) => $q->where('type', ArticleFileType::FinalPdf->value)])
            ->orderBy('position')
            ->get();

        $sections = [];

        foreach ($placements as $placement) {
            $section = IssueWorkspace::section($placement);
            $last = array_key_last($sections);

            if ($last === null || $sections[$last]['title'] !== $section) {
                $sections[] = ['title' => $section, 'articles' => []];
                $last = array_key_last($sections);
            }

            $article = $placement->article;
            $sections[$last]['articles'][] = [
                'id' => $article->id,
                'title' => $article->title,
                'url' => route('articles.show', (string) $article->slug),
                'authors' => $article->authors->sortBy('sort_order')
                    ->map(fn (ArticleAuthor $a): string => $a->short_name)
                    ->implode(', '),
                'subject' => $article->subject !== null ? ['name' => $article->subject->name, 'slug' => $article->subject->slug] : null,
                'pages' => $placement->pages(),
                'doi' => $article->doi,
                'views' => $article->views_count,
                'pdfUrl' => $article->files->isNotEmpty() ? route('articles.pdf', (string) $article->slug) : null,
            ];
        }

        $neighbour = fn (Builder $q): ?array => ($i = $q->first()) instanceof JournalIssue
            ? ['label' => $i->label, 'url' => route('issues.show', $i->slug)]
            : null;

        return [
            'issue' => [
                ...IssueCardResource::make($issue)->resolve(),
                'authorsCount' => $this->authorsCount([$issue->id])[$issue->id] ?? 0,
                'pagesTotal' => (int) $placements->max('page_to') ?: null,
            ],
            'sections' => $sections,
            'neighbours' => [
                'prev' => $neighbour($this->published()->where('published_at', '<', $issue->published_at)),
                'next' => $neighbour($this->published()->reorder()->oldest('published_at')->where('published_at', '>', $issue->published_at)),
            ],
        ];
    }

    /**
     * So'nggi nashr etilgan maqolalar (yon panel).
     *
     * @return array<int, mixed>
     */
    public function latestArticles(int $limit = 3): array
    {
        $articles = Article::query()
            ->published()
            ->with(['authors', 'subject'])
            ->latest('published_at')
            ->limit($limit)
            ->get();

        return ArticleCardResource::collection($articles)->resolve();
    }

    /**
     * Sonlar kartochkalari: maqola va mualliflar soni bilan.
     *
     * @param  Collection<int, JournalIssue>  $issues
     * @return array<int, array<string, mixed>>
     */
    private function cards(Collection $issues): array
    {
        $issues->loadCount(['articles' => fn (Builder $q) => $q->published()]);
        $authors = $this->authorsCount($issues->modelKeys());

        return $issues->map(fn (JournalIssue $issue): array => [
            ...IssueCardResource::make($issue)->resolve(),
            'authorsCount' => $authors[$issue->id] ?? 0,
        ])->all();
    }

    /**
     * Har bir sondagi (nashr etilgan maqolalar) noyob mualliflar soni.
     *
     * @param  array<int, int|string>  $issueIds
     * @return array<int, int>
     */
    private function authorsCount(array $issueIds): array
    {
        if ($issueIds === []) {
            return [];
        }

        $rows = DB::table('issue_articles')
            ->join('articles', 'articles.id', '=', 'issue_articles.article_id')
            ->join('article_authors', 'article_authors.article_id', '=', 'articles.id')
            ->whereIn('issue_articles.journal_issue_id', $issueIds)
            ->whereNotNull('articles.published_at')
            ->whereNull('articles.deleted_at')
            ->select(['issue_articles.journal_issue_id', 'article_authors.last_name', 'article_authors.first_name'])
            ->distinct()
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $id = (int) $row->journal_issue_id;
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return Builder<JournalIssue>
     */
    private function published(): Builder
    {
        return JournalIssue::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('number');
    }
}

<?php

namespace App\Services\Web;

use App\Enums\ArticleFileType;
use App\Enums\IssueStatus;
use App\Http\Resources\Web\IssueCardResource;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\JournalIssue;
use App\Models\Post;
use App\Models\Subject;
use App\Support\MediaUrl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Maqolalar katalogi (web maqola katalogi.png): qidiruv, filtrlar, saralash va yon panel.
 * Faqat nashr etilgan maqolalar.
 */
class CatalogService
{
    /** @var array<int, array{slug: string, name: string, count: int}>|null */
    private ?array $subjects = null;

    public const PER_PAGE = 10;

    public const SORTS = ['newest', 'oldest', 'popular', 'downloads', 'title'];

    /**
     * @param  array{q: string|null, subjects: array<int, string>, year: int|null, issue: string|null, author: string|null, keyword: string|null, sort: string}  $filters
     * @return LengthAwarePaginator<int, Article>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Article::query()
            ->published()
            ->with(['authors', 'subject', 'placement.issue', 'files' => fn ($q) => $q->where('type', ArticleFileType::FinalPdf->value)]);

        if ($filters['q'] !== null) {
            $like = '%'.mb_strtolower($filters['q']).'%';
            $query->where(fn (Builder $w) => $w
                ->whereRaw('lower(search_text) like ?', [$like])
                ->orWhere('title->'.app()->getLocale(), 'like', '%'.$filters['q'].'%')
                ->orWhere('title->uz', 'like', '%'.$filters['q'].'%')
                ->orWhere('doi', 'like', '%'.$filters['q'].'%')
                ->orWhereHas('authors', fn (Builder $a) => $this->authorMatch($a, $filters['q'])));
        }

        if ($filters['subjects'] !== []) {
            $query->whereHas('subject', fn (Builder $s) => $s->whereIn('slug', $filters['subjects']));
        }

        if ($filters['year'] !== null) {
            // whereYear indeksni ishlatmaydi — yil chegaralari bilan
            $query->whereBetween('published_at', [
                CarbonImmutable::create((int) $filters['year'])->startOfYear(),
                CarbonImmutable::create((int) $filters['year'])->endOfYear(),
            ]);
        }

        if ($filters['issue'] !== null) {
            $query->whereHas('placement.issue', fn (Builder $i) => $i->where('slug', $filters['issue']));
        }

        if ($filters['author'] !== null) {
            $query->whereHas('authors', fn (Builder $a) => $this->authorMatch($a, (string) $filters['author']));
        }

        if ($filters['keyword'] !== null) {
            $query->whereRaw('lower(search_text) like ?', ['%'.mb_strtolower($filters['keyword']).'%']);
        }

        $query = match ($filters['sort']) {
            'oldest' => $query->oldest('published_at'),
            'popular' => $query->orderByDesc('views_count'),
            'downloads' => $query->orderByDesc('downloads_count'),
            'title' => $query->orderBy('title->'.app()->getLocale()),
            default => $query->latest('published_at'),
        };

        return $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Katalog kartochkasi (gorizontal): muqova, yo'nalish, mualliflar, tashkilot, kalit so'zlar,
     * son, sana, DOI, PDF.
     *
     * @return array<string, mixed>
     */
    public function card(Article $article): array
    {
        $authors = $article->authors->sortBy('sort_order')->values();
        $names = $authors->take(3)->map(fn (ArticleAuthor $a): string => $a->short_name)->implode(', ')
            .($authors->count() > 3 ? ' va boshq.' : '');
        $keywords = $article->getTranslation('keywords', app()->getLocale(), true);
        $pdf = $article->files->sortByDesc('id')->first();
        $issue = $article->placement?->issue;

        return [
            'id' => $article->id,
            'url' => route('articles.show', (string) $article->slug),
            'title' => $article->title,
            'authors' => $names,
            'organization' => $authors->first()?->organization,
            'subject' => $article->subject !== null ? ['name' => $article->subject->name, 'slug' => $article->subject->slug] : null,
            'keywords' => is_array($keywords) ? array_slice(array_values(array_filter($keywords, 'is_string')), 0, 3) : [],
            'coverUrl' => MediaUrl::from($article->cover_image_path),
            'issue' => $issue !== null ? ['label' => $issue->label, 'url' => route('issues.show', $issue->slug)] : null,
            'doi' => $article->doi,
            'publishedAt' => $article->published_at?->toIso8601String(),
            'views' => $article->views_count,
            'downloads' => $article->downloads_count,
            'pdf' => $pdf instanceof ArticleFile ? [
                'size' => $pdf->size,
                'viewUrl' => route('articles.pdf', (string) $article->slug),
                'downloadUrl' => route('articles.pdf', [(string) $article->slug, 'download' => 1]),
            ] : null,
        ];
    }

    /**
     * Yo'nalishlar va nashr etilgan maqolalar soni (filtr uchun).
     *
     * @return array<int, array{slug: string, name: string, count: int}>
     */
    public function subjects(): array
    {
        // Bitta so'rovda filtr va statistika ikkalasi uchun ishlatiladi
        return $this->subjects ??= Subject::query()
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderByDesc('articles_count')
            ->get()
            ->filter(fn (Subject $s): bool => (int) $s->getAttribute('articles_count') > 0)
            ->map(fn (Subject $s): array => [
                'slug' => $s->slug,
                'name' => $s->name,
                'count' => (int) $s->getAttribute('articles_count'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function years(): array
    {
        // Barcha sanalarni emas, faqat noyob yillarni bazadan olamiz
        $year = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y', published_at)",
            'pgsql' => 'extract(year from published_at)',
            default => 'year(published_at)',
        };

        return Article::query()
            ->published()
            ->selectRaw($year.' as y')
            ->distinct()
            ->pluck('y')
            ->map(fn (mixed $y): int => (int) $y)
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{slug: string, label: string}>
     */
    public function issues(): array
    {
        return JournalIssue::query()
            ->published()
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->get()
            ->map(fn (JournalIssue $i): array => ['slug' => $i->slug, 'label' => $i->label])
            ->all();
    }

    /**
     * Eng ko'p uchraydigan kalit so'zlar (joriy tilda, 1 soat keshlanadi).
     *
     * @return array<int, string>
     */
    public function popularKeywords(int $limit = 12): array
    {
        $locale = app()->getLocale();

        /** @var array<int, string> $keywords */
        $keywords = Cache::remember("catalog.keywords.{$locale}", 3600, function () use ($locale, $limit): array {
            $counts = [];

            Article::query()->published()->select(['id', 'keywords'])->chunkById(200, function ($articles) use (&$counts, $locale): void {
                foreach ($articles as $article) {
                    /** @var Article $article */
                    $words = $article->getTranslation('keywords', $locale, true);

                    foreach (is_array($words) ? $words : [] as $word) {
                        if (is_string($word) && $word !== '') {
                            $key = mb_strtolower(trim($word));
                            $counts[$key] = ($counts[$key] ?? 0) + 1;
                        }
                    }
                }
            });

            arsort($counts);

            return array_map('strval', array_slice(array_keys($counts), 0, $limit));
        });

        return $keywords;
    }

    /**
     * @return array{articles: int, issues: int, authors: int, subjects: int}
     */
    public function stats(): array
    {
        $published = Article::query()->published();

        return [
            'articles' => (clone $published)->count(),
            'issues' => JournalIssue::query()->where('status', IssueStatus::Published->value)->count(),
            'authors' => DB::query()
                ->fromSub(
                    DB::table('article_authors')
                        ->whereIn('article_id', (clone $published)->select('id'))
                        ->select(['last_name', 'first_name'])
                        ->distinct(),
                    'unique_authors',
                )
                ->count(),
            'subjects' => count($this->subjects()),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestIssue(): ?array
    {
        $issue = JournalIssue::query()->published()->withCount('articles')->latest('published_at')->first();

        return $issue !== null ? IssueCardResource::make($issue)->resolve() : null;
    }

    /**
     * So'nggi e'lon va yangiliklar (yon panel).
     *
     * @return array<int, array{title: string, url: string, date: string|null}>
     */
    public function announcements(int $limit = 4): array
    {
        return Post::query()
            ->published()
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Post $post): array => [
                'title' => $post->title,
                'url' => route('news.show', $post->slug),
                'date' => $post->published_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Ism bo'yicha qidiruv: har bir so'z familiya yoki ism boshiga mos kelishi kerak
     * ("karimov a" → familiya "karimov…", ism "a…"). SQL MySQL va SQLite da bir xil ishlaydi.
     *
     * @param  Builder<ArticleAuthor>  $query
     */
    private function authorMatch(Builder $query, string $name): void
    {
        $tokens = array_values(array_filter(preg_split('/[\s.,]+/u', mb_strtolower($name)) ?: []));

        foreach ($tokens as $token) {
            $query->where(fn (Builder $w) => $w
                ->whereRaw('lower(last_name) like ?', [$token.'%'])
                ->orWhereRaw('lower(first_name) like ?', [$token.'%'])
                ->orWhereRaw('lower(middle_name) like ?', [$token.'%']));
        }
    }
}

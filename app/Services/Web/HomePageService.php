<?php

namespace App\Services\Web;

use App\Enums\PartnerType;
use App\Enums\PostType;
use App\Enums\RoleName;
use App\Http\Resources\Web\ArticleCardResource;
use App\Http\Resources\Web\BannerResource;
use App\Http\Resources\Web\EventResource;
use App\Http\Resources\Web\IssueCardResource;
use App\Http\Resources\Web\PartnerResource;
use App\Http\Resources\Web\PostResource;
use App\Http\Resources\Web\SubjectResource;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Bosh sahifa ma'lumotlari (ikkala dizayn varianti uchun umumiy).
 *
 * Har bir blok alohida metod — Inertia'ga closure sifatida beriladi,
 * shuning uchun partial reload'da faqat kerakli blok hisoblanadi.
 */
class HomePageService
{
    /** Bosh sahifadagi "So'nggi maqolalar" soni (klassik dizayn 6 ta, zamonaviy 4 ta ko'rsatadi) */
    public const LATEST_ARTICLES = 6;

    /**
     * Raqamli ko'rsatkichlar (hero ostidagi qator va "Jurnal statistikasi").
     *
     * @return array{articles: int, authors: int, issues: int, indexes: int, subjects: int}
     */
    public function stats(): array
    {
        return [
            'articles' => Article::query()->published()->count(),
            'authors' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Author->value))
                ->count(),
            'issues' => JournalIssue::query()->published()->count(),
            'indexes' => Partner::query()->active()->ofType(PartnerType::Indexing)->count(),
            'subjects' => Subject::query()->active()->whereNull('parent_id')->count(),
        ];
    }

    /**
     * Eng so'nggi chop etilgan son (mundarijadagi yo'nalishlar bilan).
     *
     * @return array<string, mixed>|null
     */
    public function latestIssue(): ?array
    {
        $issue = JournalIssue::query()
            ->published()
            ->withCount('articles')
            ->withMax('articles as pages_total', 'issue_articles.page_to')
            ->latest('published_at')
            ->first();

        if ($issue === null) {
            return null;
        }

        $subjects = Subject::query()
            ->whereHas('articles.issues', fn ($q) => $q->whereKey($issue->id))
            ->active()
            ->limit(4)
            ->get()
            ->map(fn (Subject $subject): string => $subject->name)
            ->all();

        return [
            ...IssueCardResource::make($issue)->resolve(),
            'subjects' => $subjects,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestArticles(int $limit = self::LATEST_ARTICLES): array
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
     * Yuqori darajadagi yo'nalishlar va ulardagi nashr etilgan maqolalar soni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function subjects(): array
    {
        $subjects = Subject::query()
            ->active()
            ->whereNull('parent_id')
            ->withPublishedArticlesCount()
            ->get();

        return SubjectResource::collection($subjects)->resolve();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function posts(PostType $type, int $limit = 4): array
    {
        $posts = Post::query()->ofType($type)->published()->limit($limit)->get();

        return PostResource::collection($posts)->resolve();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingEvents(int $limit = 3): array
    {
        $events = Event::query()->published()->upcoming()->limit($limit)->get();

        return EventResource::collection($events)->resolve();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function partners(PartnerType $type): array
    {
        $partners = Partner::query()->active()->ofType($type)->get();

        return PartnerResource::collection($partners)->resolve();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        return BannerResource::collection(Banner::query()->visible()->get())->resolve();
    }

    /**
     * Joriy yilda oyma-oy nashr etilgan maqolalar ("Maqolalar dinamikasi").
     *
     * @return array{year: int, months: array<int, int>}
     */
    public function monthlyArticles(?int $year = null): array
    {
        $year ??= (int) now()->year;

        $counts = Article::query()
            ->published()
            ->whereYear('published_at', $year)
            ->pluck('published_at')
            ->countBy(fn (mixed $date): int => Carbon::parse($date)->month);

        return [
            'year' => $year,
            'months' => array_map(
                fn (int $month): int => (int) $counts->get($month, 0),
                range(1, 12),
            ),
        ];
    }
}

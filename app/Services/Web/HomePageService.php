<?php

namespace App\Services\Web;

use App\Enums\PostType;
use App\Http\Resources\Web\ArticleCardResource;
use App\Http\Resources\Web\BannerResource;
use App\Http\Resources\Web\EventResource;
use App\Http\Resources\Web\IssueCardResource;
use App\Http\Resources\Web\PostResource;
use App\Http\Resources\Web\RecommendedBookResource;
use App\Http\Resources\Web\SubjectResource;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Partner;
use App\Models\Post;
use App\Models\RecommendedBook;
use App\Models\Subject;
use App\Support\MediaUrl;
use App\Support\Translations;
use Illuminate\Support\Facades\Route;

/**
 * Bosh sahifa ma'lumotlari.
 *
 * Har bir blok alohida metod — Inertia'ga closure sifatida beriladi,
 * shuning uchun partial reload'da faqat kerakli blok hisoblanadi.
 */
class HomePageService
{
    /** Bosh sahifadagi "So'nggi maqolalar" soni */
    public const LATEST_ARTICLES = 6;

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
            ->orderByDesc('year')
            ->orderByDesc('number')
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

        // Muqova son sahifasidagi bilan bir xil: yuklangan muqova, bo'lmasa jurnalning umumiy muqovasi
        $card = IssueCardResource::make($issue)->resolve();

        return [
            ...$card,
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
     * "Tavsiya etilgan kitoblar" (o'ng ustun).
     *
     * @return array<int, array<string, mixed>>
     */
    public function recommendedBooks(int $limit = 3): array
    {
        $books = RecommendedBook::query()->active()->limit($limit)->get();

        return RecommendedBookResource::collection($books)->resolve();
    }

    /**
     * Hamkorlar va indekslash bazalari (bosh sahifa pastidagi logolar qatori).
     *
     * @return array<int, array{id: int, type: string, name: string, subtitle: string|null, url: string|null, logoUrl: string|null}>
     */
    public function partners(int $limit = 24): array
    {
        return Partner::query()
            ->active()
            ->limit($limit)
            ->get()
            ->map(fn (Partner $p): array => [
                'id' => $p->id,
                'type' => $p->type->value,
                'name' => $p->name,
                'subtitle' => $p->subtitle ?: null,
                'url' => $p->url,
                'logoUrl' => MediaUrl::from($p->logo_path),
            ])
            ->all();
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
     * Slayder: admin paneldagi faol bannerlar, ular bo'lmasa —
     * config('journal.hero_slides') dagi standart slaydlar.
     * Standart slayd rasmi public/ da hali yo'q bo'lsa, imageUrl = null
     * (frontend brend fonini ko'rsatadi).
     *
     * @return array<int, array<string, mixed>>
     */
    public function heroSlides(): array
    {
        $banners = Banner::query()->visible()->get();

        if ($banners->isNotEmpty()) {
            return BannerResource::collection($banners)->resolve();
        }

        /** @var array<int, array{title: string, subtitle?: string|null, image?: string|null, button_text?: string|null, route?: string|null}> $defaults */
        $defaults = (array) config('journal.hero_slides', []);

        return array_map(fn (array $slide, int $index): array => [
            'key' => "default-{$index}",
            'title' => Translations::line($slide['title']),
            'subtitle' => Translations::line($slide['subtitle'] ?? null),
            'imageUrl' => isset($slide['image']) && is_file(public_path($slide['image']))
                ? asset($slide['image'])
                : null,
            'linkUrl' => isset($slide['route']) && Route::has($slide['route'])
                ? route($slide['route'])
                : null,
            'buttonText' => Translations::line($slide['button_text'] ?? null),
        ], $defaults, array_keys($defaults));
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Enums\PartnerType;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Services\Web\HomePageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bosh sahifa. Ikki dizayn varianti bor (TZ bo'yicha tanlov bosqichi):
 *   modern  — home_2.png  (pages/web/home/Modern.vue)
 *   classic — home.png    (pages/web/home/Classic.vue)
 * Standart variant config('journal.home_variant'), vaqtincha ?variant= bilan almashtiriladi.
 */
class HomeController extends Controller
{
    public const VARIANTS = ['modern', 'classic'];

    public function __invoke(Request $request, HomePageService $home): Response
    {
        $variant = $request->query('variant');

        if (! is_string($variant) || ! in_array($variant, self::VARIANTS, true)) {
            $variant = in_array(config('journal.home_variant'), self::VARIANTS, true)
                ? (string) config('journal.home_variant')
                : 'modern';
        }

        return Inertia::render('web/home/'.ucfirst($variant), [
            'variant' => $variant,
            'stats' => fn () => $home->stats(),
            'latestIssue' => fn () => $home->latestIssue(),
            'latestArticles' => fn () => $home->latestArticles(),
            'subjects' => fn () => $home->subjects(),
            'announcements' => fn () => $home->posts(PostType::Announcement, 3),
            'news' => fn () => $home->posts(PostType::News, 4),
            'events' => fn () => $home->upcomingEvents(),
            'partners' => fn () => $home->partners(PartnerType::Partner),
            'indexing' => fn () => $home->partners(PartnerType::Indexing),
            'banners' => fn () => $variant === 'classic' ? $home->banners() : [],
            'monthlyArticles' => fn () => $variant === 'modern' ? $home->monthlyArticles() : null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Services\Web\HomePageService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bosh sahifa (pages/web/Home.vue).
 * Har bir blok closure — partial reload'da faqat so'ralgani hisoblanadi.
 */
class HomeController extends Controller
{
    public function __invoke(HomePageService $home): Response
    {
        return Inertia::render('web/Home', [
            'heroSlides' => fn () => $home->heroSlides(),
            'latestIssue' => fn () => $home->latestIssue(),
            'latestArticles' => fn () => $home->latestArticles(),
            'subjects' => fn () => $home->subjects(),
            'news' => fn () => $home->posts(PostType::News, 6),
            'events' => fn () => $home->upcomingEvents(4),
            'books' => fn () => $home->recommendedBooks(),
        ]);
    }
}

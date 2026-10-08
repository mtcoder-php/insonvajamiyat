<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Web\ArticleDailyStats;
use App\Services\Web\ArticlePageService;
use App\Support\MediaUrl;
use App\Support\Seo\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Nashr etilgan maqola sahifasi (web maqola view page.png).
 */
class ArticleController extends Controller
{
    /** Bir tashrifchi bir maqolani qayta ko'rganda hisob shu vaqt ichida oshmaydi (soniya) */
    public const VIEW_WINDOW = 6 * 3600;

    public function show(Request $request, Article $article, ArticlePageService $page, ArticleDailyStats $stats): Response
    {
        abort_unless($article->isPublished(), 404);

        $this->countView($request, $article, $stats);
        app(SeoMeta::class)->forArticle($article);

        return Inertia::render('web/articles/Show', [
            'article' => $page->show($article),
            'links' => [
                'guidelines' => route('guidelines'),
                'template' => MediaUrl::publicAsset(config('journal.article_template')),
                'about' => route('about'),
            ],
        ]);
    }

    /** Ko'rishlar soni: sessiya bo'yicha VIEW_WINDOW ichida bir marta */
    private function countView(Request $request, Article $article, ArticleDailyStats $stats): void
    {
        $key = 'viewed_articles.'.$article->id;
        $last = $request->session()->get($key);

        if (is_int($last) && $last > now()->getTimestamp() - self::VIEW_WINDOW) {
            return;
        }

        $request->session()->put($key, now()->getTimestamp());
        $article->bumpCounter('views_count');
        $stats->record($article, ArticleDailyStats::VIEWS);
    }
}

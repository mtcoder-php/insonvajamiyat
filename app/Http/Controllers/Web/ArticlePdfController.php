<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Articles\ArticleFileService;
use App\Services\Web\ArticleDailyStats;
use App\Services\Web\ArticlePageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nashr etilgan maqolaning yakuniy PDF i: brauzerda ko'rish yoki ?download=1 — yuklab olish.
 * Yuklab olishlar soni sessiya bo'yicha kuniga bir marta oshadi.
 */
class ArticlePdfController extends Controller
{
    public function __invoke(Request $request, Article $article, ArticlePageService $page, ArticleFileService $files, ArticleDailyStats $stats): StreamedResponse
    {
        abort_unless($article->isPublished(), 404);

        $pdf = $page->finalPdf($article);
        abort_if($pdf === null, 404);

        if (! $request->boolean('download')) {
            return $files->inline($pdf);
        }

        $key = 'downloaded_articles.'.$article->id;

        if ($request->session()->get($key) !== now()->toDateString()) {
            $request->session()->put($key, now()->toDateString());
            $article->bumpCounter('downloads_count');
            $stats->record($article, ArticleDailyStats::DOWNLOADS);
        }

        return $files->download($pdf);
    }
}

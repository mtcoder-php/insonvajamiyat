<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Services\Articles\ArticleFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Maqola faylini yuklab olish (maxfiy disk, faqat ruxsat bilan); ?inline=1 — PDF ni ko'rish.
 */
class ArticleFileController extends Controller
{
    public function __invoke(Request $request, Article $article, ArticleFile $file, ArticleFileService $files): StreamedResponse
    {
        Gate::authorize('downloadFiles', $article);
        abort_unless($file->article_id === $article->id, 404);

        // ?inline=1 — PDF ni brauzerda ko'rsatish (korrektura oynasi)
        return $request->boolean('inline') ? $files->inline($file) : $files->download($file);
    }
}

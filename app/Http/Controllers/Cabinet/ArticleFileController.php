<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Services\Articles\ArticleFileService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Maqola faylini yuklab olish (maxfiy disk, faqat ruxsat bilan).
 */
class ArticleFileController extends Controller
{
    public function __invoke(Article $article, ArticleFile $file, ArticleFileService $files): StreamedResponse
    {
        Gate::authorize('downloadFiles', $article);
        abort_unless($file->article_id === $article->id, 404);

        return $files->download($file);
    }
}

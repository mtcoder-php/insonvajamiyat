<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\ArticleCardResource;
use App\Models\Article;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Nashr etilgan maqola sahifasi (to'liq dizayn — "web maqola view page.png" bosqichida).
 */
class ArticleController extends Controller
{
    public function show(Article $article): Response
    {
        abort_unless($article->isPublished(), 404);

        $article->load(['authors', 'subject']);

        return Inertia::render('web/articles/Show', [
            'article' => [
                ...ArticleCardResource::make($article)->resolve(),
                'abstract' => $article->abstract,
            ],
        ]);
    }
}

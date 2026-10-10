<?php

namespace App\Http\Controllers\Web;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\PostResource;
use App\Models\Post;
use App\Support\Seo\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Yangiliklar va e'lonlar: ro'yxat (/news) va to'liq matn (/news/{slug}).
 */
class NewsController extends Controller
{
    public const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $type = PostType::tryFrom($request->string('type')->toString());

        $query = Post::query()->published();

        if ($type !== null) {
            $query->ofType($type);
        }

        $posts = $query->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('web/news/Index', [
            'hero' => StaticPageController::hero('news'),
            'posts' => PostResource::collection($posts),
            'type' => $type?->value,
        ]);
    }

    public function show(Post $post): Response
    {
        abort_unless(
            $post->is_published && $post->published_at !== null && $post->published_at->isPast(),
            404,
        );

        app(SeoMeta::class)->forPost($post);

        $others = Post::query()
            ->published()
            ->whereKeyNot($post->id)
            ->limit(5)
            ->get();

        return Inertia::render('web/news/Show', [
            'hero' => StaticPageController::hero('news'),
            'post' => [
                ...PostResource::make($post)->resolve(),
                'body' => $post->body,
            ],
            'others' => PostResource::collection($others)->resolve(),
        ]);
    }
}

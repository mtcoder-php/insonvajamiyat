<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Post;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml — qidiruv tizimlari uchun ommaviy sahifalar ro'yxati (1 soat keshlanadi).
 * Chop etilgan maqolalar, sonlar, yangiliklar va tadbirlar avtomatik qo'shiladi.
 */
class SitemapController extends Controller
{
    public const CACHE_KEY = 'seo:sitemap:v1';

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addHour(), fn (): string => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function build(): string
    {
        /** @var list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}> $urls */
        $urls = [];
        $add = function (string $loc, ?CarbonInterface $lastmod, string $changefreq, string $priority) use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod?->toAtomString(),
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $add(route('home'), null, 'daily', '1.0');
        $add(route('articles.index'), null, 'daily', '0.9');
        $add(route('issues.index'), null, 'weekly', '0.8');
        $add(route('news.index'), null, 'daily', '0.6');
        $add(route('events.index'), null, 'weekly', '0.5');
        $add(route('about'), null, 'monthly', '0.5');
        $add(route('guidelines'), null, 'monthly', '0.6');
        $add(route('contact'), null, 'yearly', '0.3');

        JournalIssue::query()->published()->orderByDesc('published_at')
            ->get(['id', 'slug', 'published_at', 'updated_at'])
            ->each(fn (JournalIssue $issue) => $add(route('issues.show', $issue), $issue->updated_at ?? $issue->published_at, 'monthly', '0.7'));

        // lazyById o'zi id bo'yicha sahifalaydi — boshqa tartiblash (orderBy) qo'shilsa,
        // 500 tadan keyin maqolalar tushib qoladi yoki takrorlanadi
        Article::query()->published()
            ->select(['id', 'slug', 'published_at', 'updated_at'])
            ->lazyById(500)
            ->each(fn (Article $article) => $add(route('articles.show', $article), $article->updated_at ?? $article->published_at, 'monthly', '0.8'));

        Post::query()->published()
            ->get(['id', 'slug', 'published_at', 'updated_at'])
            ->each(fn (Post $post) => $add(route('news.show', $post->slug), $post->updated_at ?? $post->published_at, 'monthly', '0.4'));

        Event::query()->published()->orderByDesc('starts_at')
            ->get(['id', 'slug', 'starts_at', 'updated_at'])
            ->each(fn (Event $event) => $add(route('events.show', $event->slug), $event->updated_at, 'monthly', '0.4'));

        return view('seo.sitemap', ['urls' => $urls])->render();
    }
}

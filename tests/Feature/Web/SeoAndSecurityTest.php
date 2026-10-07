<?php

namespace Tests\Feature\Web;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Web\SitemapController;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Event;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Ishga tushirishga tayyorlik: SEO teglari (OG, Google Scholar), sitemap.xml, robots.txt
 * va xavfsizlik sarlavhalari.
 */
class SeoAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
    }

    public function test_article_page_has_open_graph_and_scholar_tags(): void
    {
        config(['journal.issn' => '2181-1234', 'journal.name' => 'Inson va Jamiyat']);

        $issue = JournalIssue::factory()->published(now()->subMonth())->createOne(['year' => 2026, 'number' => 2, 'slug' => '2026-2']);
        $article = Article::factory()->published(now()->setDate(2026, 5, 14))->createOne([
            'title' => ['uz' => 'Raqamli jamiyatda <ijtimoiy> kapital'],
            'abstract' => ['uz' => "<p>Maqolada   raqamli\n jamiyat tahlil qilinadi.</p>"],
            'keywords' => ['uz' => ['jamiyat', 'kapital']],
            'doi' => '10.5281/zenodo.123456',
        ]);
        ArticleAuthor::factory()->for($article)->createOne([
            'last_name' => 'Karimov',
            'first_name' => 'Aziz',
            'middle_name' => null,
            'organization' => 'Toshkent davlat universiteti',
            'orcid' => '0000-0002-1825-0097',
            'sort_order' => 1,
        ]);
        IssueArticle::query()->create(['journal_issue_id' => $issue->id, 'article_id' => $article->id, 'position' => 1, 'page_from' => 5, 'page_to' => 12]);

        $this->production();

        $response = $this->get(route('articles.show', $article))->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('<title>Raqamli jamiyatda &lt;ijtimoiy&gt; kapital — Inson va Jamiyat</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Maqolada raqamli jamiyat tahlil qilinadi." data-inertia="description">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('articles.show', $article).'">', $html);
        $this->assertStringNotContainsString('name="robots"', $html);
        $this->assertStringContainsString('<meta name="citation_author" content="Karimov, Aziz">', $html);
        $this->assertStringContainsString('<meta name="citation_author_institution" content="Toshkent davlat universiteti">', $html);
        $this->assertStringContainsString('<meta name="citation_publication_date" content="2026/05/14">', $html);
        $this->assertStringContainsString('<meta name="citation_issn" content="2181-1234">', $html);
        $this->assertStringContainsString('<meta name="citation_issue" content="2">', $html);
        $this->assertStringContainsString('<meta name="citation_firstpage" content="5">', $html);
        $this->assertStringContainsString('<meta name="citation_lastpage" content="12">', $html);
        $this->assertStringContainsString('<meta name="citation_doi" content="10.5281/zenodo.123456">', $html);
        $this->assertStringContainsString('<meta name="citation_keywords" content="jamiyat; kapital">', $html);
        $this->assertStringContainsString('"@type":"ScholarlyArticle"', $html);
        // JSON-LD ichida "<" xavfsiz kodlangan — skript tegidan chiqib ketmaydi
        $this->assertStringContainsString('"name":"Raqamli jamiyatda \\u003Cijtimoiy\\u003E kapital"', $html);
    }

    public function test_private_pages_and_non_production_are_noindex(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->production();

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>Jurnal haqida — ', false)
            ->assertDontSee('name="robots"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_sitemap_lists_only_public_content(): void
    {
        $article = Article::factory()->published()->createOne();
        $draft = Article::factory()->status(ArticleStatus::UnderReview)->createOne(['slug' => 'yashirin-maqola']);
        $issue = JournalIssue::factory()->published()->createOne(['slug' => '2026-1']);
        JournalIssue::factory()->createOne(['slug' => '2026-9']);
        $post = Post::factory()->createOne();
        $hiddenPost = Post::factory()->draft()->createOne();
        $event = Event::factory()->createOne(['is_published' => true]);

        Cache::forget(SitemapController::CACHE_KEY);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = (string) $response->getContent();
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('<loc>'.route('articles.show', $article).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('issues.show', $issue).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('news.show', $post->slug).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('events.show', $event->slug).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('guidelines').'</loc>', $xml);
        $this->assertStringNotContainsString((string) $draft->slug, $xml);
        $this->assertStringNotContainsString('2026-9', $xml);
        $this->assertStringNotContainsString($hiddenPost->slug, $xml);
    }

    public function test_robots_txt_depends_on_environment(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText('Disallow: /');

        $this->production();

        $body = (string) $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString("Disallow: /admin\n", $body);
        $this->assertStringContainsString("Disallow: /cabinet\n", $body);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
        $this->assertStringNotContainsString("Disallow: /\n", $body);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->production();

        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}

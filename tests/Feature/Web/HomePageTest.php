<?php

namespace Tests\Feature\Web;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Banner;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Subject;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Bosh sahifa va ommaviy maqola/son sahifalari.
 */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_empty_database()
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/Home')
                ->where('latestIssue', null)
                ->has('latestArticles', 0)
                ->has('announcements', 0)
                ->where('stats.articles', 0)
            );
    }

    public function test_public_pages_are_rendered_in_light_theme_only()
    {
        $this->withCookie('appearance', 'dark')
            ->get(route('home'))
            ->assertSee('data-theme="light-only"', false)
            ->assertDontSee('class="dark"', false);
    }

    public function test_hero_uses_default_slides_when_there_are_no_banners()
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('heroSlides', count((array) config('journal.hero_slides')))
                ->where('heroSlides.0.title', config('journal.hero_slides.0.title'))
                ->where('heroSlides.0.linkUrl', route('articles.index'))
            );
    }

    public function test_active_banners_replace_default_slides()
    {
        Banner::factory()->create(['title' => ['uz' => 'Birinchi banner'], 'sort_order' => 1]);
        Banner::factory()->create(['title' => ['uz' => 'Muddati tugagan'], 'ends_at' => now()->subDay()]);
        Banner::factory()->create(['title' => ['uz' => 'Nofaol'], 'is_active' => false]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('heroSlides', 1)
                ->where('heroSlides.0.title', 'Birinchi banner')
                ->whereNot('heroSlides.0.imageUrl', null)
            );
    }

    public function test_home_shows_only_published_content()
    {
        $subject = Subject::factory()->create(['name' => ['uz' => 'Tarix']]);

        $published = Article::factory()->published(now()->subDay())->for($subject)->create();
        ArticleAuthor::factory()->for($published)->create(['first_name' => 'Anvar', 'last_name' => 'Karimov']);
        Article::factory()->for($subject)->status(ArticleStatus::InReview)->create();

        $issue = JournalIssue::factory()->published()->create(['number' => 3, 'year' => 2026]);
        $issue->articles()->attach($published->id, ['position' => 1, 'page_from' => 5, 'page_to' => 18]);
        JournalIssue::factory()->create(); // qoralama son

        Post::factory()->announcement()->create(['title' => ['uz' => "Ko'rinadigan e'lon"]]);
        Post::factory()->announcement()->draft()->create();
        Post::factory()->announcement()->create(['published_at' => now()->addDay()]);

        Event::factory()->create();
        Event::factory()->past()->create();

        Partner::factory()->indexing()->create();
        Partner::factory()->create(['is_active' => false]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.articles', 1)
                ->where('stats.issues', 1)
                ->where('stats.indexes', 1)
                ->has('latestArticles', 1)
                ->where('latestArticles.0.title', $published->title)
                ->where('latestArticles.0.authors', 'A. Karimov')
                ->where('latestArticles.0.subject.name', 'Tarix')
                ->where('latestArticles.0.url', route('articles.show', $published->slug))
                ->where('latestIssue.label', '№3 (2026)')
                ->where('latestIssue.articlesCount', 1)
                ->where('latestIssue.pagesTotal', 18)
                ->where('latestIssue.subjects', ['Tarix'])
                ->has('announcements', 1)
                ->where('announcements.0.title', "Ko'rinadigan e'lon")
                ->has('events', 1)
                ->has('indexing', 1)
                ->has('partners', 0)
                ->where('subjects.0.articlesCount', 1)
            );
    }

    public function test_published_article_page_is_public_and_drafts_are_hidden()
    {
        $article = Article::factory()->published()->create();

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/articles/Show')
                ->where('article.id', $article->id)
            );

        $draft = Article::factory()->create(['slug' => 'qoralama-maqola']);

        $this->get(route('articles.show', $draft->slug))->assertNotFound();
    }

    public function test_issue_page_lists_only_published_articles()
    {
        $issue = JournalIssue::factory()->published()->create();
        $published = Article::factory()->published()->create();
        $accepted = Article::factory()->status(ArticleStatus::Accepted)->create();
        $issue->articles()->attach($published->id, ['position' => 1]);
        $issue->articles()->attach($accepted->id, ['position' => 2]);

        $this->get(route('issues.show', $issue->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/issues/Show')
                ->has('articles', 1)
                ->where('articles.0.id', $published->id)
            );

        $draftIssue = JournalIssue::factory()->create();

        $this->get(route('issues.show', $draftIssue->slug))->assertNotFound();
    }

    public function test_subject_seeder_is_idempotent()
    {
        $this->seed(SubjectSeeder::class);
        $this->seed(SubjectSeeder::class);

        $this->assertSame(count(SubjectSeeder::SUBJECTS), Subject::count());
        $this->assertSame('History', Subject::where('slug', 'history')->sole()->getTranslation('name', 'en'));
    }

    public function test_demo_content_seeder_fills_home_page()
    {
        // Rollar TestCase'da seed qilingan (muallif hisobi uchun kerak)
        $this->seed(DemoContentSeeder::class);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.issues', 3)
                ->has('latestArticles', 6)
                ->has('announcements', 3)
                ->has('events', 3)
                ->has('indexing', 4)
                ->whereNot('latestIssue', null)
            );
    }
}

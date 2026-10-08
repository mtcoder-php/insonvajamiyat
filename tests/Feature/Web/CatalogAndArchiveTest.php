<?php

namespace Tests\Feature\Web;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Saytdagi maqolalar katalogi va jurnal sonlari arxivi.
 */
class CatalogAndArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function article(array $attributes = [], ?string $lastName = null, ?Subject $subject = null): Article
    {
        $article = Article::factory()->published()->createOne([
            'subject_id' => ($subject ?? Subject::factory()->createOne())->id,
            ...$attributes,
        ]);

        ArticleAuthor::factory()->for($article)->createOne([
            'last_name' => $lastName ?? 'Aliyev',
            'first_name' => 'Bobur',
            'sort_order' => 1,
        ]);

        return $article;
    }

    public function test_catalog_lists_only_published_articles_with_sidebar(): void
    {
        $this->article(['title' => ['uz' => 'Amir Temur davlatchiligi']]);
        $this->article(['title' => ['uz' => 'Raqamli iqtisodiyot']]);
        Article::factory()->status(ArticleStatus::InReview)->createOne();

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/articles/Index')
                ->where('articles.meta.total', 2)
                ->has('articles.data.0.url')
                ->where('filters.sort', 'newest')
                ->has('facets.subjects', 2)
                ->where('sidebar.stats.articles', 2)
                ->where('sidebar.stats.authors', 1)
            );
    }

    public function test_catalog_search_and_filters(): void
    {
        $history = Subject::factory()->createOne(['slug' => 'history']);
        $temur = $this->article([
            'title' => ['uz' => 'Amir Temur davlatchiligi'],
            'keywords' => ['uz' => ['Temuriylar', 'davlat']],
            'views_count' => 10,
            'published_at' => now()->setDate(2025, 5, 1),
        ], 'Karimov', $history);
        $digital = $this->article([
            'title' => ['uz' => 'Raqamli iqtisodiyot'],
            'views_count' => 500,
            'published_at' => now()->setDate(2026, 3, 1),
        ], 'Saidova');

        $ids = fn (array $query): array => collect(
            $this->get(route('articles.index', $query))->viewData('page')['props']['articles']['data'],
        )->pluck('id')->all();

        $this->assertSame([$temur->id], $ids(['q' => 'temur']));
        $this->assertSame([$temur->id], $ids(['author' => 'karimov b']));
        $this->assertSame([$temur->id], $ids(['subjects' => ['history']]));
        // Bitta yo'nalish havolasi (bosh sahifa, "Jurnal haqida"): ?subject=history
        $this->assertSame([$temur->id], $ids(['subject' => 'history']));
        $this->assertSame([$temur->id], $ids(['keyword' => 'temuriylar']));
        $this->assertSame([$digital->id], $ids(['year' => 2026]));
        $this->assertSame([$digital->id, $temur->id], $ids(['sort' => 'popular']));
        $this->assertSame([$temur->id, $digital->id], $ids(['sort' => 'oldest']));
        $this->assertSame([], $ids(['q' => 'mavjud-emas-xyz']));
    }

    public function test_search_text_is_indexed_on_save(): void
    {
        $article = $this->article([
            'title' => ['uz' => 'Milliy Qadriyatlar', 'en' => 'National values'],
            'doi' => '10.5281/test.77',
        ]);

        $this->assertStringContainsString('milliy qadriyatlar', (string) $article->search_text);
        $this->assertStringContainsString('national values', (string) $article->search_text);
        $this->assertStringContainsString('10.5281/test.77', (string) $article->search_text);

        Article::query()->whereKey($article->id)->update(['search_text' => null]);
        $this->artisan('app:reindex-articles')->assertSuccessful();
        $this->assertNotNull($article->refresh()->search_text);
    }

    public function test_issue_archive_groups_published_issues_by_year(): void
    {
        $old = JournalIssue::factory()->published(now()->subYear())->createOne(['year' => 2025, 'number' => 4, 'slug' => '2025-4']);
        $new = JournalIssue::factory()->published(now()->subMonth())->createOne(['year' => 2026, 'number' => 1, 'slug' => '2026-1']);
        JournalIssue::factory()->createOne(['year' => 2026, 'number' => 2, 'slug' => '2026-2']); // qoralama

        $article = $this->article(['title' => ['uz' => 'Sondagi maqola']]);
        IssueArticle::query()->create(['journal_issue_id' => $new->id, 'article_id' => $article->id, 'position' => 1, 'page_from' => 5, 'page_to' => 12]);

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/issues/Index')
                ->has('tree', 2)
                ->where('tree.0.year', 2026)
                ->where('tree.0.count', 1)
                ->has('latest', 2)
                ->where('latest.0.slug', '2026-1')
                ->where('latest.0.articlesCount', 1)
                ->where('latest.0.authorsCount', 1)
                ->where('filters.year', 2026)
                ->has('yearIssues', 1)
            );

        $this->get(route('issues.index', ['year' => 2025]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.year', 2025)
                ->where('yearIssues.0.slug', $old->slug)
            );

        $this->get(route('issues.show', $new->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sections.0.articles.0.pages', '5–12')
                ->where('issue.pagesTotal', 12)
                ->where('neighbours.prev.url', route('issues.show', $old->slug))
                ->where('neighbours.next', null)
            );
    }
}

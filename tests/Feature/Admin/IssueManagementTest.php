<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Jurnallar: son yaratish/tahrirlash, tarkib (qo'shish, tartib, rukn, sahifalar),
 * fayllar, mundarija va ruxsatlar.
 */
class IssueManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $layout;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
    }

    private function issue(array $attributes = []): JournalIssue
    {
        return JournalIssue::factory()->createOne([
            'year' => 2026,
            'number' => 4,
            'slug' => '2026-4',
            ...$attributes,
        ]);
    }

    private function article(ArticleStatus $status = ArticleStatus::InProduction, ?int $pages = null): Article
    {
        $article = Article::factory()->withAuthors()->status($status)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
        ]);
        $article->forceFill(['pages_count' => $pages, 'accepted_at' => now()->subWeek()])->save();

        return $article;
    }

    public function test_index_lists_issues_with_stats(): void
    {
        $this->issue();
        $this->article(ArticleStatus::Accepted);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/issues/Index')
                ->has('issues', 1)
                ->where('issues.0.slug', '2026-4')
                ->has('stats', 4)
                ->where('stats.3.value', 1)
                ->where('next.year', (int) now()->year)
            );
    }

    public function test_issue_is_created_and_validated(): void
    {
        $this->actingAs($this->layout)
            ->post(route('admin.issues.store'), [
                'year' => 2026,
                'volume' => 3,
                'number' => 5,
                'doi' => '10.5281/insonvajamiyat.2026.5',
                'title' => 'Maxsus son',
            ])
            ->assertRedirect(route('admin.issues.show', '2026-5'));

        $issue = JournalIssue::query()->where('slug', '2026-5')->firstOrFail();
        $this->assertSame(IssueStatus::Draft, $issue->status);
        $this->assertSame($this->layout->id, $issue->created_by);
        $this->assertSame('Maxsus son', $issue->getTranslation('title', app()->getLocale()));

        $this->actingAs($this->layout)
            ->post(route('admin.issues.store'), ['year' => 2026, 'number' => 5, 'doi' => 'noto-gri'])
            ->assertSessionHasErrors(['number', 'doi']);
    }

    public function test_slug_change_moves_files(): void
    {
        $issue = $this->issue();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.files.store', $issue->slug), [
                'type' => 'cover',
                'file' => UploadedFile::fake()->image('muqova.jpg', 600, 850),
            ])
            ->assertSessionHasNoErrors();

        $cover = (string) $issue->refresh()->cover_image_path;
        Storage::disk('public')->assertExists($cover);

        $this->actingAs($this->layout)
            ->put(route('admin.issues.update', $issue->slug), ['year' => 2026, 'number' => 6])
            ->assertRedirect(route('admin.issues.show', '2026-6'));

        $issue->refresh();
        $this->assertStringStartsWith('issues/2026-6/', (string) $issue->cover_image_path);
        Storage::disk('public')->assertExists((string) $issue->cover_image_path);
        Storage::disk('public')->assertMissing($cover);
    }

    public function test_articles_are_attached_reordered_and_paginated(): void
    {
        $issue = $this->issue();
        [$a, $b, $c] = [$this->article(pages: 8), $this->article(ArticleStatus::Accepted, 6), $this->article(pages: 10)];

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$a->id, $b->id, $c->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3], IssueArticle::query()->orderBy('position')->pluck('position')->all());

        $this->actingAs($this->layout)
            ->put(route('admin.issues.articles.reorder', $issue->slug), ['order' => [$c->id, $a->id, $b->id]])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.paginate', $issue->slug), ['start_page' => 5])
            ->assertSessionHasNoErrors();

        $pages = IssueArticle::query()->orderBy('position')->get()
            ->map(fn (IssueArticle $p): string => $p->article_id.':'.$p->pages())
            ->all();
        $this->assertSame(["{$c->id}:5–14", "{$a->id}:15–22", "{$b->id}:23–28"], $pages);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/issues/Show')
                ->has('issue.articles', 3)
                ->where('issue.articles.0.id', $c->id)
                ->where('issue.summary.withPages', 3)
                ->where('issue.summary.pages', 28)
            );

        // Noto'g'ri tartib ro'yxati
        $this->actingAs($this->layout)
            ->put(route('admin.issues.articles.reorder', $issue->slug), ['order' => [$a->id]])
            ->assertSessionHasErrors('order');
    }

    public function test_only_accepted_articles_can_be_attached_and_moved(): void
    {
        $issue = $this->issue();
        $other = $this->issue(['number' => 3, 'slug' => '2026-3']);
        $article = $this->article();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$this->article(ArticleStatus::InReview)->id]])
            ->assertSessionHasErrors('article_ids');

        // Boshqa sondan ko'chirish
        IssueArticle::query()->create(['journal_issue_id' => $other->id, 'article_id' => $article->id, 'position' => 1]);
        $article->forceFill(['chief_editor_approved_at' => now()])->save();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$article->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame($issue->id, $article->placement()->value('journal_issue_id'));
        $this->assertNull($article->refresh()->chief_editor_approved_at);
    }

    public function test_placement_is_updated_and_article_detached(): void
    {
        $issue = $this->issue();
        $article = $this->article();
        $second = $this->article();
        IssueArticle::query()->create(['journal_issue_id' => $issue->id, 'article_id' => $article->id, 'position' => 1]);
        IssueArticle::query()->create(['journal_issue_id' => $issue->id, 'article_id' => $second->id, 'position' => 2]);

        $this->actingAs($this->layout)
            ->put(route('admin.issues.articles.update', [$issue->slug, $article->uuid]), [
                'section' => 'Tarix',
                'page_from' => 10,
                'page_to' => 19,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $article->refresh()->pages_count);
        $this->assertSame([app()->getLocale() => 'Tarix'], $article->placement?->section);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.toc', $issue->slug))
            ->assertOk()
            ->assertSee('MUNDARIJA')
            ->assertSee('Tarix')
            ->assertSee('10–19');

        $this->actingAs($this->layout)
            ->delete(route('admin.issues.articles.destroy', [$issue->slug, $article->uuid]))
            ->assertSessionHasNoErrors();

        $this->assertNull($article->placement()->first());
        $this->assertSame(1, $second->placement()->value('position'));
    }

    public function test_published_article_placement_is_locked(): void
    {
        $issue = $this->issue();
        $article = $this->article(ArticleStatus::Published);
        IssueArticle::query()->create(['journal_issue_id' => $issue->id, 'article_id' => $article->id, 'position' => 1]);

        $this->actingAs($this->layout)
            ->delete(route('admin.issues.articles.destroy', [$issue->slug, $article->uuid]))
            ->assertSessionHasErrors('article');

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.paginate', $issue->slug), ['start_page' => 1])
            ->assertSessionHasErrors('start_page');
    }

    public function test_pdf_files_are_uploaded_and_removed(): void
    {
        $issue = $this->issue();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.files.store', $issue->slug), [
                'type' => 'pdf',
                'file' => UploadedFile::fake()->create('son.pdf', 2000, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $path = (string) $issue->refresh()->full_pdf_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->layout)
            ->post(route('admin.issues.files.store', $issue->slug), [
                'type' => 'toc',
                'file' => UploadedFile::fake()->create('mundarija.docx', 20),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->layout)
            ->delete(route('admin.issues.files.destroy', [$issue->slug, 'pdf']))
            ->assertSessionHasNoErrors();

        $this->assertNull($issue->refresh()->full_pdf_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_only_empty_draft_issue_can_be_deleted(): void
    {
        $issue = $this->issue();
        IssueArticle::query()->create(['journal_issue_id' => $issue->id, 'article_id' => $this->article()->id, 'position' => 1]);

        $this->actingAs($this->layout)
            ->delete(route('admin.issues.destroy', $issue->slug))
            ->assertSessionHasErrors('issue');

        $published = JournalIssue::factory()->published()->createOne();
        $this->actingAs($this->layout)
            ->delete(route('admin.issues.destroy', $published->slug))
            ->assertSessionHasErrors('issue');

        $empty = $this->issue(['number' => 7, 'slug' => '2026-7']);
        $this->actingAs($this->layout)
            ->delete(route('admin.issues.destroy', $empty->slug))
            ->assertRedirect(route('admin.issues.index'));

        $this->assertSoftDeleted($empty);
    }

    public function test_permissions(): void
    {
        $issue = $this->issue();

        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->get(route('admin.issues.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->author()->createOne())
            ->get(route('admin.issues.show', $issue->slug))
            ->assertForbidden();

        $this->actingAs(User::factory()->withRole(RoleName::ChiefEditor)->createOne())
            ->get(route('admin.issues.show', $issue->slug))
            ->assertOk();
    }
}

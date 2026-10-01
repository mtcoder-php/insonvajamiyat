<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Saytda chop etish: sonni chop etish, alohida maqola, maqola sahifasi, PDF va hisoblagichlar.
 */
class PublishingTest extends TestCase
{
    use RefreshDatabase;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->chief = User::factory()->withRole(RoleName::ChiefEditor)->createOne();
    }

    private function issue(IssueStatus $status = IssueStatus::Draft): JournalIssue
    {
        return JournalIssue::factory()->createOne([
            'year' => 2026,
            'number' => 4,
            'slug' => '2026-4',
            'status' => $status,
            'published_at' => $status === IssueStatus::Published ? now()->subDay() : null,
        ]);
    }

    /** Nashrga tayyor maqola: tasdiqlangan, yakuniy PDF, songa sahifalari bilan biriktirilgan */
    private function readyArticle(JournalIssue $issue, int $position = 1, bool $approved = true): Article
    {
        $article = Article::factory()->withAuthors(2)->status(ArticleStatus::InProduction)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'doi' => '10.5281/insonvajamiyat.2026.'.$position,
        ]);
        $article->forceFill([
            'accepted_at' => now()->subWeek(),
            'chief_editor_approved_at' => $approved ? now() : null,
            'chief_editor_approved_by' => $approved ? $this->chief->id : null,
        ])->save();

        Storage::disk('local')->put("articles/{$article->uuid}/final.pdf", '%PDF-1.4 test');
        $article->files()->create([
            'type' => ArticleFileType::FinalPdf,
            'disk' => 'local',
            'path' => "articles/{$article->uuid}/final.pdf",
            'original_name' => 'final.pdf',
            'mime_type' => 'application/pdf',
            'size' => 13,
        ]);

        IssueArticle::query()->create([
            'journal_issue_id' => $issue->id,
            'article_id' => $article->id,
            'position' => $position,
            'page_from' => $position * 10,
            'page_to' => $position * 10 + 7,
        ]);

        return $article;
    }

    public function test_issue_cannot_be_published_with_unready_articles(): void
    {
        $issue = $this->issue();
        $this->readyArticle($issue);
        $this->readyArticle($issue, 2, approved: false);

        $this->actingAs($this->chief)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('issue.can.publish', false)
                ->has('issue.problems', 1)
            );

        $this->actingAs($this->chief)
            ->post(route('admin.issues.publish', $issue->slug))
            ->assertSessionHasErrors('publish');

        $this->assertSame(IssueStatus::Draft, $issue->refresh()->status);
        $this->assertSame(0, Article::query()->where('status', ArticleStatus::Published->value)->count());
    }

    public function test_issue_is_published_with_articles(): void
    {
        Notification::fake();
        $issue = $this->issue();
        $first = $this->readyArticle($issue);
        $second = $this->readyArticle($issue, 2);

        $this->actingAs($this->chief)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page->where('issue.can.publish', true)->has('issue.problems', 0));

        $this->actingAs($this->chief)
            ->post(route('admin.issues.publish', $issue->slug))
            ->assertRedirect(route('admin.issues.show', $issue->slug))
            ->assertSessionHasNoErrors();

        $issue->refresh();
        $this->assertSame(IssueStatus::Published, $issue->status);
        $this->assertNotNull($issue->published_at);
        $this->assertSame($this->chief->id, $issue->published_by);

        foreach ([$first, $second] as $article) {
            $article->refresh();
            $this->assertSame(ArticleStatus::Published, $article->status);
            $this->assertNotNull($article->published_at);
            $this->assertStringEndsWith('-'.$article->id, (string) $article->slug);
            $this->assertNotNull($article->submitter);
            Notification::assertSentTo($article->submitter, ArticleUpdateNotification::class);
        }

        // Saytda: son sahifasi va maqola sahifasi
        $this->get(route('issues.show', $issue->slug))->assertOk();

        $this->get(route('articles.show', $first->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/articles/Show')
                ->where('article.id', $first->id)
                ->where('article.pages', '10–17')
                ->where('article.issue.label', '№4 (2026)')
                ->has('article.authors', 2)
                ->where('article.citations.apa', fn (string $apa): bool => str_contains($apa, '('.now()->year.')') && str_contains($apa, 'https://doi.org/10.5281/insonvajamiyat.2026.1'))
                ->where('article.citations.gost', fn (string $gost): bool => str_contains($gost, '// Inson va Jamiyat') && str_contains($gost, 'B. 10–17'))
                ->where('article.neighbours.next.url', route('articles.show', $second->refresh()->slug))
                ->where('article.pdf.viewUrl', route('articles.pdf', $first->slug))
            );

        // Muallif kabinetida — saytdagi havola
        $this->actingAs($first->submitter)
            ->get(route('cabinet.articles.show', $first->uuid))
            ->assertInertia(fn (Assert $page) => $page->where('production.publicUrl', route('articles.show', $first->slug)));
    }

    public function test_only_chief_editor_can_publish(): void
    {
        $issue = $this->issue();
        $this->readyArticle($issue);

        $this->actingAs(User::factory()->withRole(RoleName::LayoutEditor)->createOne())
            ->post(route('admin.issues.publish', $issue->slug))
            ->assertForbidden();

        $this->assertSame(IssueStatus::Draft, $issue->refresh()->status);
    }

    public function test_late_article_is_published_into_published_issue(): void
    {
        $issue = $this->issue(IssueStatus::Published);
        $article = $this->readyArticle($issue, 3);

        $this->actingAs($this->chief)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page->where('article.can.publish', true));

        $this->actingAs($this->chief)
            ->post(route('admin.production.publish', $article->uuid))
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertTrue($article->isPublished());

        // Qoralama sondagi maqola alohida chop etilmaydi
        $draftIssue = JournalIssue::factory()->createOne();
        $other = $this->readyArticle($draftIssue, 4);

        $this->actingAs($this->chief)
            ->post(route('admin.production.publish', $other->uuid))
            ->assertSessionHasErrors('publish');
    }

    public function test_article_page_counts_views_and_downloads(): void
    {
        $issue = $this->issue();
        $article = $this->readyArticle($issue);
        $this->actingAs($this->chief)->post(route('admin.issues.publish', $issue->slug));
        $article->refresh();
        auth()->logout();

        $this->get(route('articles.show', $article->slug))->assertOk();
        $this->assertSame(1, $article->refresh()->views_count);

        // Shu sessiyada qayta ko'rish hisoblanmaydi
        $this->withSession(['viewed_articles.'.$article->id => now()->getTimestamp()])
            ->get(route('articles.show', $article->slug))
            ->assertOk();
        $this->assertSame(1, $article->refresh()->views_count);

        // Onlayn ko'rish yuklab olish hisoblanmaydi
        $this->get(route('articles.pdf', $article->slug))->assertOk();
        $this->assertSame(0, $article->refresh()->downloads_count);

        $this->get(route('articles.pdf', [$article->slug, 'download' => 1]))->assertOk()->assertDownload('final.pdf');
        $this->assertSame(1, $article->refresh()->downloads_count);

        $this->withSession(['downloaded_articles.'.$article->id => now()->toDateString()])
            ->get(route('articles.pdf', [$article->slug, 'download' => 1]))
            ->assertOk();
        $this->assertSame(1, $article->refresh()->downloads_count);
    }

    public function test_unpublished_article_pdf_is_hidden(): void
    {
        $article = $this->readyArticle($this->issue());
        $article->forceFill(['slug' => 'hali-chop-etilmagan'])->save();

        $this->get(route('articles.pdf', 'hali-chop-etilmagan'))->assertNotFound();
        $this->get(route('articles.show', 'hali-chop-etilmagan'))->assertNotFound();
    }
}

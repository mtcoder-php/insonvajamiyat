<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Nashr jarayoni: maketga olish, yakuniy PDF, korrektura, meta ma'lumotlar,
 * nashr oldidan tekshiruv, bosh muharrir tasdig'i va ruxsatlar.
 */
class ProductionTest extends TestCase
{
    use RefreshDatabase;

    private User $layout;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
        $this->chief = User::factory()->withRole(RoleName::ChiefEditor)->createOne();
    }

    private function article(ArticleStatus $status = ArticleStatus::Accepted): Article
    {
        $article = Article::factory()->withAuthors()->status($status)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subMonth(),
            'udc' => '94(575.1)',
        ]);
        $article->forceFill(['accepted_at' => now()->subWeek()])->save();

        return $article;
    }

    private function author(Article $article): User
    {
        $author = $article->submitter;
        $this->assertInstanceOf(User::class, $author);

        return $author;
    }

    private function uploadPdf(Article $article): void
    {
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), [
                'pdf' => UploadedFile::fake()->create('maqola-final.pdf', 800, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();
    }

    private function fillMetadata(Article $article, JournalIssue $issue): void
    {
        $this->actingAs($this->layout)
            ->put(route('admin.production.metadata', $article->uuid), [
                'doi' => '10.5281/insonvajamiyat.2026.0048',
                'udc' => '94(575.1)',
                'plagiarism_percent' => 11,
                'issue_id' => $issue->id,
                'page_from' => 45,
                'page_to' => 52,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_index_lists_accepted_articles_for_layout_editor(): void
    {
        $accepted = $this->article();
        $this->article(ArticleStatus::InReview);

        $this->actingAs($this->layout)
            ->get(route('admin.production.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/production/Index')
                ->where('filters.tab', 'new')
                ->where('counts.new', 1)
                ->has('articles.data', 1)
                ->where('articles.data.0.uuid', $accepted->uuid)
                ->has('stats', 4)
                ->where('adminBadges.production', 1)
            );
    }

    public function test_layout_editor_starts_production_and_uploads_final_pdf(): void
    {
        Notification::fake();
        $article = $this->article();

        $this->actingAs($this->layout)
            ->post(route('admin.production.start', $article->uuid))
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::InProduction, $article->status);
        $this->assertSame($this->layout->id, $article->layout_editor_id);

        $this->uploadPdf($article);

        $file = $article->files()->where('type', ArticleFileType::FinalPdf->value)->firstOrFail();
        Storage::disk('local')->assertExists($file->path);
        Notification::assertSentTo($this->author($article), ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::PROOF);

        // PDF brauzerda ochiladi (iframe), ?download=1 — yuklab olish
        $this->actingAs($this->layout)
            ->get(route('admin.production.files', [$article->uuid, $file->uuid]))
            ->assertOk();

        $this->actingAs($this->layout)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/production/Show')
                ->where('article.finalPdf.name', 'maqola-final.pdf')
                ->where('article.preview.isFinal', true)
                ->where('article.can.manage', true)
                ->where('article.can.approve', false)
                ->has('article.checks', 7)
                ->has('article.steps', 7)
            );
    }

    public function test_final_pdf_is_validated_and_requires_production_status(): void
    {
        $article = $this->article(ArticleStatus::InProduction);

        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), [
                'pdf' => UploadedFile::fake()->create('maqola.docx', 100),
            ])
            ->assertSessionHasErrors('pdf');

        $accepted = $this->article();
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $accepted->uuid), [
                'pdf' => UploadedFile::fake()->create('maqola.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('production');
    }

    public function test_metadata_assigns_issue_and_pages(): void
    {
        $article = $this->article(ArticleStatus::InProduction);
        $issue = JournalIssue::factory()->createOne(['year' => 2026, 'number' => 3]);

        $this->fillMetadata($article, $issue);

        $article->refresh();
        $this->assertSame('10.5281/insonvajamiyat.2026.0048', $article->doi);
        $this->assertSame(8, $article->pages_count);
        $this->assertSame($issue->id, $article->placement?->journal_issue_id);
        $this->assertSame('45–52', $article->placement?->pages());

        // Noto'g'ri DOI va sahifalar
        $this->actingAs($this->layout)
            ->put(route('admin.production.metadata', $article->uuid), [
                'doi' => 'insonvajamiyat-48',
                'issue_id' => $issue->id,
                'page_from' => 52,
                'page_to' => 45,
            ])
            ->assertSessionHasErrors(['doi', 'page_to']);

        // DOI takrorlanmaydi
        $other = $this->article(ArticleStatus::InProduction);
        $this->actingAs($this->layout)
            ->put(route('admin.production.metadata', $other->uuid), ['doi' => '10.5281/insonvajamiyat.2026.0048'])
            ->assertSessionHasErrors('doi');
    }

    public function test_full_flow_to_chief_editor_approval(): void
    {
        $article = $this->article(ArticleStatus::InProduction);
        $issue = JournalIssue::factory()->createOne();
        $author = $this->author($article);

        $this->uploadPdf($article);
        $this->fillMetadata($article, $issue);

        // Hali tayyor emas: format belgilanmagan, muallif tasdiqlamagan
        $this->actingAs($this->chief)
            ->post(route('admin.production.approve', $article->uuid))
            ->assertSessionHasErrors('production');

        $this->actingAs($this->layout)
            ->put(route('admin.production.format', $article->uuid), ['ok' => true])
            ->assertSessionHasNoErrors();

        // Muallif korrekturani ko'radi va tasdiqlaydi
        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('production.proof.name', 'maqola-final.pdf')
                ->where('production.canRespond', true)
                ->where('production.approved', false)
                ->where('production.doi', '10.5281/insonvajamiyat.2026.0048')
            );

        $this->actingAs($author)
            ->post(route('cabinet.articles.proof.approve', $article->uuid))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->chief)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.ready', true)
                ->where('article.can.approve', true)
            );

        Notification::fake();

        $this->actingAs($this->chief)
            ->post(route('admin.production.approve', $article->uuid))
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame($this->chief->id, $article->chief_editor_approved_by);
        Notification::assertSentTo($author, ArticleUpdateNotification::class);

        // Yangi PDF — tasdiq va muallif roziligi bekor bo'ladi
        $this->uploadPdf($article);
        $article->refresh();
        $this->assertNull($article->chief_editor_approved_at);

        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page->where('production.approved', false));
    }

    public function test_author_requests_proof_changes(): void
    {
        $article = $this->article(ArticleStatus::InProduction);
        $article->forceFill(['layout_editor_id' => $this->layout->id])->save();
        $this->uploadPdf($article);
        Notification::fake();

        $this->actingAs($this->author($article))
            ->post(route('cabinet.articles.proof.changes', $article->uuid), [
                'comment' => "3-bet: muallif ismi noto'g'ri yozilgan.",
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'article_id' => $article->id,
            'channel' => MessageChannel::AuthorEditor->value,
        ]);
        Notification::assertSentTo($this->layout, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::PROOF);

        $this->actingAs($this->layout)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.production.authorChanges', "3-bet: muallif ismi noto'g'ri yozilgan.")
                ->where('article.production.authorApproved', false)
            );

        // Faqat yuboruvchi muallif javob beradi
        $this->actingAs(User::factory()->author()->createOne())
            ->post(route('cabinet.articles.proof.approve', $article->uuid))
            ->assertForbidden();
    }

    public function test_cancel_returns_article_to_accepted(): void
    {
        $article = $this->article(ArticleStatus::InProduction);

        $this->actingAs($this->layout)
            ->post(route('admin.production.cancel', $article->uuid), ['reason' => 'Maket qayta tayyorlanadi'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ArticleStatus::Accepted, $article->refresh()->status);
        $this->assertDatabaseHas('article_status_histories', [
            'article_id' => $article->id,
            'to_status' => ArticleStatus::Accepted->value,
            'is_visible_to_author' => false,
        ]);
    }

    public function test_permissions(): void
    {
        $article = $this->article(ArticleStatus::InProduction);

        // Muharrirda nashr jarayoni ruxsati yo'q
        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->get(route('admin.production.index'))
            ->assertForbidden();

        // Texnik xodim bosh muharrir tasdig'ini bera olmaydi
        $this->actingAs($this->layout)
            ->post(route('admin.production.approve', $article->uuid))
            ->assertForbidden();

        // Taqrizdagi maqola nashr bo'limida ko'rinmaydi
        $this->actingAs($this->layout)
            ->get(route('admin.production.show', $this->article(ArticleStatus::InReview)->uuid))
            ->assertNotFound();

        // Bosh muharrir bo'limni ochadi
        $this->actingAs($this->chief)
            ->get(route('admin.production.index'))
            ->assertOk();
    }
}

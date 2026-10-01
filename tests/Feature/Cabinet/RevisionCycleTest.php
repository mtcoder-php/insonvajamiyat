<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVersionType;
use App\Enums\EditorialDecisionType;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tuzatish sikli: tahririyat qarori → muallif tuzatilgan versiyani yuboradi →
 * tahririyat qayta ko'rib chiqadi / yangi taqriz raundi.
 */
class RevisionCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->editor = User::factory()->withRole(RoleName::Editor)->createOne();
    }

    private function article(ArticleStatus $status = ArticleStatus::RevisionRequired): Article
    {
        $article = Article::factory()->status($status)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subWeek(),
        ]);
        $article->forceFill(['handling_editor_id' => $this->editor->id, 'review_round' => 1])->save();

        return $article;
    }

    private function author(Article $article): User
    {
        $author = $article->submitter;
        $this->assertInstanceOf(User::class, $author);

        return $author;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'manuscript' => UploadedFile::fake()->create('maqola-v2.docx', 300, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'response' => "1-izoh: metodologiya bo'limi kengaytirildi. 2-izoh: adabiyotlar yangilandi.",
        ];
    }

    public function test_editor_decision_notifies_author_and_shows_revision_card(): void
    {
        Notification::fake();
        $article = $this->article(ArticleStatus::UnderReview);

        $this->actingAs($this->editor)
            ->post(route('admin.articles.decision', $article->uuid), [
                'decision' => EditorialDecisionType::RequestRevision->value,
                'comment_to_author' => 'Metodologiyani kengaytiring va adabiyotlarni yangilang.',
            ])
            ->assertSessionHasNoErrors();

        $author = $this->author($article);
        Notification::assertSentTo($author, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::DECISION);

        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('revision.comment', 'Metodologiyani kengaytiring va adabiyotlarni yangilang.')
                ->where('revision.url', route('cabinet.articles.revision.store', $article->uuid))
            );
    }

    public function test_revision_card_is_hidden_when_revision_is_not_requested(): void
    {
        $article = $this->article(ArticleStatus::UnderReview);

        $this->actingAs($this->author($article))
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page->where('revision', null));
    }

    public function test_author_resubmits_revised_version(): void
    {
        Notification::fake();
        $article = $this->article();
        $author = $this->author($article);

        $this->actingAs($author)
            ->post(route('cabinet.articles.revision.store', $article->uuid), [
                ...$this->payload(),
                'supplementary' => [UploadedFile::fake()->create('jadval.png', 40, 'image/png')],
            ])
            ->assertRedirect(route('cabinet.articles.show', $article->uuid))
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::Resubmitted, $article->status);

        $version = $article->versions()->latest('id')->firstOrFail();
        $this->assertSame(ArticleVersionType::Revision, $version->type);
        $this->assertSame(1, $version->review_round);
        $this->assertStringContainsString('metodologiya', (string) $version->change_note);
        $this->assertSame(2, $version->files()->count());
        $this->assertTrue($version->files()->where('type', ArticleFileType::Revision->value)->exists());

        Notification::assertSentTo($this->editor, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::RESUBMITTED && $n->toStaff);

        // Ikkinchi marta yuborib bo'lmaydi
        $this->actingAs($author)
            ->post(route('cabinet.articles.revision.store', $article->uuid), $this->payload())
            ->assertForbidden();
    }

    public function test_resubmission_is_validated(): void
    {
        $article = $this->article();

        $this->actingAs($this->author($article))
            ->post(route('cabinet.articles.revision.store', $article->uuid), [
                'manuscript' => UploadedFile::fake()->create('maqola.exe', 10),
                'response' => 'qisqa',
            ])
            ->assertSessionHasErrors(['manuscript', 'response']);

        $this->assertSame(ArticleStatus::RevisionRequired, $article->refresh()->status);
    }

    public function test_only_submitter_can_resubmit(): void
    {
        $article = $this->article();

        $this->actingAs(User::factory()->author()->createOne())
            ->post(route('cabinet.articles.revision.store', $article->uuid), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, $article->versions()->count());
    }

    public function test_resubmitted_article_goes_to_new_review_round(): void
    {
        $article = $this->article();
        $this->actingAs($this->author($article))
            ->post(route('cabinet.articles.revision.store', $article->uuid), $this->payload())
            ->assertSessionHasNoErrors();

        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();

        $this->actingAs($this->editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$reviewer->id],
                'due_days' => 14,
            ])
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::InReview, $article->status);
        $this->assertSame(2, $article->review_round);

        $review = Review::query()->where('reviewer_id', $reviewer->id)->firstOrFail();
        $this->assertSame(2, $review->round);
        $review->forceFill(['status' => ReviewStatus::Accepted])->save();

        // Taqrizchi muallifning javobini ko'radi (muallif ismisiz)
        $this->actingAs($reviewer)
            ->get(route('admin.reviews.show', $review->id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('review.article.authorResponse.version', 1)
                ->where('review.article.authorResponse.note', fn (string $note): bool => str_contains($note, 'metodologiya'))
                ->where('review.article.files.0.typeLabel', ArticleFileType::Revision->label())
            );
    }
}

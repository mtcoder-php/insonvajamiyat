<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Taqriz jarayoni: taklif qilish, qabul / rad etish, fayllarga kirish, qoralama va
 * topshirish, muharrir va muallif tomonidagi natijalar, ruxsatlar.
 */
class ReviewProcessTest extends TestCase
{
    use RefreshDatabase;

    private const CRITERIA = [
        'relevance' => 4,
        'novelty' => 4.5,
        'methodology' => 4,
        'results' => 4,
        'conclusions' => 5,
        'references' => 4.5,
    ];

    private function editor(): User
    {
        return User::factory()->withRole(RoleName::Editor)->createOne();
    }

    private function reviewer(): User
    {
        return User::factory()->withRole(RoleName::Reviewer)->createOne();
    }

    private function article(ArticleStatus $status = ArticleStatus::UnderReview): Article
    {
        return Article::factory()->status($status)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subDay(),
            'review_round' => 0,
        ]);
    }

    private function review(ReviewStatus $status, ?User $reviewer = null, ?Article $article = null): Review
    {
        return Review::factory()->status($status)->createOne([
            'article_id' => ($article ?? $this->article(ArticleStatus::InReview))->id,
            'reviewer_id' => ($reviewer ?? $this->reviewer())->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(bool $submit): array
    {
        return [
            'submit' => $submit ? 1 : 0,
            'criteria' => self::CRITERIA,
            'recommendation' => ReviewRecommendation::MinorRevision->value,
            'comments_to_author' => str_repeat('Maqola dolzarb, ammo metodologiya bo\'limini kengaytirish kerak. ', 2),
            'comments_to_editor' => 'Nashrga tavsiya etaman.',
        ];
    }

    public function test_editor_invites_reviewers_and_starts_round(): void
    {
        $article = $this->article();
        [$first, $second] = [$this->reviewer(), $this->reviewer()];
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$first->id, $second->id],
                'due_days' => 10,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::InReview, $article->status);
        $this->assertSame(1, $article->review_round);
        $this->assertSame(2, $article->reviews()->where('round', 1)->count());
        $this->assertDatabaseHas('editorial_decisions', [
            'article_id' => $article->id,
            'round' => 1,
            'decision' => EditorialDecisionType::SendToReview->value,
        ]);

        // Shu raundga qo'shimcha taqrizchi — raund o'zgarmaydi, takror taklif o'tkazib yuboriladi
        $third = $this->reviewer();
        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$first->id, $third->id],
                'due_days' => 10,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $article->refresh()->review_round);
        $this->assertSame(3, $article->reviews()->count());

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('selected.reviews', 3)
                ->where('selected.can.invite', true)
                ->where('reviewers', fn ($reviewers) => collect($reviewers)->contains('id', $third->id))
            );
    }

    public function test_only_reviewers_who_are_not_authors_can_be_invited(): void
    {
        $article = $this->article();
        $editor = $this->editor();
        $author = User::factory()->withRole(RoleName::Reviewer)->createOne();
        $article->update(['submitter_id' => $author->id]);

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [User::factory()->author()->createOne()->id],
                'due_days' => 14,
            ])
            ->assertSessionHasErrors('reviewer_ids');

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$author->id],
                'due_days' => 14,
            ])
            ->assertSessionHasErrors('reviewer_ids');

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $this->article(ArticleStatus::Submitted)->uuid), [
                'reviewer_ids' => [$this->reviewer()->id],
                'due_days' => 14,
            ])
            ->assertSessionHasErrors('reviewer_ids');

        $this->assertSame(0, Review::query()->count());
    }

    public function test_reviewer_sees_invitations_and_accepts(): void
    {
        $reviewer = $this->reviewer();
        $review = $this->review(ReviewStatus::Invited, $reviewer);
        $this->review(ReviewStatus::Invited); // boshqa taqrizchiniki

        $this->actingAs($reviewer)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reviews/Index')
                ->where('filters.tab', 'invited')
                ->where('counts.invited', 1)
                ->has('reviews.data', 1)
                ->where('adminBadges.reviews', 1)
            );

        $this->actingAs($reviewer)
            ->get(route('admin.reviews.show', $review->id))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reviews/Show')
                ->where('review.can.respond', true)
                ->has('review.article.files', 0)
                ->missing('review.article.authors')
                ->has('options.criteria', 6)
            );

        $this->actingAs($reviewer)
            ->post(route('admin.reviews.accept', $review->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertSame(ReviewStatus::Accepted, $review->status);
        $this->assertNotNull($review->responded_at);
    }

    public function test_reviewer_declines_with_reason(): void
    {
        $reviewer = $this->reviewer();
        $review = $this->review(ReviewStatus::Invited, $reviewer);

        $this->actingAs($reviewer)
            ->post(route('admin.reviews.decline', $review->id), ['reason' => 'Mavzu sohamga mos emas'])
            ->assertRedirect(route('admin.reviews.index'));

        $review->refresh();
        $this->assertSame(ReviewStatus::Declined, $review->status);
        $this->assertSame('Mavzu sohamga mos emas', $review->comments_to_editor);

        // Qayta javob berib bo'lmaydi
        $this->actingAs($reviewer)
            ->post(route('admin.reviews.accept', $review->id))
            ->assertSessionHasErrors('review');
    }

    public function test_article_files_open_only_after_acceptance(): void
    {
        Storage::fake('local');
        $reviewer = $this->reviewer();
        $review = $this->review(ReviewStatus::Invited, $reviewer);
        Storage::disk('local')->put('articles/test/manuscript.pdf', '%PDF-1.4 test');
        $file = $review->article->files()->create([
            'type' => ArticleFileType::Manuscript,
            'disk' => 'local',
            'path' => 'articles/test/manuscript.pdf',
            'original_name' => 'maqola.pdf',
            'mime_type' => 'application/pdf',
            'size' => 13,
        ]);

        $url = route('admin.reviews.files', [$review->id, $file->uuid]);

        $this->actingAs($reviewer)->get($url)->assertForbidden();

        $review->forceFill(['status' => ReviewStatus::Accepted])->save();

        $this->actingAs($reviewer)->get($url)->assertOk();
        $this->actingAs($reviewer)
            ->get(route('admin.reviews.show', $review->id))
            ->assertInertia(fn (Assert $page) => $page
                ->has('review.article.files', 1)
                // Blind review: asl nom (muallif ismi bo'lishi mumkin) o'rniga neytral nom
                ->where('review.article.files.0.name', 'manuscript-'.$file->id.'.pdf')
            );

        $this->actingAs($reviewer)->get($url.'?download=1')
            ->assertDownload('manuscript-'.$file->id.'.pdf');

        // Boshqa taqrizchi bu faylni ocha olmaydi
        $this->actingAs($this->reviewer())->get($url)->assertForbidden();

        // Yakuniy PDF taqrizchiga berilmaydi
        $final = $review->article->files()->create([
            'type' => ArticleFileType::FinalPdf,
            'disk' => 'local',
            'path' => 'articles/test/manuscript.pdf',
            'original_name' => 'final.pdf',
            'mime_type' => 'application/pdf',
            'size' => 13,
        ]);
        $this->actingAs($reviewer)
            ->get(route('admin.reviews.files', [$review->id, $final->uuid]))
            ->assertNotFound();
    }

    public function test_draft_is_saved_and_submission_is_validated(): void
    {
        Storage::fake('local');
        $reviewer = $this->reviewer();
        $review = $this->review(ReviewStatus::Accepted, $reviewer);

        // Qoralama — to'liq bo'lmasa ham saqlanadi
        $this->actingAs($reviewer)
            ->put(route('admin.reviews.update', $review->id), [
                'submit' => 0,
                'criteria' => ['relevance' => 4],
                'comments_to_author' => 'Qisqa izoh',
            ])
            ->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertSame(ReviewStatus::Accepted, $review->status);
        $this->assertEquals(['relevance' => 4.0], $review->criteria_scores);

        // Topshirish — barcha mezonlar va kamida 50 belgili taqriz kerak
        $this->actingAs($reviewer)
            ->put(route('admin.reviews.update', $review->id), [
                'submit' => 1,
                'criteria' => ['relevance' => 4],
                'comments_to_author' => 'Qisqa izoh',
            ])
            ->assertSessionHasErrors(['criteria.novelty', 'recommendation', 'comments_to_author']);

        $this->actingAs($reviewer)
            ->put(route('admin.reviews.update', $review->id), [...$this->payload(true), 'criteria' => [...self::CRITERIA, 'relevance' => 4.3]])
            ->assertSessionHasErrors('criteria.relevance');

        $this->actingAs($reviewer)
            ->put(route('admin.reviews.update', $review->id), [
                ...$this->payload(true),
                'attachment' => UploadedFile::fake()->create('taqriz.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertSame(ReviewStatus::Completed, $review->status);
        $this->assertNotNull($review->completed_at);
        $this->assertSame(ReviewRecommendation::MinorRevision, $review->recommendation);
        // O'rtacha 26/6 = 4.33 → 0.5 qadamga yaxlitlanadi
        $this->assertEquals(4.5, $review->score);
        $this->assertNotNull($review->attachment_path);
        Storage::disk('local')->assertExists((string) $review->attachment_path);

        // Yakunlangan taqrizni o'zgartirib bo'lmaydi
        $this->actingAs($reviewer)
            ->put(route('admin.reviews.update', $review->id), $this->payload(true))
            ->assertSessionHasErrors('review');
    }

    public function test_editor_sees_completed_review_and_can_cancel_pending(): void
    {
        $editor = $this->editor();
        $article = $this->article(ArticleStatus::InReview);
        $article->forceFill(['review_round' => 1])->save();
        $done = $this->review(ReviewStatus::Completed, article: $article);
        $done->forceFill([
            'criteria_scores' => self::CRITERIA,
            'score' => 4.5,
            'recommendation' => ReviewRecommendation::Accept,
            'comments_to_author' => 'Yaxshi maqola',
            'completed_at' => now(),
        ])->save();
        $pending = $this->review(ReviewStatus::Invited, article: $article);

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('selected.reviews', 2)
                ->where('selected.reviews', fn ($reviews) => collect($reviews)->contains(
                    fn (array $r): bool => $r['id'] === $done->id && $r['commentsToAuthor'] === 'Yaxshi maqola' && $r['cancelUrl'] === null,
                ))
            );

        $this->actingAs($editor)
            ->delete(route('admin.articles.reviews.destroy', [$article->uuid, $pending->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReviewStatus::Cancelled, $pending->refresh()->status);

        $this->actingAs($editor)
            ->delete(route('admin.articles.reviews.destroy', [$article->uuid, $done->id]))
            ->assertSessionHasErrors('review');
    }

    public function test_author_sees_anonymous_reviews_only_after_decision(): void
    {
        $article = $this->article(ArticleStatus::InReview);
        $article->forceFill(['review_round' => 1])->save();
        $reviewer = $this->reviewer();
        $review = $this->review(ReviewStatus::Completed, $reviewer, $article);
        $review->forceFill([
            'criteria_scores' => self::CRITERIA,
            'score' => 4.5,
            'recommendation' => ReviewRecommendation::MinorRevision,
            'comments_to_author' => 'Muallifga izoh',
            'comments_to_editor' => 'Maxfiy izoh',
            'completed_at' => now(),
        ])->save();
        $author = $article->submitter;
        $this->assertInstanceOf(User::class, $author);

        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page->has('reviews', 0));

        $article->decisions()->create([
            'editor_id' => $this->editor()->id,
            'round' => 1,
            'decision' => EditorialDecisionType::RequestRevision,
        ]);

        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews', 1)
                ->where('reviews.0.label', 'Taqrizchi 1')
                ->where('reviews.0.comments', 'Muallifga izoh')
                ->has('reviews.0.criteria', 6)
                ->missing('reviews.0.reviewer')
                ->missing('reviews.0.commentsToEditor')
            )
            ->assertDontSee($reviewer->name)
            ->assertDontSee('Maxfiy izoh');
    }

    public function test_permissions(): void
    {
        $review = $this->review(ReviewStatus::Invited);
        $article = $this->article();

        // Muallif taqrizchi bo'limiga kira olmaydi
        $this->actingAs(User::factory()->author()->createOne())
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        // Begona taqrizchi boshqa taqrizni ko'ra / qabul qila olmaydi
        $stranger = $this->reviewer();
        $this->actingAs($stranger)->get(route('admin.reviews.show', $review->id))->assertForbidden();
        $this->actingAs($stranger)->post(route('admin.reviews.accept', $review->id))->assertForbidden();
        $this->actingAs($stranger)->put(route('admin.reviews.update', $review->id), $this->payload(false))->assertForbidden();

        // Taqrizchi boshqa taqrizchilarni tayinlay olmaydi
        $this->actingAs($stranger)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$stranger->id],
                'due_days' => 14,
            ])
            ->assertForbidden();
    }
}

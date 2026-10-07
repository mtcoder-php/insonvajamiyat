<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Mualliflar va Taqrizchilar: ro'yxatlar, sahifalar, taqrizchini qo'shish,
 * to'xtatish, yo'nalishlar va taklif ro'yxatida mos yo'nalish.
 */
class PeopleTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->withRole(RoleName::Editor)->createOne();
    }

    private function reviewer(): User
    {
        return User::factory()->withRole(RoleName::Reviewer)->createOne();
    }

    public function test_authors_list_filters_and_detail(): void
    {
        $history = Subject::factory()->createOne();
        $author = User::factory()->author()->createOne(['name' => 'Aziza Karimova']);
        $other = User::factory()->author()->createOne(['name' => 'Bobur Aliyev']);

        Article::factory()->published()->createOne([
            'submitter_id' => $author->id,
            'subject_id' => $history->id,
            'views_count' => 40,
        ]);
        Article::factory()->status(ArticleStatus::InReview)->createOne(['submitter_id' => $author->id]);
        Article::factory()->status(ArticleStatus::Draft)->createOne(['submitter_id' => $author->id]);
        Payment::factory()->paid()->createOne(['user_id' => $author->id, 'amount' => 150000]);

        // Hammuallif sifatida
        $coauthored = Article::factory()->status(ArticleStatus::Accepted)->createOne(['submitter_id' => $other->id]);
        ArticleAuthor::factory()->createOne(['article_id' => $coauthored->id, 'user_id' => $author->id]);

        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.authors.index', ['sort' => 'articles']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/authors/Index')
                ->where('counts.total', 2)
                ->where('counts.published', 1)
                ->where('canPayments', true)
                ->where('authors.data.0.id', $author->id)
                ->where('authors.data.0.articles', 2)
                ->where('authors.data.0.published', 1)
                ->where('authors.data.0.paid', fn (mixed $v): bool => (float) $v === 150000.0)
            );

        $this->actingAs($editor)
            ->get(route('admin.authors.index', ['subject' => $history->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('authors.data', 1)
                ->where('filters.subject', $history->id)
            );

        $this->actingAs($editor)
            ->get(route('admin.authors.index', ['search' => 'Bobur']))
            ->assertInertia(fn (Assert $page) => $page->has('authors.data', 1)->where('authors.data.0.id', $other->id));

        $this->actingAs($editor)
            ->get(route('admin.authors.show', $author->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/authors/Show')
                ->where('profile.id', $author->id)
                ->where('stats.articles', 3)
                ->where('stats.coauthored', 1)
                ->where('stats.drafts', 1)
                ->where('stats.groups.published', 1)
                ->where('stats.views', 40)
                ->has('articles', 3)
                ->has('payments', 1)
                ->where('urls.user', null)
            );

        // Muallif bo'lmagan xodim sahifasi yo'q; muallif admin bo'limiga kira olmaydi
        $this->actingAs($editor)->get(route('admin.authors.show', $editor->id))->assertNotFound();
        $this->actingAs($author)->get(route('admin.authors.index'))->assertForbidden();
    }

    public function test_reviewers_list_shows_load_speed_and_status_filters(): void
    {
        $fast = $this->reviewer();
        $busy = $this->reviewer();
        $paused = $this->reviewer();
        $paused->forceFill(['reviews_paused_at' => now()])->save();

        Review::factory()->status(ReviewStatus::Completed)->createOne([
            'reviewer_id' => $fast->id,
            'created_at' => now()->subDays(10),
            'completed_at' => now()->subDays(6),
            'due_at' => now()->subDays(2),
            'recommendation' => ReviewRecommendation::Accept,
            'score' => 4.5,
        ]);

        foreach (range(1, 3) as $i) {
            Review::factory()->status(ReviewStatus::Accepted)->createOne([
                'reviewer_id' => $busy->id,
                'due_at' => $i === 1 ? now()->subDay() : now()->addWeek(),
            ]);
        }

        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.reviewers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reviewers/Index')
                ->where('counts.total', 3)
                ->where('counts.paused', 1)
                ->where('counts.available', 1)
                ->where('counts.active', 3)
                ->where('counts.overdue', 1)
                ->has('reviewers.data', 3)
            );

        $this->actingAs($editor)
            ->get(route('admin.reviewers.index', ['status' => 'busy']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviewers.data', 1)
                ->where('reviewers.data.0.id', $busy->id)
                ->where('reviewers.data.0.active', 3)
                ->where('reviewers.data.0.overdue', 1)
            );

        $this->actingAs($editor)
            ->get(route('admin.reviewers.index', ['status' => 'available']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviewers.data', 1)
                ->where('reviewers.data.0.id', $fast->id)
                ->where('reviewers.data.0.avgDays', fn (mixed $d): bool => (float) $d === 4.0)
                ->where('reviewers.data.0.onTime', 100)
            );

        $this->actingAs($editor)
            ->get(route('admin.reviewers.index', ['status' => 'paused']))
            ->assertInertia(fn (Assert $page) => $page->has('reviewers.data', 1)->where('reviewers.data.0.isPaused', true));

        $this->actingAs($editor)
            ->get(route('admin.reviewers.show', $fast->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reviewers/Show')
                ->where('isReviewer', true)
                ->where('stats.completed', 1)
                ->where('stats.avgScore', fn (mixed $v): bool => (float) $v === 4.5)
                ->where('stats.recommendations.accept', 1)
                ->has('reviews', 1)
            );

        // Taqrizchilar bo'limi — articles.assign_reviewer; kontent menejer kira olmaydi
        $this->actingAs(User::factory()->withRole(RoleName::ContentManager)->createOne())
            ->get(route('admin.reviewers.index'))
            ->assertForbidden();
    }

    public function test_editor_adds_reviewer_with_subjects_and_candidate_search(): void
    {
        $subject = Subject::factory()->createOne();
        $candidate = User::factory()->author()->createOne(['name' => 'Qwzx Rahimova', 'email' => 'qwzx.rahimova@example.uz']);
        $blocked = User::factory()->author()->blocked()->createOne(['name' => 'Qwzx Bloklangan']);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->getJson(route('admin.reviewers.candidates', ['q' => 'Qwzx']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $candidate->id);

        $this->actingAs($editor)
            ->post(route('admin.reviewers.store'), ['user_id' => $blocked->id])
            ->assertSessionHasErrors('user_id');

        $this->actingAs($editor)
            ->post(route('admin.reviewers.store'), ['user_id' => $candidate->id, 'subject_ids' => [$subject->id]])
            ->assertRedirect(route('admin.reviewers.show', $candidate->id));

        $candidate->refresh();
        $this->assertTrue($candidate->hasRole(RoleName::Reviewer));
        $this->assertTrue($candidate->hasRole(RoleName::Author));
        $this->assertSame([$subject->id], $candidate->subjects()->pluck('subjects.id')->all());
        $this->assertDatabaseHas('audit_logs', ['event' => 'review.reviewer_added']);

        // Takror qo'shib bo'lmaydi; endi nomzodlar ro'yxatida yo'q
        $this->actingAs($editor)
            ->post(route('admin.reviewers.store'), ['user_id' => $candidate->id])
            ->assertSessionHasErrors('user_id');
        $this->actingAs($editor)
            ->getJson(route('admin.reviewers.candidates', ['q' => 'Rahimova']))
            ->assertJsonCount(0, 'data');

        // Yo'nalishlarni almashtirish
        $other = Subject::factory()->createOne();
        $this->actingAs($editor)
            ->put(route('admin.reviewers.subjects', $candidate->id), ['subject_ids' => [$other->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$other->id], $candidate->subjects()->pluck('subjects.id')->all());
        $this->assertDatabaseHas('audit_logs', ['event' => 'review.reviewer_subjects']);
    }

    public function test_paused_reviewer_is_hidden_from_invites_and_matching_subject_comes_first(): void
    {
        $subject = Subject::factory()->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'subject_id' => $subject->id,
            'submitted_at' => now()->subDay(),
            'review_round' => 0,
        ]);

        $plain = $this->reviewer();
        $plain->forceFill(['name' => 'Aaa Umumiy'])->save();
        $expert = $this->reviewer();
        $expert->forceFill(['name' => 'Zzz Mutaxassis'])->save();
        $expert->subjects()->attach($subject->id);
        $paused = $this->reviewer();

        $editor = $this->editor();

        $this->actingAs($editor)
            ->put(route('admin.reviewers.status', $paused->id), ['paused' => true])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($paused->refresh()->reviews_paused_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'review.reviewer_paused']);

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviewers', 2)
                ->where('reviewers.0.id', $expert->id)
                ->where('reviewers.0.matches', true)
                ->where('reviewers.1.matches', false)
            );

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), ['reviewer_ids' => [$paused->id], 'due_days' => 14])
            ->assertSessionHasErrors('reviewer_ids');

        $this->actingAs($editor)
            ->put(route('admin.reviewers.status', $paused->id), ['paused' => false])
            ->assertSessionHasNoErrors();
        $this->assertNull($paused->refresh()->reviews_paused_at);
    }

    public function test_reviewer_with_active_reviews_cannot_be_removed(): void
    {
        $reviewer = $this->reviewer();
        $review = Review::factory()->status(ReviewStatus::Accepted)->createOne(['reviewer_id' => $reviewer->id]);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->delete(route('admin.reviewers.destroy', $reviewer->id))
            ->assertSessionHasErrors('reviewer');
        $this->assertTrue($reviewer->refresh()->hasRole(RoleName::Reviewer));

        $review->forceFill(['status' => ReviewStatus::Completed, 'completed_at' => now()])->save();

        $this->actingAs($editor)
            ->delete(route('admin.reviewers.destroy', $reviewer->id))
            ->assertRedirect(route('admin.reviewers.index'));

        $this->assertFalse($reviewer->refresh()->hasRole(RoleName::Reviewer));
        $this->assertDatabaseHas('audit_logs', ['event' => 'review.reviewer_removed']);

        // Tarix saqlanadi: sahifa ochiladi, lekin endi taqrizchi emas
        $this->actingAs($editor)
            ->get(route('admin.reviewers.show', $reviewer->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('isReviewer', false)->has('reviews', 1));
    }
}

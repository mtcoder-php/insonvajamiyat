<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ArticleStatus;
use App\Events\ArticleStatusChanged;
use App\Models\Article;
use App\Models\User;
use App\Services\Articles\ArticleTimeline;
use App\Services\Articles\ArticleWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Maqola holatlari mashinasi (ArticleWorkflow) va muallif timeline'i.
 */
class ArticleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowed_transition_updates_status_history_and_timestamps(): void
    {
        Event::fake([ArticleStatusChanged::class]);

        $editor = User::factory()->create();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne();

        $history = app(ArticleWorkflow::class)
            ->transition($article, ArticleStatus::Accepted, $editor, 'Qabul qilindi');

        $article->refresh();
        $this->assertSame(ArticleStatus::Accepted, $article->status);
        $this->assertNotNull($article->accepted_at);
        $this->assertSame(ArticleStatus::UnderReview, $history->from_status);
        $this->assertSame($editor->id, $history->changed_by);
        $this->assertSame('Qabul qilindi', $history->comment);
        $this->assertTrue($history->is_visible_to_author);

        Event::assertDispatched(
            ArticleStatusChanged::class,
            fn (ArticleStatusChanged $event): bool => $event->to === ArticleStatus::Accepted
                && $event->from === ArticleStatus::UnderReview,
        );
    }

    public function test_forbidden_transition_throws_and_changes_nothing(): void
    {
        $article = Article::factory()->published()->createOne();

        try {
            app(ArticleWorkflow::class)->transition($article, ArticleStatus::Withdrawn, null);
            $this->fail('ValidationException kutilgan edi');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(ArticleStatus::Published, $article->refresh()->status);
        $this->assertSame(0, $article->statusHistories()->count());
    }

    public function test_submitted_at_is_kept_on_resubmission_chain(): void
    {
        $article = Article::factory()->status(ArticleStatus::Draft)->createOne(['submitted_at' => null]);
        $workflow = app(ArticleWorkflow::class);

        $workflow->transition($article, ArticleStatus::Submitted, null);
        $first = $article->refresh()->submitted_at;

        $this->assertNotNull($first);

        $this->travel(2)->days();
        $workflow->transition($article, ArticleStatus::UnderReview, null);

        $this->assertEquals($first, $article->refresh()->submitted_at);
    }

    public function test_timeline_marks_done_current_and_pending_steps(): void
    {
        $article = Article::factory()->status(ArticleStatus::Submitted)->createOne();
        $workflow = app(ArticleWorkflow::class);
        $workflow->transition($article, ArticleStatus::UnderReview, null);
        $workflow->transition($article, ArticleStatus::InReview, null);
        $workflow->transition($article, ArticleStatus::RevisionRequired, null);

        $steps = collect(app(ArticleTimeline::class)->for($article->refresh()))->pluck('state', 'key');

        $this->assertSame([
            'submitted' => 'done',
            'editor' => 'done',
            'review' => 'done',
            'revision' => 'current',
            'accepted' => 'pending',
            'published' => 'pending',
        ], $steps->all());
    }

    public function test_timeline_shows_failed_step_for_withdrawn_article(): void
    {
        $article = Article::factory()->status(ArticleStatus::Submitted)->createOne();
        app(ArticleWorkflow::class)->transition($article, ArticleStatus::Withdrawn, null);

        $steps = app(ArticleTimeline::class)->for($article->refresh());

        $this->assertSame('done', $steps[0]['state']);
        $this->assertSame('withdrawn', $steps[1]['key']);
        $this->assertSame('failed', $steps[1]['state']);
    }
}

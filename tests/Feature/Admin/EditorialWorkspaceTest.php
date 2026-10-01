<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Muharrir ish joyi: navbatlar, maqola kartasi, ko'rib chiqishga olish, qarorlar,
 * mas'ul muharrir va ichki izohlar.
 */
class EditorialWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->withRole(RoleName::Editor)->createOne();
    }

    private function article(ArticleStatus $status = ArticleStatus::Submitted): Article
    {
        return Article::factory()->status($status)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function test_index_opens_new_queue_and_selects_first_article(): void
    {
        $first = $this->article();
        $this->article(ArticleStatus::UnderReview);
        $this->article(ArticleStatus::Draft);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/articles/Index')
                ->where('filters.queue', 'new')
                ->where('counts.new', 1)
                ->where('counts.reviewing', 1)
                ->where('counts.all', 2)
                ->has('articles.data', 1)
                ->where('selected.uuid', $first->uuid)
                ->where('selected.can.startReview', true)
                ->has('stats', 5)
                ->where('editors', fn ($editors) => collect($editors)->contains('id', $editor->id))
            );
    }

    public function test_article_can_be_selected_from_any_queue_but_not_drafts(): void
    {
        $reviewing = $this->article(ArticleStatus::UnderReview);
        $draft = $this->article(ArticleStatus::Draft);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'new', 'article' => $reviewing->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.uuid', $reviewing->uuid)
                ->where('selected.availableDecisions', ['request_revision', 'accept', 'reject'])
            );

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $draft->uuid]))
            ->assertInertia(fn (Assert $page) => $page->where('selected', null));
    }

    public function test_editor_takes_article_into_review(): void
    {
        $article = $this->article();
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.articles.start-review', $article->uuid))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::UnderReview, $article->status);
        $this->assertSame($editor->id, $article->handling_editor_id);
    }

    public function test_revision_request_requires_comment_and_is_visible_to_author(): void
    {
        $article = $this->article(ArticleStatus::UnderReview);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.articles.decision', $article->uuid), ['decision' => 'request_revision'])
            ->assertSessionHasErrors(['comment_to_author']);

        $this->actingAs($editor)
            ->post(route('admin.articles.decision', $article->uuid), [
                'decision' => 'request_revision',
                'comment_to_author' => "Adabiyotlar ro'yxatini to'ldiring.",
                'internal_note' => 'Plagiat 12%',
            ])
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::RevisionRequired, $article->status);
        $this->assertDatabaseHas('editorial_decisions', [
            'article_id' => $article->id,
            'editor_id' => $editor->id,
            'decision' => EditorialDecisionType::RequestRevision->value,
            'internal_note' => 'Plagiat 12%',
        ]);

        $this->actingAs($article->submitter)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.history.0.comment', "Adabiyotlar ro'yxatini to'ldiring.")
            );
    }

    public function test_editor_accepts_and_rejects(): void
    {
        $editor = $this->editor();
        $accepted = $this->article(ArticleStatus::UnderReview);
        $rejected = $this->article(ArticleStatus::UnderReview);

        $this->actingAs($editor)
            ->post(route('admin.articles.decision', $accepted->uuid), ['decision' => 'accept'])
            ->assertSessionHasNoErrors();

        $this->actingAs($editor)
            ->post(route('admin.articles.decision', $rejected->uuid), [
                'decision' => 'reject',
                'comment_to_author' => "Mavzu jurnal yo'nalishiga mos emas.",
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ArticleStatus::Accepted, $accepted->refresh()->status);
        $this->assertNotNull($accepted->accepted_at);
        $this->assertSame(ArticleStatus::Rejected, $rejected->refresh()->status);
        $this->assertNotNull($rejected->rejected_at);
    }

    public function test_decision_must_be_allowed_for_current_status(): void
    {
        $article = $this->article();

        $this->actingAs($this->editor())
            ->post(route('admin.articles.decision', $article->uuid), ['decision' => 'accept'])
            ->assertSessionHasErrors(['decision']);

        $this->assertSame(ArticleStatus::Submitted, $article->refresh()->status);
        $this->assertDatabaseCount('editorial_decisions', 0);
    }

    public function test_handling_editor_can_be_assigned_only_to_deciders(): void
    {
        $article = $this->article(ArticleStatus::UnderReview);
        $editor = $this->editor();
        $colleague = $this->editor();

        $this->actingAs($editor)
            ->put(route('admin.articles.editor', $article->uuid), ['editor_id' => $colleague->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($colleague->id, $article->refresh()->handling_editor_id);

        $this->actingAs($editor)
            ->put(route('admin.articles.editor', $article->uuid), ['editor_id' => $article->submitter_id])
            ->assertSessionHasErrors(['editor_id']);

        $this->actingAs($editor)
            ->put(route('admin.articles.editor', $article->uuid), ['editor_id' => null])
            ->assertSessionHasNoErrors();
        $this->assertNull($article->refresh()->handling_editor_id);

        // "Mening vazifalarim"
        $article->forceFill(['handling_editor_id' => $editor->id])->save();

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.mine', 1)
                ->where('articles.data.0.uuid', $article->uuid)
            );
    }

    public function test_internal_notes_are_listed_for_staff(): void
    {
        $article = $this->article(ArticleStatus::UnderReview);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.articles.notes', $article->uuid), ['body' => 'Taqrizchilar natijasini kutamiz.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['article' => $article->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('selected.notes', 1)
                ->where('selected.notes.0.body', 'Taqrizchilar natijasini kutamiz.')
                ->where('selected.notes.0.mine', true)
            );
    }

    public function test_permissions(): void
    {
        $article = $this->article(ArticleStatus::UnderReview);
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();
        $author = User::factory()->author()->createOne();

        $this->actingAs($reviewer)->get(route('admin.articles.index'))->assertForbidden();
        $this->actingAs($reviewer)
            ->post(route('admin.articles.decision', $article->uuid), ['decision' => 'accept'])
            ->assertForbidden();
        $this->actingAs($author)
            ->post(route('admin.articles.start-review', $article->uuid))
            ->assertForbidden();
    }
}

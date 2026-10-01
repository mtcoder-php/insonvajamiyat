<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\User;
use App\Services\Articles\ArticleFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Muallif kabineti: dashboard, "Mening maqolalarim", maqola sahifasi,
 * qaytarib olish va fayl yuklab olish (faqat o'z maqolalari).
 */
class AuthorArticlesTest extends TestCase
{
    use RefreshDatabase;

    private function author(): User
    {
        return User::factory()->author()->createOne();
    }

    private function articleFor(User $user, ArticleStatus $status = ArticleStatus::UnderReview): Article
    {
        return Article::factory()->status($status)->createOne([
            'submitter_id' => $user->id,
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function test_dashboard_shows_only_own_articles(): void
    {
        $author = $this->author();
        $this->articleFor($author);
        $this->articleFor($author, ArticleStatus::RevisionRequired);
        $this->articleFor($this->author());

        $this->actingAs($author)
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/Dashboard')
                ->has('articles', 2)
                ->where('cards.0.key', 'total')
                ->where('cards.0.value', 2)
                ->where('cards.2.value', 1)
                ->where('focus.article.status', ArticleStatus::RevisionRequired->value)
                ->has('focus.steps', 6)
                ->has('chart.submitted', 6)
            );
    }

    public function test_index_lists_own_and_coauthored_articles_with_filters(): void
    {
        $author = $this->author();
        $own = $this->articleFor($author);
        $this->articleFor($author, ArticleStatus::Published);

        $coauthored = $this->articleFor($this->author());
        ArticleAuthor::factory()->for($coauthored)->create(['user_id' => $author->id]);

        $this->articleFor($this->author());

        $this->actingAs($author)
            ->get(route('cabinet.articles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/articles/Index')
                ->has('articles.data', 3)
                ->where('counts.all', 3)
                ->where('counts.reviewing', 2)
                ->where('counts.published', 1)
            );

        $this->actingAs($author)
            ->get(route('cabinet.articles.index', ['status' => 'published']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('articles.data', 1)
                ->where('filters.status', 'published')
            );

        $this->actingAs($author)
            ->get(route('cabinet.articles.index', ['search' => mb_substr($own->title, 0, 12)]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('articles.data.0.uuid', $own->uuid)
            );
    }

    public function test_author_can_view_own_article(): void
    {
        $author = $this->author();
        $article = $this->articleFor($author);

        $this->actingAs($author)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/articles/Show')
                ->where('article.uuid', $article->uuid)
                ->where('article.can.withdraw', false)
                ->has('steps', 6)
            );
    }

    public function test_author_cannot_view_someone_elses_article(): void
    {
        $article = $this->articleFor($this->author());

        $this->actingAs($this->author())
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertForbidden();
    }

    public function test_author_can_withdraw_submitted_article(): void
    {
        $author = $this->author();
        $article = $this->articleFor($author, ArticleStatus::Submitted);

        $this->actingAs($author)
            ->post(route('cabinet.articles.withdraw', $article->uuid), ['reason' => 'Boshqa jurnalga'])
            ->assertRedirect(route('cabinet.articles.show', $article->uuid));

        $article->refresh();
        $this->assertSame(ArticleStatus::Withdrawn, $article->status);
        $this->assertNotNull($article->withdrawn_at);
        $this->assertDatabaseHas('article_status_histories', [
            'article_id' => $article->id,
            'to_status' => ArticleStatus::Withdrawn->value,
            'changed_by' => $author->id,
            'comment' => 'Boshqa jurnalga',
        ]);
    }

    public function test_author_cannot_withdraw_article_under_review_or_foreign(): void
    {
        $author = $this->author();
        $underReview = $this->articleFor($author);
        $foreign = $this->articleFor($this->author(), ArticleStatus::Submitted);

        $this->actingAs($author)
            ->post(route('cabinet.articles.withdraw', $underReview->uuid))
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('cabinet.articles.withdraw', $foreign->uuid))
            ->assertForbidden();

        $this->assertSame(ArticleStatus::Submitted, $foreign->refresh()->status);
    }

    public function test_files_are_downloadable_only_by_article_authors(): void
    {
        Storage::fake('local');

        $author = $this->author();
        $article = $this->articleFor($author);
        $file = app(ArticleFileService::class)->store(
            $article,
            UploadedFile::fake()->create('maqola.docx', 120),
            ArticleFileType::Manuscript,
            $author,
        );

        $this->actingAs($author)
            ->get(route('cabinet.articles.files.download', [$article->uuid, $file->uuid]))
            ->assertOk()
            ->assertDownload('maqola.docx');

        $this->actingAs($this->author())
            ->get(route('cabinet.articles.files.download', [$article->uuid, $file->uuid]))
            ->assertForbidden();

        // Boshqa maqola orqali faylga murojaat — topilmaydi
        $other = $this->articleFor($author);
        $this->actingAs($author)
            ->get(route('cabinet.articles.files.download', [$other->uuid, $file->uuid]))
            ->assertNotFound();
    }
}

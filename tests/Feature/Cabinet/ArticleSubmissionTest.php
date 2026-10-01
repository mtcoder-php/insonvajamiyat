<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ArticleFileType;
use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVersionType;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Yangi maqola yuborish formasi (7 bosqich): qoralama, bosqichlarni saqlash,
 * fayllar, yuborish va ruxsatlar.
 */
class ArticleSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private const ABSTRACT = 'Maqolada XIX asr oxiri — XX asr boshlarida Turkiston o\'lkasida ijtimoiy-iqtisodiy jarayonlar arxiv manbalari asosida tahlil qilingan va yangi xulosalar berilgan.';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function author(): User
    {
        return User::factory()->author()->createOne();
    }

    private function type(int $price = 0): ArticleType
    {
        return ArticleType::factory()->createOne(['price' => $price, 'is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return [
            'article_type_id' => $this->type()->id,
            'subject_id' => Subject::factory()->createOne()->id,
            'language' => 'uz',
            'title' => 'Turkiston o\'lkasida ijtimoiy-iqtisodiy jarayonlar',
            'udc' => '94(575.1)',
            ...$overrides,
        ];
    }

    private function draftFor(User $user, int $price = 0): Article
    {
        $this->actingAs($user)->post(route('cabinet.articles.store'), $this->details([
            'article_type_id' => $this->type($price)->id,
        ]));

        return Article::query()->where('submitter_id', $user->id)->latest('id')->firstOrFail();
    }

    /**
     * Qoralamaning 2–5-bosqichlarini to'liq to'ldiradi.
     */
    private function complete(User $user, Article $article): void
    {
        $this->actingAs($user)->put(route('cabinet.articles.abstract.update', $article->uuid), [
            'abstract' => ['uz' => self::ABSTRACT, 'en' => 'English abstract'],
            'title' => ['en' => 'Socio-economic processes in Turkestan'],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->put(route('cabinet.articles.keywords.update', $article->uuid), [
            'keywords' => ['uz' => ['Turkiston', 'arxiv', 'iqtisodiyot']],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('cabinet.articles.files.store', $article->uuid), [
            'type' => ArticleFileType::Manuscript->value,
            'file' => UploadedFile::fake()->create('maqola.docx', 200),
        ])->assertSessionHasNoErrors();
    }

    public function test_create_page_renders_wizard_with_options(): void
    {
        $this->type();
        Subject::factory()->createOne();

        $this->actingAs($this->author())
            ->get(route('cabinet.articles.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/articles/Wizard')
                ->where('step', 1)
                ->where('article', null)
                ->has('steps', 7)
                ->has('options.types', 1)
                ->has('options.subjects', 1)
                ->has('options.languages', 3)
            );
    }

    public function test_first_step_creates_draft_with_submitter_as_author(): void
    {
        $user = $this->author();

        $response = $this->actingAs($user)->post(route('cabinet.articles.store'), $this->details());

        $article = Article::query()->firstOrFail();
        $response->assertRedirect(route('cabinet.articles.edit', ['article' => $article->uuid, 'step' => 2]));

        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertSame($user->id, $article->submitter_id);
        $this->assertSame('Turkiston o\'lkasida ijtimoiy-iqtisodiy jarayonlar', $article->getTranslation('title', 'uz'));
        $this->assertDatabaseHas('article_authors', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'is_corresponding' => true,
        ]);
        $this->assertDatabaseHas('article_status_histories', [
            'article_id' => $article->id,
            'from_status' => null,
            'to_status' => ArticleStatus::Draft->value,
        ]);
    }

    public function test_first_step_is_validated(): void
    {
        $this->actingAs($this->author())
            ->post(route('cabinet.articles.store'), $this->details([
                'title' => 'Qisqa',
                'subject_id' => null,
                'article_type_id' => ArticleType::factory()->createOne(['is_active' => false])->id,
            ]))
            ->assertSessionHasErrors(['title', 'subject_id', 'article_type_id']);

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_stay_keeps_current_step(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('cabinet.articles.details.update', $article->uuid), [...$this->details(), 'stay' => true])
            ->assertRedirect(route('cabinet.articles.edit', ['article' => $article->uuid, 'step' => 1]));
    }

    public function test_authors_are_synced_and_coauthor_is_linked_by_email(): void
    {
        $user = $this->author();
        $coauthor = User::factory()->author()->createOne(['email' => 'hammuallif@example.uz']);
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('cabinet.articles.authors.update', $article->uuid), [
                'authors' => [
                    ['last_name' => 'Olimov', 'first_name' => 'Bobur', 'email' => 'Hammuallif@example.uz', 'organization' => 'NUU', 'is_me' => false],
                    ['last_name' => 'Karimov', 'first_name' => 'Anvar', 'email' => $user->email, 'organization' => 'YAU', 'is_me' => true],
                ],
                'corresponding' => 1,
            ])
            ->assertRedirect(route('cabinet.articles.edit', ['article' => $article->uuid, 'step' => 3]));

        $authors = $article->authors()->get();

        $this->assertCount(2, $authors);
        $this->assertSame($coauthor->id, $authors[0]->user_id);
        $this->assertSame('hammuallif@example.uz', $authors[0]->email);
        $this->assertSame($user->id, $authors[1]->user_id);
        $this->assertTrue($authors[1]->is_corresponding);

        // Hammuallif maqolani o'z kabinetida ko'radi
        $this->actingAs($coauthor)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertOk();
    }

    public function test_corresponding_author_requires_email(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('cabinet.articles.authors.update', $article->uuid), [
                'authors' => [
                    ['last_name' => 'Olimov', 'first_name' => 'Bobur', 'email' => null, 'organization' => 'NUU'],
                ],
                'corresponding' => 0,
            ])
            ->assertSessionHasErrors(['authors.0.email']);
    }

    public function test_abstract_is_required_in_article_language(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('cabinet.articles.abstract.update', $article->uuid), [
                'abstract' => ['uz' => 'Juda qisqa', 'ru' => self::ABSTRACT],
            ])
            ->assertSessionHasErrors(['abstract.uz']);
    }

    public function test_keywords_are_normalized_and_validated(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('cabinet.articles.keywords.update', $article->uuid), [
                'keywords' => ['uz' => ['Tarix', ' tarix ', 'Arxiv']],
            ])
            ->assertSessionHasErrors(['keywords.uz']);

        $this->actingAs($user)
            ->put(route('cabinet.articles.keywords.update', $article->uuid), [
                'keywords' => ['uz' => ['Tarix', ' tarix ', 'Arxiv', '  Turkiston  o\'lkasi '], 'en' => ['History']],
            ])
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(['Tarix', 'Arxiv', 'Turkiston o\'lkasi'], $article->getTranslation('keywords', 'uz'));
        $this->assertSame(['History'], $article->getTranslation('keywords', 'en'));
    }

    public function test_manuscript_upload_replaces_previous_and_rejects_wrong_type(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        foreach (['v1.docx', 'v2.pdf'] as $name) {
            $this->actingAs($user)
                ->post(route('cabinet.articles.files.store', $article->uuid), [
                    'type' => 'manuscript',
                    'file' => UploadedFile::fake()->create($name, 100),
                ])
                ->assertSessionHasNoErrors();
        }

        $files = $article->files()->get();
        $this->assertCount(1, $files);
        $this->assertSame('v2.pdf', $files[0]->original_name);
        Storage::disk('local')->assertExists($files[0]->path);

        $this->actingAs($user)
            ->post(route('cabinet.articles.files.store', $article->uuid), [
                'type' => 'manuscript',
                'file' => UploadedFile::fake()->create('virus.exe', 10),
            ])
            ->assertSessionHasErrors(['file']);

        $this->actingAs($user)
            ->post(route('cabinet.articles.files.store', $article->uuid), [
                'type' => 'final_pdf',
                'file' => UploadedFile::fake()->create('final.pdf', 10),
            ])
            ->assertSessionHasErrors(['type']);
    }

    public function test_supplementary_file_can_be_deleted(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)->post(route('cabinet.articles.files.store', $article->uuid), [
            'type' => 'supplementary',
            'file' => UploadedFile::fake()->create('jadval.xlsx', 50),
        ]);
        $file = $article->files()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('cabinet.articles.files.destroy', [$article->uuid, $file->uuid]))
            ->assertRedirect();

        $this->assertDatabaseMissing('article_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_incomplete_draft_cannot_be_submitted(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);

        $this->actingAs($user)
            ->post(route('cabinet.articles.submit', $article->uuid), [
                'consents' => ['originality' => true, 'exclusivity' => true, 'rules' => true],
            ])
            ->assertSessionHasErrors(['submit']);

        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);
    }

    public function test_consents_are_required(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);
        $this->complete($user, $article);

        $this->actingAs($user)
            ->post(route('cabinet.articles.submit', $article->uuid), ['consents' => ['originality' => true]])
            ->assertSessionHasErrors(['consents.exclusivity', 'consents.rules']);
    }

    public function test_free_article_is_submitted_to_editorial_queue(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);
        $this->complete($user, $article);

        $this->actingAs($user)
            ->get(route('cabinet.articles.edit', ['article' => $article->uuid, 'step' => 7]))
            ->assertInertia(fn (Assert $page) => $page->where('isComplete', true));

        $this->actingAs($user)
            ->post(route('cabinet.articles.submit', $article->uuid), [
                'consents' => ['originality' => '1', 'exclusivity' => '1', 'rules' => '1'],
            ])
            ->assertRedirect(route('cabinet.articles.show', $article->uuid));

        $article->refresh();
        $this->assertSame(ArticleStatus::Submitted, $article->status);
        $this->assertSame(ArticlePaymentStatus::Waived, $article->payment_status);
        $this->assertNotNull($article->submitted_at);

        $version = $article->versions()->firstOrFail();
        $this->assertSame(ArticleVersionType::Submission, $version->type);
        $this->assertSame(1, $version->version_number);
        $this->assertSame(1, $article->files()->where('article_version_id', $version->id)->count());
    }

    public function test_paid_article_waits_for_payment(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user, 150000);
        $this->complete($user, $article);

        $this->actingAs($user)->post(route('cabinet.articles.submit', $article->uuid), [
            'consents' => ['originality' => true, 'exclusivity' => true, 'rules' => true],
        ]);

        $article->refresh();
        $this->assertSame(ArticleStatus::AwaitingPayment, $article->status);
        $this->assertSame(ArticlePaymentStatus::Unpaid, $article->payment_status);
        $this->assertSame(
            [ArticleStatus::Draft, ArticleStatus::Submitted, ArticleStatus::AwaitingPayment],
            $article->statusHistories()->get()->pluck('to_status')->all(),
        );
    }

    public function test_submitted_article_is_no_longer_editable(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);
        $this->complete($user, $article);
        $this->actingAs($user)->post(route('cabinet.articles.submit', $article->uuid), [
            'consents' => ['originality' => true, 'exclusivity' => true, 'rules' => true],
        ]);

        $this->actingAs($user)
            ->get(route('cabinet.articles.edit', $article->uuid))
            ->assertRedirect(route('cabinet.articles.show', $article->uuid));

        $this->actingAs($user)
            ->put(route('cabinet.articles.details.update', $article->uuid), $this->details())
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('cabinet.articles.destroy', $article->uuid))
            ->assertForbidden();
    }

    public function test_other_users_cannot_touch_a_draft(): void
    {
        $article = $this->draftFor($this->author());
        $stranger = $this->author();

        $this->actingAs($stranger)
            ->get(route('cabinet.articles.edit', $article->uuid))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->put(route('cabinet.articles.details.update', $article->uuid), $this->details())
            ->assertForbidden();

        $this->actingAs($stranger)
            ->post(route('cabinet.articles.files.store', $article->uuid), [
                'type' => 'manuscript',
                'file' => UploadedFile::fake()->create('x.docx', 10),
            ])
            ->assertForbidden();

        $this->actingAs($stranger)
            ->delete(route('cabinet.articles.destroy', $article->uuid))
            ->assertForbidden();
    }

    public function test_draft_can_be_deleted_with_files(): void
    {
        $user = $this->author();
        $article = $this->draftFor($user);
        $this->actingAs($user)->post(route('cabinet.articles.files.store', $article->uuid), [
            'type' => 'manuscript',
            'file' => UploadedFile::fake()->create('maqola.docx', 100),
        ]);
        $path = $article->files()->firstOrFail()->path;

        $this->actingAs($user)
            ->delete(route('cabinet.articles.destroy', $article->uuid))
            ->assertRedirect(route('cabinet.articles.index'));

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        Storage::disk('local')->assertMissing($path);
    }
}

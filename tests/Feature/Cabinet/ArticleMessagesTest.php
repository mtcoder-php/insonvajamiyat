<?php

namespace Tests\Feature\Cabinet;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Muallif ↔ tahririyat yozishmasi: yuborish, bildirishnoma, o'qilgan belgisi, fayl, ruxsatlar.
 */
class ArticleMessagesTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Article $article;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $this->author = User::factory()->author()->createOne();
        $this->article = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $this->author->id,
            'submitted_at' => now()->subDay(),
        ]);
        $this->article->forceFill(['handling_editor_id' => $this->editor->id])->save();
    }

    public function test_author_writes_and_editor_is_notified(): void
    {
        Notification::fake();

        $this->actingAs($this->author)
            ->post(route('cabinet.articles.messages.store', $this->article->uuid), [
                'body' => "Assalomu alaykum, maqolam qachon ko'rib chiqiladi?",
                'attachment' => UploadedFile::fake()->create('izoh.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $message = Message::query()->firstOrFail();
        $this->assertSame(MessageChannel::AuthorEditor, $message->channel);
        $this->assertSame('izoh.pdf', $message->attachment_name);
        Storage::disk('local')->assertExists((string) $message->attachment_path);

        Notification::assertSentTo($this->editor, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::MESSAGE && $n->toStaff);
        Notification::assertNotSentTo($this->author, ArticleUpdateNotification::class);
    }

    public function test_editor_reads_and_replies_author_sees_editorial_label(): void
    {
        $this->article->messages()->create([
            'channel' => MessageChannel::AuthorEditor,
            'sender_id' => $this->author->id,
            'body' => 'Savol',
        ]);

        // Muharrir maqolani ochadi — muallif xabari o'qilgan bo'ladi
        $this->actingAs($this->editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $this->article->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('selected.messages', 1)
                ->where('selected.messages.0.side', 'author')
                ->where('selected.can.message', true)
            );

        $this->assertNotNull(Message::query()->firstOrFail()->read_at);

        $this->actingAs($this->editor)
            ->post(route('admin.articles.messages.store', $this->article->uuid), ['body' => 'Javob: 2 hafta ichida.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(1, $this->author->unreadNotifications()->count());

        $this->actingAs($this->author)
            ->get(route('cabinet.articles.show', $this->article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->has('messages.items', 2)
                ->where('messages.items.0.mine', true)
                ->where('messages.items.0.readAt', fn (?string $v): bool => $v !== null)
                ->where('messages.items.1.sender', 'Tahririyat')
                ->where('messages.items.1.side', 'editorial')
                ->where('messages.sendUrl', route('cabinet.articles.messages.store', $this->article->uuid))
                // Yozishmada xodim ismi ko'rinmaydi — faqat "Tahririyat"
                ->where('messages.items', fn ($items): bool => ! str_contains((string) json_encode($items), $this->editor->name))
            );

        // Kabinetni ochish bildirishnomani va xabarni o'qilgan qiladi
        $this->assertSame(0, $this->author->unreadNotifications()->count());
        $this->assertSame(0, Message::query()->whereNull('read_at')->count());
    }

    public function test_dashboard_shows_unread_editorial_message(): void
    {
        $this->article->messages()->create([
            'channel' => MessageChannel::AuthorEditor,
            'sender_id' => $this->editor->id,
            'body' => 'Tahririyatdan xabar',
        ]);

        $this->actingAs($this->author)
            ->get(route('cabinet.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('messages.0.message', 'Tahririyatdan xabar')
                ->where('messages.0.unread', true)
            );
    }

    public function test_attachment_is_available_only_to_participants(): void
    {
        $this->actingAs($this->author)
            ->post(route('cabinet.articles.messages.store', $this->article->uuid), [
                'body' => 'Fayl ilova qilindi',
                'attachment' => UploadedFile::fake()->create('ilova.pdf', 20, 'application/pdf'),
            ]);

        $message = Message::query()->firstOrFail();
        $url = route('cabinet.articles.messages.attachment', [$this->article->uuid, $message->id]);

        $this->actingAs($this->author)->get($url)->assertOk();
        $this->actingAs($this->editor)->get($url)->assertOk();
        $this->actingAs(User::factory()->author()->createOne())->get($url)->assertForbidden();
    }

    public function test_permissions(): void
    {
        // Begona muallif
        $this->actingAs(User::factory()->author()->createOne())
            ->post(route('cabinet.articles.messages.store', $this->article->uuid), ['body' => 'Salom'])
            ->assertForbidden();

        // Taqrizchida muallif bilan yozishish ruxsati yo'q
        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->post(route('admin.articles.messages.store', $this->article->uuid), ['body' => 'Salom'])
            ->assertForbidden();

        // Qoralamada yozishma yo'q
        $draft = Article::factory()->createOne(['submitter_id' => $this->author->id]);
        $this->actingAs($this->author)
            ->post(route('cabinet.articles.messages.store', $draft->uuid), ['body' => 'Salom'])
            ->assertForbidden();

        // Bo'sh xabar
        $this->actingAs($this->author)
            ->post(route('cabinet.articles.messages.store', $this->article->uuid), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, Message::query()->count());
    }
}

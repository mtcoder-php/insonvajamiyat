<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Broadcast;
use App\Models\Message;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Xabarlar: yozishmalar markazi (o'qilmaganlar, filtrlar, javob), ommaviy xabar,
 * bildirishnomalar va sidebar raqami.
 */
class MessageCenterTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $author, ?User $editor = null): Article
    {
        return Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $author->id,
            'handling_editor_id' => $editor?->id,
            'submitted_at' => now()->subDay(),
        ]);
    }

    private function message(Article $article, User $sender, string $body, bool $read = false): Message
    {
        $message = $article->messages()->create([
            'channel' => MessageChannel::AuthorEditor,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        if ($read) {
            $message->forceFill(['read_at' => now()])->save();
        }

        return $message;
    }

    public function test_editor_sees_conversations_filters_and_reads_thread(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $author = User::factory()->author()->createOne(['name' => 'Karimova Aziza']);
        $other = User::factory()->author()->createOne(['name' => 'Aliyev Bobur']);

        $mine = $this->article($author, $editor);
        $foreign = $this->article($other);
        $answered = $this->article($other);

        $this->message($mine, $author, 'Maqolam qachon ko\'rib chiqiladi?');
        $this->message($mine, $author, 'Fayl yangiladim.');
        $this->message($foreign, $other, 'Salom!');
        $this->message($answered, $other, 'Rahmat', read: true);
        $this->message($answered, $editor, 'Arzimaydi');

        $this->actingAs($editor)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/messages/Index')
                ->where('tabs', ['messages', 'notifications'])
                ->where('counts', ['all' => 3, 'unread' => 2, 'mine' => 1])
                ->has('conversations.data', 3)
                ->where('conversations.data.0.uuid', $answered->uuid)
                ->where('conversations.data.0.unread', 0)
                ->where('conversations.data.0.last.mine', true)
                ->where('thread', null)
                ->where('adminBadges.messages', 3)
            );

        $this->actingAs($editor)
            ->get(route('admin.messages.index', ['scope' => 'unread']))
            ->assertInertia(fn (Assert $page) => $page->has('conversations.data', 2));

        $this->actingAs($editor)
            ->get(route('admin.messages.index', ['scope' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('conversations.data', 1)
                ->where('conversations.data.0.unread', 2)
                ->where('conversations.data.0.isMine', true)
            );

        $this->actingAs($editor)
            ->get(route('admin.messages.index', ['q' => 'karimova']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('conversations.data', 1)
                ->where('conversations.data.0.uuid', $mine->uuid)
            );

        // Yozishmani ochish — muallif xabarlari o'qilgan bo'ladi
        $this->actingAs($editor)
            ->get(route('admin.messages.index', ['article' => $mine->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('thread.uuid', $mine->uuid)
                ->where('thread.author', 'Karimova Aziza')
                ->has('thread.items', 2)
                ->where('thread.sendUrl', route('admin.articles.messages.store', $mine->uuid))
                ->where('counts.unread', 1)
            );

        $this->assertSame(0, Message::query()->where('article_id', $mine->id)->whereNull('read_at')->count());

        // Javob yozish (mavjud yo'nalish)
        $this->actingAs($editor)
            ->post(route('admin.articles.messages.store', $mine->uuid), ['body' => 'Bir hafta ichida javob beramiz.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('messages', ['article_id' => $mine->id, 'sender_id' => $editor->id]);

        // Muallif admin bo'limiga kira olmaydi; kontent menejerda ruxsat yo'q
        $this->actingAs($author)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs(User::factory()->withRole(RoleName::ContentManager)->createOne())
            ->get(route('admin.messages.index'))
            ->assertForbidden();
    }

    public function test_admin_sends_broadcast_to_selected_audience(): void
    {
        Notification::fake();

        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $authors = User::factory()->author()->count(3)->create();
        User::factory()->author()->blocked()->createOne();
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();

        $this->actingAs($admin)
            ->get(route('admin.messages.index', ['tab' => 'broadcast']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'broadcast')
                ->where('audiences.0.value', 'authors')
                ->where('audiences.0.count', 3)
                ->has('broadcasts', 0)
            );

        $this->actingAs($admin)
            ->post(route('admin.messages.broadcasts.store'), [
                'audience' => 'authors',
                'subject' => 'Qisqa',
                'body' => 'kam',
                'send_email' => true,
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($admin)
            ->post(route('admin.messages.broadcasts.store'), [
                'audience' => 'authors',
                'subject' => 'Navbatdagi son uchun maqolalar qabuli',
                'body' => "Hurmatli mualliflar!\n\nMaqolalar 15-noyabrgacha qabul qilinadi.",
                'send_email' => true,
            ])
            ->assertSessionHasNoErrors();

        $broadcast = Broadcast::query()->firstOrFail();
        $this->assertSame(Broadcast::SENT, $broadcast->status);
        $this->assertSame(3, $broadcast->recipients_count);
        $this->assertSame(3, $broadcast->sent_count);
        $this->assertNotNull($broadcast->sent_at);

        Notification::assertSentTo($authors, BroadcastNotification::class, function (BroadcastNotification $n, array $channels): bool {
            return in_array('mail', $channels, true) && in_array('database', $channels, true);
        });
        Notification::assertNotSentTo($reviewer, BroadcastNotification::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.broadcast']);

        // Muharrirda users.manage yo'q — tab ham, yuborish ham yopiq
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $this->actingAs($editor)
            ->get(route('admin.messages.index', ['tab' => 'broadcast']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.tab', 'messages')->where('urls.broadcast', null));
        $this->actingAs($editor)
            ->post(route('admin.messages.broadcasts.store'), ['audience' => 'authors', 'subject' => 'Test xabar', 'body' => 'Bu test xabar matni.', 'send_email' => false])
            ->assertForbidden();
    }

    public function test_broadcast_notification_payload_and_staff_notifications_tab(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $author = User::factory()->author()->createOne();

        $this->actingAs($admin)
            ->post(route('admin.messages.broadcasts.store'), [
                'audience' => 'all',
                'subject' => 'Saytda texnik ishlar',
                'body' => 'Shanba kuni 22:00 dan 23:00 gacha sayt ishlamaydi.',
                'send_email' => false,
            ])
            ->assertSessionHasNoErrors();

        $notification = $author->notifications()->firstOrFail();
        $this->assertSame('broadcast', $notification->data['kind']);
        $this->assertSame('Saytda texnik ishlar', $notification->data['title']);

        $this->actingAs($admin)
            ->get(route('admin.messages.index', ['tab' => 'notifications']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'notifications')
                ->has('notificationsPage.data', 1)
                ->where('notificationsPage.data.0.kind', 'broadcast')
            );
    }
}

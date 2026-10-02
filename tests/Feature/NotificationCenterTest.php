<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Bildirishnomalar: header'dagi qo'ng'iroqcha, o'qish, tahririyatga xabarlar
 * va muallif kabinetidagi "Xabarlar" sahifasi.
 */
class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, Article $article, string $title, string $kind = ArticleUpdateNotification::DECISION): void
    {
        $user->notify(new ArticleUpdateNotification($article, $kind, $title, 'Izoh matni'));
    }

    public function test_header_receives_unread_count_and_latest_items(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne();

        $this->notify($editor, $article, 'Birinchi');
        $this->notify($editor, $article, 'Ikkinchi', ArticleUpdateNotification::MESSAGE);

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.unread', 2)
                ->has('notifications.items', 2)
                ->where('notifications.items.0.read', false)
                ->has('notifications.items.0.openUrl')
                ->where('notifications.items.0.articleTitle', $article->title)
            );
    }

    public function test_opening_notification_marks_read_and_redirects(): void
    {
        $author = User::factory()->author()->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne(['submitter_id' => $author->id]);
        $this->notify($author, $article, 'Qaror');

        $notification = $author->notifications()->firstOrFail();

        $this->actingAs($author)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('cabinet.articles.show', $article->uuid));

        $this->assertNotNull($notification->refresh()->read_at);

        // Boshqa foydalanuvchining bildirishnomasi — ochilmaydi
        $other = User::factory()->author()->createOne();
        $this->notify($other, $article, 'Begona');
        $foreign = $other->notifications()->firstOrFail();

        $this->actingAs($author)
            ->from(route('cabinet.dashboard'))
            ->post(route('notifications.read', $foreign->id))
            ->assertRedirect(route('cabinet.dashboard'));
        $this->assertNull($foreign->refresh()->read_at);

        $this->notify($author, $article, 'Yana bir');
        $this->actingAs($author)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $author->unreadNotifications()->count());
    }

    public function test_only_internal_urls_are_followed(): void
    {
        $this->assertSame('/cabinet', NotificationCenter::safeUrl('/cabinet'));
        $this->assertNull(NotificationCenter::safeUrl('//evil.example.com'));
        $this->assertNull(NotificationCenter::safeUrl('https://evil.example.com/x'));
        $this->assertSame(config('app.url').'/admin', NotificationCenter::safeUrl(config('app.url').'/admin'));
    }

    public function test_editors_are_notified_about_new_submission(): void
    {
        Notification::fake();

        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $chief = User::factory()->withRole(RoleName::ChiefEditor)->createOne();
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();
        $article = Article::factory()->createOne(['status' => ArticleStatus::Draft]);

        app(ArticleWorkflow::class)->transition($article, ArticleStatus::Submitted, $article->submitter);

        $isSubmitted = fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::SUBMITTED && $n->toStaff;
        Notification::assertSentTo($editor, ArticleUpdateNotification::class, $isSubmitted);
        Notification::assertSentTo($chief, ArticleUpdateNotification::class, $isSubmitted);
        Notification::assertNotSentTo($reviewer, ArticleUpdateNotification::class);
    }

    public function test_reviewer_gets_invitation_and_editor_gets_response(): void
    {
        Notification::fake();

        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne(['submitted_at' => now()->subDay()]);

        $this->actingAs($editor)
            ->post(route('admin.articles.reviewers.store', $article->uuid), [
                'reviewer_ids' => [$reviewer->id],
                'due_days' => 10,
            ])
            ->assertSessionHasNoErrors();

        $review = Review::query()->firstOrFail();

        Notification::assertSentTo($reviewer, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::REVIEW
            && $n->url() === route('admin.reviews.show', $review->id));

        $this->actingAs($reviewer)
            ->post(route('admin.reviews.accept', $review->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReviewStatus::Accepted, $review->refresh()->status);
        Notification::assertSentTo($editor, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::REVIEW);
    }

    public function test_author_messages_page_lists_conversations_and_opens_thread(): void
    {
        $author = User::factory()->author()->createOne();
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $first = Article::factory()->status(ArticleStatus::UnderReview)->createOne(['submitter_id' => $author->id, 'submitted_at' => now()->subWeek()]);
        $second = Article::factory()->status(ArticleStatus::Accepted)->createOne(['submitter_id' => $author->id, 'submitted_at' => now()->subMonth()]);
        Article::factory()->createOne(['submitter_id' => $author->id, 'status' => ArticleStatus::Draft]); // qoralama — ro'yxatda yo'q

        Message::query()->create([
            'article_id' => $second->id,
            'channel' => MessageChannel::AuthorEditor,
            'sender_id' => $editor->id,
            'body' => 'Maqolangiz qabul qilindi, tabriklaymiz!',
        ]);
        $this->notify($author, $second, 'Tahririyatdan yangi xabar', ArticleUpdateNotification::MESSAGE);

        $this->actingAs($author)
            ->get(route('cabinet.messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/Messages')
                ->has('conversations', 2)
                // Xabari bor yozishma birinchi
                ->where('conversations.0.uuid', $second->uuid)
                ->where('conversations.0.last.fromEditorial', true)
                ->where('conversations.1.uuid', $first->uuid)
                // Birinchi yozishma avtomatik ochiladi va o'qiladi
                ->where('thread.uuid', $second->uuid)
                ->where('thread.items.0.sender', 'Tahririyat')
                ->where('thread.sendUrl', route('cabinet.articles.messages.store', $second->uuid))
                ->where('conversations.0.unread', 0)
                ->where('unreadMessages', 0)
            );

        $this->assertNotNull(Message::query()->firstOrFail()->read_at);
        $this->assertSame(0, $author->unreadNotifications()->count());

        // Begona maqola ochilmaydi
        $foreign = Article::factory()->status(ArticleStatus::UnderReview)->createOne();
        $this->actingAs($author)
            ->get(route('cabinet.messages.index', ['article' => $foreign->uuid]))
            ->assertInertia(fn (Assert $page) => $page->where('thread', null));

        $this->notify($author, $first, 'Qaror');
        $this->actingAs($author)
            ->get(route('cabinet.messages.index', ['tab' => 'notifications', 'unread' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'notifications')
                ->where('thread', null)
                ->where('notificationsPage.meta.total', 1)
                ->where('notificationsPage.data.0.title', 'Qaror')
            );
    }
}

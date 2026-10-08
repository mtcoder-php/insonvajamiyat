<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * To'lov eslatmalari (TZ 4.2.8): avtomatik jadval (3, 7, 14 kun), qo'lda yuborish, sutkalik cheklov.
 */
class PaymentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['journal.payment_reminders' => ['days' => [3, 7, 14], 'cooldown_hours' => 24]]);
    }

    private function awaiting(int $daysAgo, array $overrides = []): Article
    {
        return Article::factory()->status(ArticleStatus::AwaitingPayment)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'article_type_id' => ArticleType::factory()->createOne(['price' => 150000])->id,
            'payment_status' => ArticlePaymentStatus::Unpaid,
            'submitted_at' => now()->subDays($daysAgo),
            ...$overrides,
        ]);
    }

    public function test_scheduled_reminders_follow_the_day_schedule_once_each(): void
    {
        $fresh = $this->awaiting(1);
        $due = $this->awaiting(4);
        $paid = $this->awaiting(10, ['payment_status' => ArticlePaymentStatus::Paid]);

        $this->artisan('app:payment-reminders')->assertSuccessful();

        Notification::assertSentTo($due->submitter, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::PAYMENT_REMINDER
            && str_contains((string) $n->body, '150 000')
            && str_ends_with($n->url(), '#payment'));
        Notification::assertNotSentTo($fresh->submitter, ArticleUpdateNotification::class);
        Notification::assertNotSentTo($paid->submitter, ArticleUpdateNotification::class);

        $due->refresh();
        $this->assertSame(1, $due->payment_reminders_count);
        $this->assertNotNull($due->payment_reminded_at);

        // Ertasi kuni — 2-eslatma hali emas (7-kun kerak)
        $this->travel(1)->days();
        $this->artisan('app:payment-reminders');
        $this->assertSame(1, $due->fresh()->payment_reminders_count);

        // 7-kun — ikkinchisi; 14-kun — uchinchisi; keyin to'xtaydi
        $this->travel(2)->days();
        $this->artisan('app:payment-reminders');
        $this->assertSame(2, $due->fresh()->payment_reminders_count);

        $this->travel(7)->days();
        $this->artisan('app:payment-reminders');
        $this->assertSame(3, $due->fresh()->payment_reminders_count);

        $this->travel(10)->days();
        $this->artisan('app:payment-reminders');
        $this->assertSame(3, $due->fresh()->payment_reminders_count);
        Notification::assertSentToTimes($due->submitter, ArticleUpdateNotification::class, 3);

        $this->assertTrue(AuditLog::query()->where('event', 'payment.reminded')->exists());
    }

    public function test_admin_sends_manual_reminder_with_daily_cooldown(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $article = $this->awaiting(2);

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'awaiting']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('awaiting.data.0.reminders.count', 0)
                ->where('awaiting.data.0.reminders.availableAt', null)
                ->where('reminderDays', [3, 7, 14])
                ->has('awaiting.data.0.urls.remind')
            );

        $this->actingAs($admin)->post(route('admin.payments.remind', $article->uuid))->assertRedirect();
        Notification::assertSentToTimes($article->submitter, ArticleUpdateNotification::class, 1);

        // Sutka ichida takror — yuborilmaydi
        $this->actingAs($admin)->post(route('admin.payments.remind', $article->uuid));
        Notification::assertSentToTimes($article->submitter, ArticleUpdateNotification::class, 1);

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'awaiting']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('awaiting.data.0.reminders.count', 1)
                ->where('awaiting.data.0.reminders.availableAt', fn ($at) => is_string($at))
            );

        // "Hammasiga": yangi maqola — ha, yaqinda eslatilgani — yo'q
        $other = $this->awaiting(5);
        $this->actingAs($admin)->post(route('admin.payments.remind-all'))->assertRedirect();
        Notification::assertSentToTimes($other->submitter, ArticleUpdateNotification::class, 1);
        Notification::assertSentToTimes($article->submitter, ArticleUpdateNotification::class, 1);

        // To'lov kutmayotgan maqola
        $published = Article::factory()->status(ArticleStatus::UnderReview)->createOne();
        $this->actingAs($admin)->post(route('admin.payments.remind', $published->uuid));
        Notification::assertNotSentTo($published->submitter, ArticleUpdateNotification::class);

        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->post(route('admin.payments.remind', $article->uuid))
            ->assertForbidden();
    }
}

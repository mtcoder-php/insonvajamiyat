<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\Newsletter\NewsletterCampaignNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Admin → Obuna: ro'yxat, xat yuborish (faqat tasdiqlanganlarga, til bo'yicha), eksport, o'chirish.
 */
class NewsletterAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    private function subscriber(string $email, string $locale = 'uz', string $state = 'confirmed'): NewsletterSubscriber
    {
        $subscriber = new NewsletterSubscriber([
            'email' => $email,
            'locale' => $locale,
            'token' => str_pad(md5($email), 64, 'x'),
        ]);
        $subscriber->forceFill([
            'confirmed_at' => $state === 'pending' ? null : now()->subDay(),
            'unsubscribed_at' => $state === 'unsubscribed' ? now() : null,
        ])->save();

        return $subscriber;
    }

    public function test_index_shows_stats_audiences_and_filtered_subscribers(): void
    {
        $this->subscriber('a@example.com', 'uz');
        $this->subscriber('b@example.com', 'ru');
        $this->subscriber('c@example.com', 'uz', 'pending');
        $this->subscriber('d@example.com', 'en', 'unsubscribed');

        $this->actingAs($this->admin)
            ->get(route('admin.newsletter.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/newsletter/Index')
                ->where('tab', 'compose')
                ->where('stats', ['confirmed' => 2, 'pending' => 1, 'unsubscribed' => 1, 'campaigns' => 0])
                ->where('audiences.0.count', 2)
                ->where('audiences.1.value', 'uz')
                ->where('audiences.1.count', 1)
                ->where('autoIssue', true)
                ->has('subscribers.data', 4)
            );

        $this->actingAs($this->admin)
            ->get(route('admin.newsletter.index', ['tab' => 'subscribers', 'status' => 'pending']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'subscribers')
                ->has('subscribers.data', 1)
                ->where('subscribers.data.0.email', 'c@example.com')
                ->where('subscribers.data.0.state', 'pending')
            );

        $this->actingAs($this->admin)
            ->get(route('admin.newsletter.index', ['q' => 'B@EX']))
            ->assertInertia(fn (Assert $page) => $page->has('subscribers.data', 1)->where('subscribers.data.0.email', 'b@example.com'));
    }

    public function test_campaign_is_sent_only_to_confirmed_subscribers_of_selected_language(): void
    {
        Notification::fake();
        $uz = $this->subscriber('uz@example.com', 'uz');
        $this->subscriber('ru@example.com', 'ru');
        $this->subscriber('pending@example.com', 'uz', 'pending');
        $this->subscriber('gone@example.com', 'uz', 'unsubscribed');

        $this->actingAs($this->admin)
            ->post(route('admin.newsletter.campaigns.store'), [
                'audience' => 'uz',
                'subject' => 'Maqolalar qabuli',
                'body' => "Hurmatli o'quvchilar!\n\nNavbatdagi son uchun maqolalar qabuli boshlandi.",
                'button_label' => 'Yo\'riqnoma',
                'button_url' => 'https://insonvajamiyat.uz/guidelines',
            ])
            ->assertSessionHasNoErrors();

        $campaign = NewsletterCampaign::sole();
        $this->assertSame('uz', $campaign->locale);
        $this->assertSame(1, $campaign->recipients_count);
        $this->assertSame(NewsletterCampaign::SENT, $campaign->fresh()?->status);
        $this->assertSame(1, $campaign->fresh()?->sent_count);

        Notification::assertSentOnDemandTimes(NewsletterCampaignNotification::class, 1);
        Notification::assertSentOnDemand(
            NewsletterCampaignNotification::class,
            function (NewsletterCampaignNotification $n, array $channels, AnonymousNotifiable $notifiable) use ($uz): bool {
                $mail = $n->toMail($notifiable);
                $text = implode("\n", [...$mail->introLines, ...$mail->outroLines]);

                $email = new Email;
                foreach ($mail->callbacks as $callback) {
                    $callback($email);
                }

                return $notifiable->routes['mail'] === 'uz@example.com'
                    && $mail->subject === 'Maqolalar qabuli'
                    && $mail->actionUrl === 'https://insonvajamiyat.uz/guidelines'
                    && str_contains($text, $uz->unsubscribeUrl())
                    && str_contains((string) $email->getHeaders()->get('List-Unsubscribe')?->getBodyAsString(), $uz->oneClickUnsubscribeUrl())
                    && $email->getHeaders()->get('List-Unsubscribe-Post')?->getBodyAsString() === 'List-Unsubscribe=One-Click';
            },
        );
    }

    public function test_unsubscribed_while_queued_does_not_receive_mail(): void
    {
        $subscriber = $this->subscriber('late@example.com');
        $campaign = new NewsletterCampaign(['subject' => 'Test', 'body' => 'Matn matn matn']);
        $campaign->save();

        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        $this->assertFalse((new NewsletterCampaignNotification($campaign, $subscriber))->shouldSend(new AnonymousNotifiable, 'mail'));
    }

    public function test_campaign_without_recipients_is_rejected(): void
    {
        $this->subscriber('pending@example.com', 'uz', 'pending');

        $this->actingAs($this->admin)
            ->post(route('admin.newsletter.campaigns.store'), [
                'audience' => 'all',
                'subject' => 'Salom',
                'body' => 'Yangi son tez orada chiqadi.',
            ])
            ->assertSessionHasErrors('audience');

        $this->assertSame(0, NewsletterCampaign::count());
    }

    public function test_button_url_must_be_http(): void
    {
        $this->subscriber('a@example.com');

        $this->actingAs($this->admin)
            ->post(route('admin.newsletter.campaigns.store'), [
                'audience' => 'all',
                'subject' => 'Salom',
                'body' => 'Yangi son tez orada chiqadi.',
                'button_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('button_url');
    }

    public function test_auto_issue_setting_is_toggled(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.newsletter.settings'), ['auto_issue' => false])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->get(route('admin.newsletter.index'))
            ->assertInertia(fn (Assert $page) => $page->where('autoIssue', false));
    }

    public function test_subscribers_are_exported_as_csv_and_can_be_deleted(): void
    {
        $this->subscriber('a@example.com');
        $pending = $this->subscriber('p@example.com', 'ru', 'pending');

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.newsletter.subscribers.export', ['status' => 'pending']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('p@example.com,ru,pending', $csv);
        $this->assertStringNotContainsString('a@example.com', $csv);

        $this->actingAs($this->admin)
            ->delete(route('admin.newsletter.subscribers.destroy', $pending))
            ->assertRedirect();

        $this->assertModelMissing($pending);
    }

    public function test_requires_content_manage_permission(): void
    {
        $author = User::factory()->author()->createOne();
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->createOne();

        $this->actingAs($author)->get(route('admin.newsletter.index'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('admin.newsletter.index'))->assertForbidden();
        $this->actingAs($reviewer)->post(route('admin.newsletter.campaigns.store'), [])->assertForbidden();
    }
}

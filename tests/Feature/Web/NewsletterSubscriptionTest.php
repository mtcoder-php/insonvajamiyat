<?php

namespace Tests\Feature\Web;

use App\Models\NewsletterSubscriber;
use App\Notifications\Newsletter\ConfirmSubscriptionNotification;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_subscription_sends_confirmation_email()
    {
        Notification::fake();

        $this->from(route('home'))
            ->post(route('newsletter.subscribe'), ['email' => 'Reader@Example.com'])
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $subscriber = NewsletterSubscriber::sole();

        $this->assertSame('reader@example.com', $subscriber->email);
        $this->assertSame(64, strlen($subscriber->token));
        $this->assertNull($subscriber->confirmed_at);
        $this->assertNotNull($subscriber->confirmation_sent_at);
        $this->assertFalse($subscriber->isConfirmed());

        Notification::assertSentOnDemand(
            ConfirmSubscriptionNotification::class,
            function (ConfirmSubscriptionNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($subscriber) {
                $mail = $notification->toMail($notifiable);

                return $notifiable->routes['mail'] === 'reader@example.com'
                    && $mail->actionUrl === route('newsletter.confirm', $subscriber->token);
            },
        );
    }

    public function test_confirmation_is_not_resent_within_ten_minutes()
    {
        Notification::fake();

        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        Notification::assertSentOnDemandTimes(ConfirmSubscriptionNotification::class, 1);

        $this->travel(11)->minutes();
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        Notification::assertSentOnDemandTimes(ConfirmSubscriptionNotification::class, 2);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_confirm_link_activates_subscription()
    {
        Notification::fake();

        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
        $subscriber = NewsletterSubscriber::sole();

        $this->get(route('newsletter.confirm', $subscriber->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/newsletter/Status')
                ->where('state', 'confirmed')
            );

        $this->assertTrue($subscriber->fresh()->isConfirmed());
        $this->assertSame(1, NewsletterSubscriber::query()->confirmed()->count());

        // Faol obunachi qayta obuna bo'lsa — xat yuborilmaydi
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
        Notification::assertSentOnDemandTimes(ConfirmSubscriptionNotification::class, 1);
    }

    public function test_invalid_token_shows_invalid_state()
    {
        $this->get(route('newsletter.confirm', str_repeat('x', 64)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('state', 'invalid'));

        $this->get(route('newsletter.unsubscribe', 'short'))
            ->assertInertia(fn (Assert $page) => $page->where('state', 'invalid'));
    }

    public function test_unsubscribe_requires_button_press_and_hides_email()
    {
        $subscriber = $this->confirmedSubscriber();

        // GET faqat so'raydi (havolani tekshiruvchi skanerlar chiqarib yubormasin)
        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('state', 'ask')
                ->where('token', $subscriber->token)
                ->where('email', 'r•••r@example.com')
            );
        $this->assertTrue($subscriber->fresh()->isConfirmed());

        $this->post(route('newsletter.unsubscribe.store', $subscriber->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('state', 'unsubscribed'));

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
        $this->assertSame(0, NewsletterSubscriber::query()->confirmed()->count());

        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertInertia(fn (Assert $page) => $page->where('state', 'unsubscribed')->where('token', null));
    }

    public function test_one_click_unsubscribe_works_without_csrf_token()
    {
        $subscriber = $this->confirmedSubscriber();

        $this->post(route('newsletter.unsubscribe.one-click', $subscriber->token), ['List-Unsubscribe' => 'One-Click'])
            ->assertNoContent();

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        // Testlarda CSRF o'chiq — marshrut darajasida chiqarib tashlanganini alohida tekshiramiz
        $this->assertContains(
            PreventRequestForgery::class,
            Route::getRoutes()->getByName('newsletter.unsubscribe.one-click')?->excludedMiddleware() ?? [],
        );
    }

    public function test_resubscribing_after_unsubscribe_requires_new_confirmation()
    {
        Notification::fake();

        $subscriber = $this->confirmedSubscriber();
        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        $this->post(route('newsletter.subscribe'), ['email' => 'READER@example.com'])
            ->assertSessionHasNoErrors();

        $subscriber->refresh();
        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertTrue($subscriber->isActive());
        $this->assertNull($subscriber->confirmed_at);
        Notification::assertSentOnDemandTimes(ConfirmSubscriptionNotification::class, 1);
    }

    public function test_unconfirmed_subscribers_are_pruned_after_thirty_days()
    {
        Notification::fake();

        $this->post(route('newsletter.subscribe'), ['email' => 'old@example.com']);
        $this->confirmedSubscriber();

        $this->travel(31)->days();
        $this->artisan('model:prune', ['--model' => [NewsletterSubscriber::class]])->assertSuccessful();

        $this->assertSame(['reader@example.com'], NewsletterSubscriber::query()->pluck('email')->all());
    }

    public function test_invalid_email_is_rejected()
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_shared_journal_contact_is_available_on_public_pages()
    {
        config()->set('journal.contact.email', 'test@insonvajamiyat.uz');
        config()->set('journal.socials', ['telegram' => 'https://t.me/test', 'facebook' => null]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('journal.contact.email', 'test@insonvajamiyat.uz')
                ->where('journal.socials', ['telegram' => 'https://t.me/test'])
                ->where('notifications', null)
            );
    }

    private function confirmedSubscriber(): NewsletterSubscriber
    {
        $subscriber = new NewsletterSubscriber([
            'email' => 'reader@example.com',
            'locale' => 'uz',
            'token' => str_repeat('a', 64),
        ]);
        $subscriber->forceFill(['confirmed_at' => now()])->save();

        return $subscriber;
    }
}

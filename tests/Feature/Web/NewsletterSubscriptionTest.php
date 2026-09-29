<?php

namespace Tests\Feature\Web;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe()
    {
        $this->from(route('home'))
            ->post(route('newsletter.subscribe'), ['email' => 'Reader@Example.com'])
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $subscriber = NewsletterSubscriber::sole();

        $this->assertSame('reader@example.com', $subscriber->email);
        $this->assertSame(64, strlen($subscriber->token));
        $this->assertNull($subscriber->unsubscribed_at);
    }

    public function test_repeated_subscription_does_not_duplicate_and_reactivates()
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        NewsletterSubscriber::sole()->forceFill(['unsubscribed_at' => now()])->save();

        $this->post(route('newsletter.subscribe'), ['email' => 'READER@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertTrue(NewsletterSubscriber::sole()->isActive());
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
}

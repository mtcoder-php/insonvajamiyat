<?php

namespace Tests\Feature\Web;

use App\Enums\PostType;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ommaviy yangiliklar (/news) va tadbirlar (/events) sahifalari.
 */
class NewsAndEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_index_lists_published_posts_and_filters_by_type(): void
    {
        Post::factory()->create(['title' => ['uz' => 'Yangilik'], 'type' => PostType::News]);
        Post::factory()->announcement()->create(['title' => ['uz' => "E'lon"]]);
        Post::factory()->create(['title' => ['uz' => 'Qoralama'], 'is_published' => false]);
        Post::factory()->create(['title' => ['uz' => 'Kelajakdagi'], 'published_at' => now()->addDay()]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/news/Index')
                ->has('posts.data', 2)
                ->where('posts.meta.total', 2)
            );

        $this->get(route('news.index', ['type' => 'announcement']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', "E'lon")
                ->where('type', 'announcement')
            );
    }

    public function test_news_show_page_and_hidden_posts(): void
    {
        $post = Post::factory()->create(['slug' => 'yangi-son', 'body' => ['uz' => "To'liq matn"]]);
        Post::factory()->create();

        $this->get(route('news.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/news/Show')
                ->where('post.slug', 'yangi-son')
                ->where('post.body', '<p>To&#039;liq matn</p>')
                ->has('others', 1)
            );

        $draft = Post::factory()->create(['slug' => 'qoralama', 'is_published' => false]);
        $this->get(route('news.show', $draft))->assertNotFound();

        $future = Post::factory()->create(['slug' => 'kelajak', 'published_at' => now()->addDay()]);
        $this->get(route('news.show', $future))->assertNotFound();
    }

    public function test_events_index_splits_upcoming_and_past(): void
    {
        Event::factory()->create(['title' => ['uz' => 'Kelgusi'], 'starts_at' => now()->addDays(5)]);
        Event::factory()->create(['title' => ['uz' => "O'tgan"], 'starts_at' => now()->subDays(5)]);
        Event::factory()->create(['is_published' => false]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/events/Index')
                ->has('upcoming', 1)
                ->where('upcoming.0.title', 'Kelgusi')
                ->has('past', 1)
                ->where('past.0.title', "O'tgan")
            );
    }

    public function test_event_show_page(): void
    {
        $event = Event::factory()->create(['slug' => 'forum', 'description' => ['uz' => 'Tavsif']]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/events/Show')
                ->where('event.slug', 'forum')
                ->where('event.description', '<p>Tavsif</p>')
                ->where('event.isPast', false)
            );

        $hidden = Event::factory()->create(['slug' => 'yashirin', 'is_published' => false]);
        $this->get(route('events.show', $hidden))->assertNotFound();
    }

    public function test_home_cards_link_to_detail_pages(): void
    {
        $post = Post::factory()->create(['slug' => 'xabar']);
        $event = Event::factory()->create(['slug' => 'tadbir']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('news.0.url', route('news.show', $post))
                ->where('events.0.url', route('events.show', $event))
            );
    }
}

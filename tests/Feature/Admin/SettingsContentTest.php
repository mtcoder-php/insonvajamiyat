<?php

namespace Tests\Feature\Admin;

use App\Enums\PostType;
use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Post;
use App\Models\RecommendedBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Sozlamalar (2-qism): yangiliklar, tadbirlar, tavsiya etilgan kitoblar, hamkorlar.
 */
class SettingsContentTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->manager = User::factory()->withRole(RoleName::ContentManager)->createOne();
    }

    public function test_content_manager_sees_new_tabs_and_others_are_forbidden(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'partners']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tabs', ['subjects', 'pages', 'board', 'documents', 'banners', 'posts', 'events', 'books', 'partners'])
                ->where('tab', 'partners')
                ->has('partners', 0)
                ->has('partnerTypes', 2)
                ->where('posts', null)
            );

        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->post(route('admin.settings.posts.store'), [])
            ->assertForbidden();
    }

    public function test_post_is_created_with_image_kept_slug_and_soft_deleted(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.settings.posts.store'), [
                'type' => 'announcement',
                'title' => ['uz' => '', 'ru' => 'Объявление'],
                'is_published' => true,
                'is_pinned' => false,
            ])
            ->assertSessionHasErrors('title.uz');

        $this->actingAs($this->manager)
            ->post(route('admin.settings.posts.store'), [
                'type' => 'announcement',
                'title' => ['uz' => 'Maqolalar qabuli boshlandi', 'ru' => '', 'en' => 'Call for papers'],
                'excerpt' => ['uz' => 'Navbatdagi son uchun'],
                // Rasmli forma (multipart) — brauzer qator oxirlarini \r\n qilib yuboradi
                'body' => ['uz' => "Birinchi xatboshi.\r\n\r\nIkkinchi xatboshi."],
                'is_published' => true,
                'is_pinned' => true,
                'published_at' => '',
                'image' => UploadedFile::fake()->image('cover.jpg', 1200, 600),
            ])
            ->assertSessionHasNoErrors();

        $post = Post::query()->where('slug', 'maqolalar-qabuli-boshlandi')->firstOrFail();
        $this->assertSame(PostType::Announcement, $post->type);
        $this->assertSame("Birinchi xatboshi.\n\nIkkinchi xatboshi.", $post->getTranslation('body', 'uz'));
        $this->assertSame($this->manager->id, $post->author_id);
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->image_path);
        Storage::disk('public')->assertExists($post->image_path);

        $this->get(route('news.show', $post->slug))->assertOk();

        // Sarlavha o'zgarsa ham slug o'zgarmaydi; rasm olib tashlanadi; kelajak sanasi — rejalashtirilgan
        $image = $post->image_path;
        $this->actingAs($this->manager)
            ->post(route('admin.settings.posts.update', $post->id), [
                '_method' => 'put',
                'type' => 'announcement',
                'title' => ['uz' => 'Maqolalar qabuli davom etmoqda'],
                'is_published' => true,
                'is_pinned' => false,
                'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'remove_image' => true,
            ])
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('maqolalar-qabuli-boshlandi', $post->slug);
        $this->assertNull($post->image_path);
        Storage::disk('public')->assertMissing($image);
        $this->get(route('news.show', $post->slug))->assertNotFound();

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'posts', 'type' => 'announcement', 'q' => 'davom']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.status', 'scheduled')
                ->where('posts.counts.announcement', 1)
                ->where('filters.type', 'announcement')
            );

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'posts', 'type' => 'news']))
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));

        $this->actingAs($this->manager)->delete(route('admin.settings.posts.destroy', $post->id))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($post);
        $this->assertDatabaseHas('audit_logs', ['event' => 'content.deleted']);
    }

    public function test_event_dates_are_validated_and_listed_by_period(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.settings.events.store'), [
                'title' => ['uz' => 'Konferensiya'],
                'starts_at' => '2026-11-10T09:00',
                'ends_at' => '2026-11-09T09:00',
                'registration_url' => 'javascript:alert(1)',
                'is_published' => true,
            ])
            ->assertSessionHasErrors(['ends_at', 'registration_url']);

        $this->actingAs($this->manager)
            ->post(route('admin.settings.events.store'), [
                'title' => ['uz' => 'Xalqaro konferensiya'],
                'location' => ['uz' => 'Toshkent'],
                'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'ends_at' => '',
                'registration_url' => 'https://forms.gle/abc',
                'is_published' => true,
                'image' => UploadedFile::fake()->image('poster.png', 1000, 600),
            ])
            ->assertSessionHasNoErrors();

        $event = Event::query()->where('slug', 'xalqaro-konferensiya')->firstOrFail();
        $this->assertSame('https://forms.gle/abc', $event->registration_url);
        $this->assertNotNull($event->image_path);

        $this->get(route('events.show', $event->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->whereNot('event.imageUrl', null));

        Event::factory()->createOne(['starts_at' => now()->subMonths(2), 'ends_at' => null]);

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'events', 'when' => 'past']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.isPast', true)
            );

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'events', 'when' => 'upcoming']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.title', 'Xalqaro konferensiya')
            );

        $this->actingAs($this->manager)->delete(route('admin.settings.events.destroy', $event->id))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($event);
    }

    public function test_book_cover_is_replaced_and_removed_with_book(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.settings.books.store'), [
                'title' => ['uz' => 'Ilmiy tadqiqot metodologiyasi'],
                'author' => 'A. Karimov',
                'year' => 2023,
                'url' => 'https://example.uz/book',
                'is_active' => true,
                'sort_order' => 1,
                'cover' => UploadedFile::fake()->image('book.jpg', 400, 560),
            ])
            ->assertSessionHasNoErrors();

        $book = RecommendedBook::query()->firstOrFail();
        $first = (string) $book->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->manager)
            ->post(route('admin.settings.books.update', $book->id), [
                '_method' => 'put',
                'title' => ['uz' => 'Ilmiy tadqiqot metodologiyasi'],
                'author' => 'A. Karimov',
                'year' => 2024,
                'is_active' => true,
                'sort_order' => 1,
                'cover' => UploadedFile::fake()->image('book2.png', 400, 560),
            ])
            ->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertSame(2024, $book->year);
        $this->assertNull($book->url);
        Storage::disk('public')->assertMissing($first);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->has('books', 1));

        $this->actingAs($this->manager)->delete(route('admin.settings.books.destroy', $book->id))->assertSessionHasNoErrors();
        $this->assertModelMissing($book);
        Storage::disk('public')->assertMissing((string) $book->cover_image_path);
    }

    public function test_partners_are_managed_and_shown_on_home_page(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.settings.partners.store'), [
                'type' => 'indexing',
                'name' => ['uz' => 'Google Scholar'],
                'subtitle' => ['uz' => 'Indekslangan'],
                'url' => 'https://scholar.google.com',
                'is_active' => true,
                'sort_order' => 0,
                'logo' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('logo');

        $this->actingAs($this->manager)
            ->post(route('admin.settings.partners.store'), [
                'type' => 'indexing',
                'name' => ['uz' => 'Google Scholar'],
                'subtitle' => ['uz' => 'Indekslangan'],
                'url' => 'https://scholar.google.com',
                'is_active' => true,
                'sort_order' => 0,
                'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            ])
            ->assertSessionHasNoErrors();

        Partner::factory()->createOne(['is_active' => false]);
        $partner = Partner::query()->where('type', 'indexing')->firstOrFail();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('partners', 1)
                ->where('partners.0.name', 'Google Scholar')
                ->where('partners.0.subtitle', 'Indekslangan')
                ->whereNot('partners.0.logoUrl', null)
            );

        $logo = (string) $partner->logo_path;
        $this->actingAs($this->manager)->delete(route('admin.settings.partners.destroy', $partner->id))->assertSessionHasNoErrors();
        $this->assertModelMissing($partner);
        Storage::disk('public')->assertMissing($logo);
    }
}

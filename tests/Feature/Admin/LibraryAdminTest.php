<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Book;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Kutubxona: ro'yxat va filtrlar, kitob qo'shish (muqova + PDF), tahrirlash,
 * "Tavsiya etilgan"/"Faol" belgilari, o'chirish (fayllar bilan), ruxsatlar.
 */
class LibraryAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->manager = User::factory()->withRole(RoleName::ContentManager)->createOne();
    }

    public function test_index_lists_books_with_filters_and_stats(): void
    {
        $subject = Subject::factory()->createOne(['name' => ['uz' => 'Tarix']]);
        Book::factory()->featured()->create(['title' => ['uz' => 'Tarix metodologiyasi'], 'subject_id' => $subject->id]);
        Book::factory()->inactive()->create(['title' => ['uz' => 'Falsafa asoslari'], 'downloads_count' => 7]);

        $this->actingAs($this->manager)
            ->get(route('admin.library.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/library/Index')
                ->has('books.data', 2)
                ->where('stats.total', 2)
                ->where('stats.featured', 1)
                ->where('stats.downloads', 7)
                ->has('subjects', 1)
            );

        $this->actingAs($this->manager)
            ->get(route('admin.library.index', ['status' => 'hidden']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('books.data', 1)
                ->where('books.data.0.title', 'Falsafa asoslari')
                ->where('books.data.0.isActive', false)
            );

        $this->actingAs($this->manager)
            ->get(route('admin.library.index', ['q' => 'METODOLOG', 'subject' => $subject->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('books.data', 1)
                ->where('books.data.0.subject', 'Tarix')
                ->where('books.data.0.isFeatured', true)
            );
    }

    public function test_book_is_created_updated_and_deleted_with_files(): void
    {
        $subject = Subject::factory()->createOne();

        $this->actingAs($this->manager)
            ->post(route('admin.library.store'), [
                'title' => ['uz' => 'Ilmiy tadqiqot metodologiyasi', 'ru' => 'Методология научных исследований'],
                'description' => ['uz' => 'Qisqacha annotatsiya'],
                'subject_id' => $subject->id,
                'author' => 'A. Karimov',
                'publisher' => 'Fan',
                'year' => 2023,
                'pages' => 240,
                'isbn' => '978-9943-11-222-3',
                'language' => 'uz',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 1,
                'cover' => UploadedFile::fake()->image('book.jpg', 400, 560),
                'file' => UploadedFile::fake()->create('kitob.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $book = Book::query()->firstOrFail();
        $this->assertSame('ilmiy-tadqiqot-metodologiyasi', $book->slug);
        $this->assertSame($subject->id, $book->subject_id);
        $this->assertTrue($book->is_featured);
        $this->assertSame('kitob.pdf', $book->file_name);
        $cover = (string) $book->cover_image_path;
        $pdf = (string) $book->file_path;
        Storage::disk('public')->assertExists($cover);
        Storage::disk('local')->assertExists($pdf);
        $this->assertStringStartsWith('library/', $pdf);

        // Bosh sahifadagi "Tavsiya etilgan kitoblar"
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->has('books', 1)
            ->where('books.0.title', 'Ilmiy tadqiqot metodologiyasi')
            ->where('books.0.hasFile', true)
        );

        // Yangi PDF eski faylni almashtiradi, muqova olib tashlanadi
        $this->actingAs($this->manager)
            ->post(route('admin.library.update', $book), [
                '_method' => 'put',
                'title' => ['uz' => 'Ilmiy tadqiqot metodologiyasi'],
                'author' => 'A. Karimov',
                'year' => 2024,
                'language' => 'ru',
                'url' => 'https://example.uz/book',
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 1,
                'remove_cover' => true,
                'file' => UploadedFile::fake()->create('yangi.pdf', 80, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertSame(2024, $book->year);
        $this->assertSame('ru', $book->language);
        $this->assertNull($book->cover_image_path);
        $this->assertSame('yangi.pdf', $book->file_name);
        Storage::disk('public')->assertMissing($cover);
        Storage::disk('local')->assertMissing($pdf);
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->has('books', 0));

        $this->actingAs($this->manager)->get(route('admin.library.file', $book))->assertOk();

        $newPdf = (string) $book->file_path;
        $this->actingAs($this->manager)->delete(route('admin.library.destroy', $book))->assertSessionHasNoErrors();
        $this->assertModelMissing($book);
        Storage::disk('local')->assertMissing($newPdf);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.library.store'), [
                'title' => ['uz' => ''],
                'author' => '',
                'isbn' => 'abc',
                'language' => 'de',
                'url' => 'javascript:alert(1)',
                'file' => UploadedFile::fake()->create('kitob.docx', 10, 'application/msword'),
            ])
            ->assertSessionHasErrors(['title.uz', 'author', 'isbn', 'language', 'url', 'file']);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_featured_and_active_flags_are_toggled(): void
    {
        $book = Book::factory()->createOne();

        $this->actingAs($this->manager)
            ->patch(route('admin.library.toggle', $book), ['field' => 'is_featured', 'value' => true])
            ->assertSessionHasNoErrors();
        $this->assertTrue($book->refresh()->is_featured);

        $this->actingAs($this->manager)
            ->patch(route('admin.library.toggle', $book), ['field' => 'is_active', 'value' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($book->refresh()->is_active);

        $this->actingAs($this->manager)
            ->patch(route('admin.library.toggle', $book), ['field' => 'author', 'value' => true])
            ->assertSessionHasErrors('field');
    }

    public function test_users_without_content_permission_are_forbidden(): void
    {
        $book = Book::factory()->createOne();
        $author = User::factory()->withRole(RoleName::Author)->createOne();

        $this->actingAs($author)->get(route('admin.library.index'))->assertForbidden();
        $this->actingAs($author)->post(route('admin.library.store'), [])->assertForbidden();
        $this->actingAs($author)->delete(route('admin.library.destroy', $book))->assertForbidden();
        $this->assertModelExists($book);
    }
}

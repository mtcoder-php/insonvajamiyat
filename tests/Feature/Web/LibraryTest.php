<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Sayt → Kutubxona: ro'yxat (qidiruv, yo'nalish, til, saralash), kitob sahifasi,
 * PDF o'qish / yuklab olish hisoblagichlari, yashirin kitob.
 */
class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_active_books_with_featured_and_filters(): void
    {
        $history = Subject::factory()->createOne(['name' => ['uz' => 'Tarix'], 'slug' => 'tarix']);
        $philosophy = Subject::factory()->createOne(['name' => ['uz' => 'Falsafa'], 'slug' => 'falsafa']);

        Book::factory()->featured()->create(['title' => ['uz' => 'Amir Temur davri'], 'subject_id' => $history->id, 'language' => 'uz']);
        Book::factory()->create(['title' => ['uz' => 'Философия'], 'subject_id' => $philosophy->id, 'language' => 'ru', 'downloads_count' => 50]);
        Book::factory()->inactive()->create(['title' => ['uz' => 'Yashirin kitob']]);

        $this->get(route('library.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/library/Index')
                ->has('books.data', 2)
                ->where('books.data.0.title', 'Amir Temur davri')
                ->has('featured', 1)
                ->has('subjects', 2)
                ->where('stats.books', 2)
            );

        $this->get(route('library.index', ['subject' => 'falsafa']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('books.data', 1)
                ->where('books.data.0.subject.slug', 'falsafa')
                ->has('featured', 0)
            );

        $this->get(route('library.index', ['language' => 'ru']))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.language', 'ru'));

        $this->get(route('library.index', ['q' => 'temur']))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.title', 'Amir Temur davri'));

        $this->get(route('library.index', ['sort' => 'popular']))
            ->assertInertia(fn (Assert $page) => $page->where('books.data.0.title', 'Философия')->where('filters.sort', 'popular'));
    }

    public function test_book_page_counts_views_and_serves_pdf(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('k.pdf', 20, 'application/pdf')->storeAs('library', 'test.pdf', 'local');

        $subject = Subject::factory()->createOne();
        $book = Book::factory()->createOne([
            'title' => ['uz' => 'Ijtimoiy falsafa'],
            'subject_id' => $subject->id,
            'file_path' => $path,
            'file_name' => 'k.pdf',
            'file_size' => 20480,
            'url' => 'https://example.uz/book',
        ]);
        Book::factory()->create(['subject_id' => $subject->id]);
        Book::factory()->inactive()->create();

        $this->get(route('library.show', $book->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/library/Show')
                ->where('book.title', 'Ijtimoiy falsafa')
                ->where('book.views', 1)
                ->where('book.file.readUrl', route('library.read', $book->slug))
                ->where('book.externalUrl', 'https://example.uz/book')
                ->has('related', 1)
            );

        $this->assertSame(1, $book->refresh()->views_count);

        $this->get(route('library.read', $book->slug))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(0, $book->refresh()->downloads_count);

        $this->get(route('library.download', $book->slug))->assertOk()->assertDownload('ijtimoiy-falsafa.pdf');
        $this->assertSame(1, $book->refresh()->downloads_count);
    }

    public function test_hidden_book_and_missing_file_return_404(): void
    {
        $hidden = Book::factory()->inactive()->createOne();
        $noFile = Book::factory()->createOne();

        $this->get(route('library.show', $hidden->slug))->assertNotFound();
        $this->get(route('library.read', $hidden->slug))->assertNotFound();
        $this->get(route('library.download', $noFile->slug))->assertNotFound();
    }
}

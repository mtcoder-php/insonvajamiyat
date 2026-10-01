<?php

namespace Tests\Feature\Web;

use App\Models\RecommendedBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesFakeImages;
use Tests\TestCase;

/**
 * public/web/books/<kitob nomi>.png muqovalarini tavsiya etilgan kitoblarga biriktirish.
 */
class BookCoverImporterTest extends TestCase
{
    use CreatesFakeImages, RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->publicDir = sys_get_temp_dir().'/ivj-public-'.uniqid();
        File::ensureDirectoryExists($this->publicDir.'/web/books');
        $this->app->usePublicPath($this->publicDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    public function test_command_attaches_book_covers_by_title(): void
    {
        File::put($this->publicDir.'/web/books/Oʻrta Osiyo xalqlari etnologiyasi.png', $this->pngContents(60, 90));

        $book = RecommendedBook::factory()->create(['title' => ['uz' => "O'rta Osiyo xalqlari etnologiyasi"]]);
        $other = RecommendedBook::factory()->create(['title' => ['uz' => "O'zbek adabiyoti tarixi"]]);

        $this->artisan('app:import-book-covers')
            ->expectsOutputToContain('Biriktirildi: 1 ta kitob')
            ->expectsOutputToContain("O'zbek adabiyoti tarixi")
            ->assertSuccessful();

        $path = $book->refresh()->cover_image_path;
        $this->assertSame("books/covers/{$book->id}-orta-osiyo-xalqlari-etnologiyasi.png", $path);
        Storage::disk('public')->assertExists((string) $path);
        $this->assertNull($other->refresh()->cover_image_path);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('books', fn ($books) => collect($books)
                    ->contains(fn ($b) => str_ends_with((string) $b['coverUrl'], (string) $path)))
            );
    }

    public function test_existing_cover_is_kept_unless_forced(): void
    {
        File::put($this->publicDir.'/web/books/Tarix.png', $this->pngContents(60, 90));
        $book = RecommendedBook::factory()->create([
            'title' => ['uz' => 'Tarix'],
            'cover_image_path' => 'books/covers/eski.png',
        ]);

        $this->artisan('app:import-book-covers')->assertSuccessful();
        $this->assertSame('books/covers/eski.png', $book->refresh()->cover_image_path);

        $this->artisan('app:import-book-covers', ['--force' => true])->assertSuccessful();
        $this->assertSame("books/covers/{$book->id}-tarix.png", $book->refresh()->cover_image_path);
    }
}

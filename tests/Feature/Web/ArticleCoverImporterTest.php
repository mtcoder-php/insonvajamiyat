<?php

namespace Tests\Feature\Web;

use App\Models\Article;
use App\Services\Web\ArticleCoverImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesFakeImages;
use Tests\TestCase;

/**
 * public/web/article/<sarlavha>.png rasmlarini maqolalarga biriktirish.
 */
class ArticleCoverImporterTest extends TestCase
{
    use CreatesFakeImages, RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->publicDir = sys_get_temp_dir().'/ivj-public-'.uniqid();
        File::ensureDirectoryExists($this->publicDir.'/web/article');
        $this->app->usePublicPath($this->publicDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    private function putSource(string $name): void
    {
        // Vaqtinchalik UploadedFile emas — baytlar to'g'ridan-to'g'ri diskka yoziladi
        File::put($this->publicDir.'/web/article/'.$name, $this->pngContents(120, 80));
    }

    public function test_command_attaches_images_by_title_ignoring_apostrophe_style(): void
    {
        // Fayl nomida jingalak apostrof (‘), sarlavhada oddiy (')
        $this->putSource('O‘rta asrlar davrida Amir Temur davlatining ijtimoiy-siyosiy tizimi.png');

        $matched = Article::factory()->published()->create([
            'title' => ['uz' => "O'rta asrlar davrida Amir Temur davlatining ijtimoiy-siyosiy tizimi"],
            'slug' => 'amir-temur',
        ]);
        $other = Article::factory()->published()->create(['title' => ['uz' => 'Rasmsiz maqola']]);
        Article::factory()->create(['title' => ['uz' => 'Jarayondagi maqola']]); // qoralama

        $this->artisan('app:import-article-covers')
            ->expectsOutputToContain('Biriktirildi: 1 ta')
            ->expectsOutputToContain('Rasmsiz maqola')
            ->expectsOutputToContain('Nashr etilmagan (jarayondagi) 1 ta')
            ->assertSuccessful();

        $this->assertSame('articles/covers/amir-temur.png', $matched->refresh()->cover_image_path);
        Storage::disk('public')->assertExists('articles/covers/amir-temur.png');
        $this->assertNull($other->refresh()->cover_image_path);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('latestArticles', fn ($articles) => collect($articles)
                    ->contains(fn ($a) => str_ends_with((string) $a['coverUrl'], 'articles/covers/amir-temur.png')))
            );
    }

    public function test_existing_cover_is_kept_unless_forced(): void
    {
        $this->putSource('Navoiy.png');
        $article = Article::factory()->published()->create([
            'title' => ['uz' => 'Navoiy'],
            'slug' => 'navoiy',
            'cover_image_path' => 'articles/covers/eski.png',
        ]);

        app(ArticleCoverImporter::class)->import();
        $this->assertSame('articles/covers/eski.png', $article->refresh()->cover_image_path);

        app(ArticleCoverImporter::class)->import(force: true);
        $this->assertSame('articles/covers/navoiy.png', $article->refresh()->cover_image_path);
    }

    public function test_normalize(): void
    {
        $this->assertSame(
            ArticleCoverImporter::normalize("Farg'ona  vodiysi."),
            ArticleCoverImporter::normalize('FARGʻONA vodiysi'),
        );
    }
}

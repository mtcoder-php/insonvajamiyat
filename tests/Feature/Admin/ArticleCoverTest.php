<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Maqola rasmi: nashr jarayoni sahifasidan yuklash, almashtirish, o'chirish.
 */
class ArticleCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_editor_uploads_replaces_and_removes_cover_of_published_article(): void
    {
        Storage::fake('public');
        $layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
        $article = Article::factory()->published()->createOne();

        $this->actingAs($layout)
            ->post(route('admin.production.cover', $article->uuid), [
                'cover' => UploadedFile::fake()->image('rasm.jpg', 1200, 800),
            ])
            ->assertSessionHasNoErrors();

        $first = (string) $article->refresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($layout)
            ->get(route('admin.production.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.can.cover', true)
                ->where('article.coverUrl', fn (?string $url): bool => $url !== null)
            );

        // Almashtirish — eski fayl o'chadi
        $this->actingAs($layout)
            ->post(route('admin.production.cover', $article->uuid), [
                'cover' => UploadedFile::fake()->image('yangi.png', 900, 600),
            ])
            ->assertSessionHasNoErrors();

        $second = (string) $article->refresh()->cover_image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        // Saytdagi maqola sahifasida ko'rinadi
        $this->get(route('articles.show', (string) $article->slug))
            ->assertInertia(fn (Assert $page) => $page->where('article.coverUrl', fn (?string $url): bool => str_contains((string) $url, basename($second))));

        $this->actingAs($layout)
            ->delete(route('admin.production.cover', $article->uuid))
            ->assertSessionHasNoErrors();

        $this->assertNull($article->refresh()->cover_image_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_cover_is_validated_and_protected(): void
    {
        Storage::fake('public');
        $article = Article::factory()->status(ArticleStatus::InProduction)->createOne();

        $this->actingAs(User::factory()->withRole(RoleName::LayoutEditor)->createOne())
            ->post(route('admin.production.cover', $article->uuid), [
                'cover' => UploadedFile::fake()->image('kichik.jpg', 300, 200),
            ])
            ->assertSessionHasErrors('cover');

        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->post(route('admin.production.cover', $article->uuid), [
                'cover' => UploadedFile::fake()->image('rasm.jpg', 1200, 800),
            ])
            ->assertForbidden();

        $this->assertNull($article->refresh()->cover_image_path);
    }
}

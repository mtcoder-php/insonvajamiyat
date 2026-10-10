<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\ContentImage;
use App\Models\Post;
use App\Models\User;
use App\Services\Content\ContentImageService;
use App\Support\Html\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Matn muharriri ichiga rasm: yuklash, xavfsizlik (faqat o'z rasmlarimiz), ishlatilmaganlarini tozalash.
 */
class ContentImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    public function test_image_is_uploaded_resized_and_reencoded(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.content-images.store'), [
                'image' => UploadedFile::fake()->image('konferensiya.jpg', 3200, 1600),
            ])
            ->assertCreated()
            ->assertJsonPath('width', ContentImageService::MAX_WIDTH)
            ->assertJsonPath('height', 800);

        $image = ContentImage::sole();
        $this->assertMatchesRegularExpression('~^content-images/\d{4}/\d{2}/[a-z0-9]{20}\.(webp|jpg)$~', $image->path);
        Storage::disk('public')->assertExists($image->path);
        $this->assertSame(Storage::disk('public')->url($image->path), $response->json('url'));
        $this->assertSame($this->admin->id, $image->user_id);
    }

    public function test_upload_is_validated_and_protected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.content-images.store'), [
                'image' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.content-images.store'), [
                'image' => UploadedFile::fake()->image('kichik.png', 50, 50),
            ])
            ->assertJsonValidationErrors('image');

        $this->actingAs(User::factory()->author()->createOne())
            ->postJson(route('admin.settings.content-images.store'), [
                'image' => UploadedFile::fake()->image('a.jpg', 800, 600),
            ])
            ->assertForbidden();

        $this->assertSame(0, ContentImage::count());
    }

    public function test_sanitizer_keeps_only_own_uploaded_images(): void
    {
        $own = Storage::disk('public')->url('content-images/2026/10/abcdefghij0123456789.webp');

        $html = RichText::sanitize(
            '<p>Matn</p><img src="'.$own.'" alt="Ishtirokchilar" onerror="alert(1)" width="9999">'
            .'<img src="https://evil.example/x.png" alt="x">'
            .'<img src="'.Storage::disk('public')->url('covers/abc.webp').'">'
            .'<img src="javascript:alert(1)">',
        );

        $this->assertSame(
            '<p>Matn</p><img src="'.$own.'" alt="Ishtirokchilar" loading="lazy" decoding="async">',
            $html,
        );
    }

    public function test_unused_images_are_pruned_after_a_day(): void
    {
        $service = app(ContentImageService::class);
        $used = $service->store(UploadedFile::fake()->image('a.jpg', 800, 600), $this->admin);
        $unused = $service->store(UploadedFile::fake()->image('b.jpg', 800, 600), $this->admin);
        $fresh = null;

        Post::factory()->create(['body' => ['uz' => '<p>Matn</p><img src="'.Storage::disk('public')->url($used->path).'" alt="">']]);

        $this->travel(25)->hours();
        $fresh = $service->store(UploadedFile::fake()->image('c.jpg', 800, 600), $this->admin);

        $this->artisan('app:prune-content-images')->assertSuccessful();

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
        $this->assertModelExists($fresh);
        Storage::disk('public')->assertExists($used->path);
        Storage::disk('public')->assertMissing($unused->path);
    }
}

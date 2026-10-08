<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Banner;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Sozlamalar: yo'nalishlar, maqola turlari va narxlar, bannerlar.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    public function test_tabs_follow_permissions(): void
    {
        $manager = User::factory()->withRole(RoleName::ContentManager)->createOne();

        $this->actingAs($manager)
            ->get(route('admin.settings.index', ['tab' => 'types']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/settings/Index')
                ->where('tab', 'subjects')
                ->where('tabs', ['subjects', 'pages', 'board', 'banners', 'posts', 'events', 'books', 'partners'])
                ->where('urls.types', null)
            );

        $this->actingAs($manager)
            ->post(route('admin.settings.types.store'), [])
            ->assertForbidden();

        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['tab' => 'types']))
            ->assertInertia(fn (Assert $page) => $page->where('tab', 'types')->has('types'));
    }

    public function test_subjects_are_created_translated_and_protected_from_deletion(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.settings.subjects.store'), [
                'name' => ['uz' => '', 'ru' => 'Этнология', 'en' => ''],
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('name.uz');

        $this->actingAs($admin)
            ->post(route('admin.settings.subjects.store'), [
                'name' => ['uz' => 'Madaniyatshunoslik', 'ru' => 'Культурология', 'en' => ''],
                'code' => '24.00.01',
                'is_active' => true,
                'sort_order' => 3,
            ])
            ->assertSessionHasNoErrors();

        $subject = Subject::query()->where('slug', 'madaniyatshunoslik')->firstOrFail();
        $this->assertSame('Культурология', $subject->getTranslation('name', 'ru'));
        $this->assertSame([], array_diff_key($subject->getTranslations('name'), ['uz' => 1, 'ru' => 1]));

        $this->actingAs($admin)
            ->put(route('admin.settings.subjects.update', $subject->id), [
                'name' => ['uz' => 'Madaniyatshunoslik', 'ru' => '', 'en' => 'Cultural studies'],
                'code' => '',
                'is_active' => false,
                'sort_order' => 5,
            ])
            ->assertSessionHasNoErrors();

        $subject->refresh();
        $this->assertFalse($subject->is_active);
        $this->assertNull($subject->code);
        $this->assertSame('Cultural studies', $subject->getTranslation('name', 'en'));

        Article::factory()->createOne(['subject_id' => $subject->id]);

        $this->actingAs($admin)
            ->delete(route('admin.settings.subjects.destroy', $subject->id))
            ->assertSessionHasErrors('subject');

        $empty = Subject::factory()->createOne();
        $this->actingAs($admin)->delete(route('admin.settings.subjects.destroy', $empty->id))->assertSessionHasNoErrors();
        $this->assertModelMissing($empty);
        $this->assertDatabaseHas('audit_logs', ['event' => 'content.deleted']);
    }

    public function test_article_type_price_change_is_audited_and_type_is_soft_deleted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.settings.types.store'), [
                'name' => ['uz' => 'Tezkor nashr'],
                'description' => ['uz' => '10 kunda ko\'rib chiqiladi'],
                'price' => 300000,
                'review_days' => 10,
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertSessionHasNoErrors();

        $type = ArticleType::query()->where('slug', 'tezkor_nashr')->firstOrFail();
        $this->assertSame(300000.0, (float) $type->price);

        $this->actingAs($admin)
            ->put(route('admin.settings.types.update', $type->id), [
                'name' => ['uz' => 'Tezkor nashr'],
                'price' => 350000,
                'review_days' => 10,
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(350000.0, (float) $type->refresh()->price);
        $this->assertDatabaseHas('audit_logs', ['event' => 'content.price']);

        $this->actingAs($admin)->delete(route('admin.settings.types.destroy', $type->id))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($type);
    }

    public function test_banners_are_uploaded_replaced_and_shown_on_home_page(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.settings.banners.store'), [
                'title' => ['uz' => 'Yangi son chiqdi'],
                'is_active' => true,
                'sort_order' => 0,
                'image' => UploadedFile::fake()->image('small.jpg', 600, 300),
            ])
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('admin.settings.banners.store'), [
                'title' => ['uz' => 'Yangi son chiqdi'],
                'button_text' => ['uz' => "Ko'rish"],
                'link_url' => '/issues',
                'is_active' => true,
                'sort_order' => 0,
                'image' => UploadedFile::fake()->image('banner.jpg', 1920, 720),
            ])
            ->assertSessionHasNoErrors();

        $banner = Banner::query()->firstOrFail();
        $first = $banner->image_path;
        Storage::disk('public')->assertExists($first);

        // Rasmsiz yangilash — rasm saqlanadi
        $this->actingAs($admin)
            ->post(route('admin.settings.banners.update', $banner->id), [
                '_method' => 'put',
                'title' => ['uz' => 'Yangi son chiqdi!'],
                'link_url' => 'javascript:alert(1)',
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('link_url');

        $this->actingAs($admin)
            ->post(route('admin.settings.banners.update', $banner->id), [
                '_method' => 'put',
                'title' => ['uz' => 'Yangi son chiqdi!'],
                'link_url' => '/issues',
                'is_active' => true,
                'sort_order' => 0,
                'image' => UploadedFile::fake()->image('new.png', 1600, 600),
            ])
            ->assertSessionHasNoErrors();

        $banner->refresh();
        $this->assertNotSame($first, $banner->image_path);
        Storage::disk('public')->assertMissing($first);

        $this->get(route('home'))->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'banners']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('banners', 1)
                ->where('banners.0.visible', true)
                ->where('banners.0.title', 'Yangi son chiqdi!')
            );

        $this->actingAs($admin)->delete(route('admin.settings.banners.destroy', $banner->id))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($banner->image_path);
        $this->assertModelMissing($banner);
    }
}

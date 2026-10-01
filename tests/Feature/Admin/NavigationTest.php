<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminSection;
use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin sidebar bo'limlari: route'lar, ruxsatlar va menyu raqamlari.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_every_section(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        foreach (AdminSection::cases() as $section) {
            // Tayyor bo'limlar o'z sahifasini ochadi (masalan, admin/users/Index)
            if ($section->isReady()) {
                $this->actingAs($admin)
                    ->get(route('admin.'.$section->routeKey().'.index'))
                    ->assertOk();

                continue;
            }

            $this->actingAs($admin)
                ->get(route('admin.'.$section->routeKey().'.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('admin/Section')
                    ->where('section.key', $section->value)
                    ->where('section.title', $section->title())
                );
        }
    }

    public function test_sections_are_protected_by_permissions(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->create();

        $this->actingAs($editor)->get(route('admin.articles.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.payments.index'))->assertOk();

        $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.system.index'))->assertForbidden();
    }

    public function test_authors_cannot_open_admin_sections(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)->get(route('admin.articles.index'))->assertForbidden();
    }

    public function test_admin_pages_share_sidebar_badges(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        Article::factory()->count(2)->status(ArticleStatus::Submitted)->create();
        Article::factory()->status(ArticleStatus::Accepted)->create();
        Payment::factory()->create(); // kutilmoqda

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminBadges.articles', 2)
                ->where('adminBadges.payments', 1)
                ->missing('adminBadges.issues')
            );
    }

    public function test_badges_respect_permissions(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->create();

        Article::factory()->status(ArticleStatus::Submitted)->create();

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminBadges.articles', 1)
                ->missing('adminBadges.ai')
            );
    }

    public function test_badges_are_not_computed_outside_admin(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('adminBadges', null));
    }
}

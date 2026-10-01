<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Web / Kabinet / Admin qismlarining ajratilganini tekshiradi.
 */
class AreaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_open_to_guests()
    {
        foreach (['home', 'about', 'articles.index', 'issues.index', 'guidelines', 'contact'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_home_page_renders_web_component()
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('web/Home')
                ->where('auth.user', null)
                ->where('auth.isStaff', false)
            );
    }

    public function test_guest_cannot_open_cabinet_or_admin()
    {
        $this->get(route('cabinet.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_author_can_open_cabinet()
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cabinet/Dashboard')
                ->where('auth.isStaff', false)
                ->where('auth.roles', ['author'])
                ->has('profileCompleted')
                ->has('cards', 5)
                ->has('chart.months', 6)
            );
    }

    public function test_author_cannot_open_admin_panel()
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_every_staff_role_can_open_admin_panel()
    {
        foreach (RoleName::staff() as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->actingAs($user)
                ->get(route('admin.dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('admin/Dashboard')
                    ->where('auth.isStaff', true)
                );
        }
    }

    public function test_super_admin_receives_wildcard_permissions()
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', ['*']));
    }

    public function test_unverified_user_cannot_open_cabinet()
    {
        $user = User::factory()->unverified()->author()->create();

        $this->actingAs($user)
            ->get(route('cabinet.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_blocked_user_is_logged_out_on_next_request()
    {
        $user = User::factory()->author()->create();

        $this->actingAs($user);
        $user->block('Qoidabuzarlik');

        $this->get(route('cabinet.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}

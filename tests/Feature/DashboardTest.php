<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /dashboard — rolga qarab yo'naltirish.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_author_is_redirected_to_cabinet()
    {
        $user = User::factory()->author()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('cabinet.dashboard'));
    }

    public function test_staff_is_redirected_to_admin_panel()
    {
        foreach (RoleName::staff() as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertRedirect(route('admin.dashboard'));
        }
    }
}

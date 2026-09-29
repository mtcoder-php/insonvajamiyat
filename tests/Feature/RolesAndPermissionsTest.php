<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_and_permissions_are_seeded()
    {
        $this->assertEqualsCanonicalizing(RoleName::values(), Role::pluck('name')->all());
        $this->assertEqualsCanonicalizing(PermissionName::values(), Permission::pluck('name')->all());
    }

    public function test_seeder_is_idempotent()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(RoleName::cases()), Role::count());
        $this->assertSame(count(PermissionName::cases()), Permission::count());
    }

    public function test_database_seeder_works_on_empty_database()
    {
        // `php artisan migrate --seed` holati: bo'sh baza + WithoutModelEvents
        Role::query()->delete();
        Permission::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(RoleName::cases()), Role::count());
        $this->assertSame(count(PermissionName::cases()), Permission::count());
        $this->assertTrue(
            Role::findByName(RoleName::Editor->value, 'web')->hasPermissionTo(PermissionName::ArticlesDecide->value)
        );
    }

    public function test_super_admin_passes_every_gate()
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        foreach (PermissionName::cases() as $permission) {
            $this->assertTrue($admin->can($permission->value), $permission->value);
        }
    }

    public function test_reviewer_can_only_review()
    {
        $reviewer = User::factory()->withRole(RoleName::Reviewer)->create();

        $this->assertTrue($reviewer->can(PermissionName::ReviewsSubmit->value));
        $this->assertFalse($reviewer->can(PermissionName::ArticlesDecide->value));
        $this->assertFalse($reviewer->can(PermissionName::UsersManage->value));
        $this->assertFalse($reviewer->can(PermissionName::PaymentsView->value));
    }

    public function test_author_has_no_admin_permissions()
    {
        $author = User::factory()->author()->create();

        $this->assertFalse($author->can(PermissionName::AdminAccess->value));
        $this->assertFalse($author->isStaff());
    }

    public function test_create_super_admin_command()
    {
        $this->artisan('app:create-super-admin', ['--email' => 'boss@example.com', '--name' => 'Bosh Admin'])
            ->expectsQuestion('Parol', 'Very-Strong-Pass-2026!')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.com')->firstOrFail();

        $this->assertTrue($user->isSuperAdmin());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_create_super_admin_rejects_duplicate_email()
    {
        User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('app:create-super-admin', ['--email' => 'boss@example.com', '--name' => 'Bosh Admin'])
            ->expectsQuestion('Parol', 'Very-Strong-Pass-2026!')
            ->assertFailed();
    }
}

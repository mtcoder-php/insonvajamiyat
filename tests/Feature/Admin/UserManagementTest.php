<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesFakeImages;
use Tests\TestCase;

/**
 * Admin panel — Foydalanuvchilar (TZ 4.2.4).
 */
class UserManagementTest extends TestCase
{
    use CreatesFakeImages, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'last_name' => 'Karimov',
            'first_name' => 'Ali',
            'middle_name' => null,
            'email' => 'ali@example.com',
            'phone' => '90 123 45 67',
            'locale' => 'uz',
            'roles' => [RoleName::Editor->value],
            'email_verified' => true,
            'organization' => 'Yangi Asr universiteti',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            ...$overrides,
        ];
    }

    public function test_index_lists_users_with_filters_and_counts(): void
    {
        $admin = $this->admin();
        User::factory()->author()->create(['name' => 'Rahimova Nilufar']);
        User::factory()->withRole(RoleName::Editor)->blocked()->create(['name' => 'Toshev Bek']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('users.data', 3)
                ->where('users.meta.total', 3)
                ->where('counts.total', 3)
                ->where('counts.staff', 2)
                ->where('counts.authors', 1)
                ->where('counts.blocked', 1)
                ->has('roleOptions', count(RoleName::cases()))
            );

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'nilufar']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Rahimova Nilufar')
                ->where('filters.search', 'nilufar')
            );

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'blocked']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.isBlocked', true)
            );

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'staff']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
    }

    public function test_admin_can_create_user_with_roles_profile_and_avatar(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->payload([
            'roles' => [RoleName::Editor->value, RoleName::Reviewer->value],
            'avatar' => $this->fakePng('ali.png', 200, 200),
        ]));

        $user = User::where('email', 'ali@example.com')->firstOrFail();

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.show', $user));

        $this->assertSame('Karimov Ali', $user->name);
        $this->assertSame('+998901234567', $user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Str0ng!Passw0rd', (string) $user->password));
        $this->assertTrue($user->hasAllRoles([RoleName::Editor, RoleName::Reviewer]));
        $this->assertSame('Yangi Asr universiteti', $user->authorProfile?->organization);
        $this->assertFalse($user->authorProfile?->is_public);
        Storage::disk('public')->assertExists((string) $user->authorProfile?->avatar_path);
    }

    public function test_create_validates_input(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->payload([
                'email' => 'taken@example.com',
                'roles' => [],
                'password_confirmation' => 'other',
                'first_name' => '',
            ]))
            ->assertSessionHasErrors(['email', 'roles', 'password', 'first_name']);
    }

    public function test_show_and_edit_pages(): void
    {
        $admin = $this->admin();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Show')
                ->where('user.id', $user->id)
                ->where('user.can.delete', true)
                ->has('activity')
            );

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Form')
                ->where('user.id', $user->id)
                ->where('canGrantSuperAdmin', true)
            );
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload([
                'email' => 'new@example.com',
                'roles' => [RoleName::Reviewer->value],
                'email_verified' => false,
                'academic_degree' => 'PhD',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole(RoleName::Reviewer));
        $this->assertFalse($user->hasRole(RoleName::Author));
        $this->assertSame('PhD', $user->authorProfile?->academic_degree);
    }

    public function test_only_super_admin_can_grant_super_admin_role(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('users.manage', 'admin.access');
        $user = User::factory()->author()->create();

        $this->actingAs($manager)
            ->put(route('admin.users.update', $user), $this->payload([
                'email' => $user->email,
                'roles' => [RoleName::SuperAdmin->value],
            ]))
            ->assertSessionHasErrors('roles');

        $this->assertFalse($user->refresh()->isSuperAdmin());
    }

    public function test_manager_cannot_touch_super_admin_account(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('users.manage', 'admin.access');
        $admin = $this->admin();

        $this->actingAs($manager)->get(route('admin.users.edit', $admin))->assertForbidden();
        $this->actingAs($manager)->delete(route('admin.users.destroy', $admin))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.users.block', $admin))->assertForbidden();
    }

    public function test_admin_cannot_remove_own_super_admin_role_block_or_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), $this->payload([
                'email' => $admin->email,
                'roles' => [RoleName::Editor->value],
            ]))
            ->assertSessionHasErrors('roles');

        $this->actingAs($admin)->post(route('admin.users.block', $admin))->assertSessionHasErrors('user');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');

        $admin->refresh();
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertFalse($admin->is_blocked);
        $this->assertNull($admin->deleted_at);
    }

    public function test_block_and_unblock(): void
    {
        $admin = $this->admin();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.block', $user), ['reason' => 'Spam'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->is_blocked);
        $this->assertSame('Spam', $user->blocked_reason);

        $this->actingAs($admin)->delete(route('admin.users.unblock', $user))->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->is_blocked);
    }

    public function test_delete_and_restore(): void
    {
        $admin = $this->admin();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted($user);

        // O'chirilgan foydalanuvchi profili ochiladi (tiklash uchun)
        $this->actingAs($admin)
            ->get(route('admin.users.show', $user->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('user.isDeleted', true));

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $user->id))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertNotSoftDeleted($user);
    }

    public function test_admin_can_set_password_and_send_reset_link(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $user), [
                'password' => 'N3w!Passw0rd',
                'password_confirmation' => 'N3w!Passw0rd',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('N3w!Passw0rd', (string) $user->refresh()->password));

        $this->actingAs($admin)->post(route('admin.users.password.reset', $user))->assertRedirect();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_admin_can_change_user_avatar(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $user = User::factory()->withRole(RoleName::Editor)->create(['name' => 'Rahimov Sardor']);

        $this->actingAs($admin)
            ->post(route('admin.users.avatar.store', $user), [
                'avatar' => $this->fakePng('a.png', 256, 256),
            ])
            ->assertSessionHasNoErrors();

        // Profili bo'lmagan xodimga profil users.name dan yaratiladi
        $profile = $user->refresh()->authorProfile;
        $this->assertNotNull($profile);
        $this->assertSame('Rahimov', $profile->last_name);
        $this->assertSame('Sardor', $profile->first_name);
        Storage::disk('public')->assertExists((string) $profile->avatar_path);

        $this->actingAs($admin)->delete(route('admin.users.avatar.destroy', $user))->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->authorProfile?->avatar_path);
    }

    public function test_users_section_requires_permission(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->create();
        $user = User::factory()->author()->create();

        $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.users.show', $user))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.users.store'), $this->payload())->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Notifications\System\MailTestNotification;
use App\Services\Settings\SystemSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin → Rollar va ruxsatlar (matritsa) va Tizim sozlamalari (jurnal, pochta, holat).
 */
class RolesAndSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    public function test_role_matrix_is_shown_and_updated_with_guards(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/roles/Index')
                ->has('roles', count(RoleName::cases()))
                ->where('roles.0.name', 'super_admin')
                ->where('roles.0.locked', true)
                ->has('groups', 6)
            );

        // Muharrirga hisobotlar ruxsati; admin.access olib tashlansa ham saqlanib qoladi
        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'editor'), [
                'permissions' => [PermissionName::ArticlesViewAny->value, PermissionName::ReportsView->value, 'unknown.permission'],
            ])
            ->assertSessionHasNoErrors();

        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $this->assertTrue($editor->can(PermissionName::ReportsView->value));
        $this->assertTrue($editor->can(PermissionName::AdminAccess->value));
        $this->assertFalse($editor->can(PermissionName::ArticlesDecide->value));
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.role_permissions']);

        // Muallifga admin ruxsati berilmaydi; bosh administrator o'zgarmaydi
        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'author'), ['permissions' => [PermissionName::AdminAccess->value]])
            ->assertSessionHasErrors('permissions');
        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'super_admin'), ['permissions' => []])
            ->assertSessionHasErrors('permissions');
        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'nonexistent'), ['permissions' => []])
            ->assertNotFound();

        // Standartga qaytarish
        $this->actingAs($admin)->post(route('admin.roles.reset', 'editor'))->assertSessionHasNoErrors();
        $editor = $editor->fresh() ?? $editor;
        $this->assertTrue($editor->can(PermissionName::ArticlesDecide->value));
        $this->assertFalse($editor->can(PermissionName::ReportsView->value));
    }

    public function test_user_cannot_remove_roles_manage_from_own_role_and_seeder_keeps_changes(): void
    {
        // Muharrirga rollarni boshqarish ruxsati beramiz
        $role = Role::findByName(RoleName::Editor->value, 'web');
        $role->givePermissionTo(PermissionName::RolesManage->value);
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();

        $this->actingAs($editor)
            ->put(route('admin.roles.update', 'editor'), ['permissions' => [PermissionName::ArticlesViewAny->value]])
            ->assertSessionHasErrors('permissions');

        $this->actingAs($editor)
            ->put(route('admin.roles.update', 'editor'), ['permissions' => [PermissionName::RolesManage->value, PermissionName::ReportsView->value]])
            ->assertSessionHasNoErrors();

        // Seeder qayta ishga tushsa — admin o'zgarishlari saqlanadi
        $this->seed(RolesAndPermissionsSeeder::class);
        $names = Role::findByName(RoleName::Editor->value, 'web')->permissions()->pluck('name')->all();
        $this->assertEqualsCanonicalizing(
            [PermissionName::AdminAccess->value, PermissionName::RolesManage->value, PermissionName::ReportsView->value],
            $names,
        );

        // Ruxsatsiz foydalanuvchi bo'limga kira olmaydi
        $this->actingAs(User::factory()->withRole(RoleName::ContentManager)->createOne())
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_journal_settings_override_config_and_shared_props(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.system.journal'), [
                'name' => 'Inson va Jamiyat',
                'issn' => '1234-56',
            ])
            ->assertSessionHasErrors('issn');

        $this->actingAs($admin)
            ->put(route('admin.system.journal'), [
                'name' => 'Inson va Jamiyat ilmiy jurnali',
                'subtitle' => 'Scientific Journal',
                'issn' => '2181-1234',
                'eissn' => '2181-567X',
                'plagiarism_max' => '15',
                'contact_email' => 'info@example.uz',
                'contact_phone' => '',
                'social_telegram' => 'https://t.me/insonvajamiyat',
                'payment_account' => '20208000900123456001',
                'payment_mfo' => '00450',
                'payment_inn' => '123456789',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Inson va Jamiyat ilmiy jurnali', config('journal.name'));
        $this->assertSame(15.0, config('journal.plagiarism_max'));
        $this->assertNull(config('journal.contact.phone'));
        $this->assertDatabaseHas('settings', ['group' => 'journal', 'key' => 'issn', 'value' => '2181-1234']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'settings.updated']);

        // Yangi so'rov: kesh orqali qayta qo'llanadi va sahifaga uzatiladi
        config(['journal.name' => 'Eski nom']);
        SystemSettings::apply();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('journal.name', 'Inson va Jamiyat ilmiy jurnali')
                ->where('journal.issn', '2181-1234')
                ->where('journal.socials.telegram', 'https://t.me/insonvajamiyat')
            );

        $this->actingAs($admin)
            ->get(route('admin.system.index', ['tab' => 'payment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/system/Index')
                ->where('tab', 'payment')
                ->where('journalForm.payment_inn', '123456789')
                ->has('status')
            );
    }

    public function test_mail_settings_keep_password_secret_and_send_test(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.system.mail'), [
                'mailer' => 'smtp',
                'host' => '',
                'scheme' => 'smtp',
                'from_address' => 'noreply@example.uz',
                'from_name' => 'Jurnal',
            ])
            ->assertSessionHasErrors(['host', 'port']);

        $this->actingAs($admin)
            ->put(route('admin.system.mail'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.uz',
                'port' => 465,
                'scheme' => 'smtps',
                'username' => 'noreply@example.uz',
                'password' => 'very-secret-pass',
                'from_address' => 'noreply@example.uz',
                'from_name' => 'Inson va Jamiyat',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('very-secret-pass', config('mail.mailers.smtp.password'));
        $this->assertDatabaseMissing('settings', ['group' => 'mail', 'key' => 'password', 'value' => 'very-secret-pass']);

        // Parol bo'sh yuborilsa — o'zgarmaydi; sahifaga hech qachon chiqmaydi
        $this->actingAs($admin)
            ->put(route('admin.system.mail'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.uz',
                'port' => 587,
                'scheme' => 'smtp',
                'password' => '',
                'from_address' => 'noreply@example.uz',
                'from_name' => 'Inson va Jamiyat',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('very-secret-pass', config('mail.mailers.smtp.password'));

        $this->actingAs($admin)
            ->get(route('admin.system.index', ['tab' => 'mail']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('mailForm.port', 587)
                ->where('mailPassword.set', true)
                ->where('mailPassword.source', 'database')
                ->missing('mailForm.password')
            );

        $this->actingAs($admin)
            ->post(route('admin.system.mail.test'), ['test_email' => 'check@example.uz'])
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            MailTestNotification::class,
            fn (MailTestNotification $n, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'check@example.uz',
        );
        $this->assertDatabaseHas('audit_logs', ['event' => 'settings.mail_test']);

        // Faqat settings.manage
        $this->actingAs(User::factory()->withRole(RoleName::ChiefEditor)->createOne())
            ->get(route('admin.system.index'))
            ->assertForbidden();
    }
}

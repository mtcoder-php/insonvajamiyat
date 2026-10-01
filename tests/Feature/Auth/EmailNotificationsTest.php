<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * O'zbekcha tasdiqlash / parol xatlari va ularning yuborilish holatlari.
 */
class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_mail_is_uzbek_and_renders(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Karimov Ali']);

        $mail = (new VerifyEmailNotification)->toMail($user);

        $this->assertStringContainsString('Elektron pochtangizni tasdiqlang', (string) $mail->subject);
        $this->assertSame('Pochtani tasdiqlash', $mail->actionText);
        $this->assertStringContainsString('/email/verify/', (string) $mail->actionUrl);

        $html = (string) $mail->render();
        $this->assertStringContainsString('INSON VA JAMIYAT', $html);
        $this->assertStringContainsString('Assalomu alaykum, Karimov Ali!', $html);
        $this->assertStringContainsString('Pochtani tasdiqlash', $html);
    }

    public function test_reset_password_mail_is_uzbek_and_renders(): void
    {
        $user = User::factory()->create();

        $mail = (new ResetPasswordNotification('token-123'))->toMail($user);

        $this->assertStringContainsString('Parolni tiklash', (string) $mail->subject);
        $this->assertStringContainsString('token-123', (string) $mail->actionUrl);
        $this->assertStringContainsString("Yangi parol o'rnatish", (string) $mail->render());
    }

    public function test_changing_email_in_profile_sends_new_verification_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'last_name' => 'Karimov',
            'first_name' => 'Ali',
            'email' => 'yangi@example.com',
            'locale' => 'uz',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_unchanged_email_does_not_send_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'last_name' => 'Karimov',
            'first_name' => 'Ali',
            'email' => $user->email,
            'locale' => 'uz',
        ])->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_admin_created_unverified_user_receives_verification_mail(): void
    {
        Notification::fake();
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'last_name' => 'Rahimova',
            'first_name' => 'Nilufar',
            'email' => 'nilufar@example.com',
            'locale' => 'uz',
            'roles' => [RoleName::Author->value],
            'email_verified' => false,
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'nilufar@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verify_email_page_shows_address(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/VerifyEmail')
                ->where('email', $user->email)
            );
    }

    public function test_verify_email_command_marks_user_verified(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'stuck@example.com']);

        $this->artisan('app:verify-email', ['email' => 'STUCK@example.com'])->assertSuccessful();

        $this->assertTrue($user->refresh()->hasVerifiedEmail());

        $this->artisan('app:verify-email', ['email' => 'nobody@example.com'])->assertFailed();
    }
}

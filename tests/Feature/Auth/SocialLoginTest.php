<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Enums\SocialProvider;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\AuditLog;
use App\Models\AuthorProfile;
use App\Models\SocialAccount;
use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Google / ORCID orqali kirish, ro'yxatdan o'tish, bog'lash va uzish.
 * Provayderlar Http::fake bilan almashtiriladi.
 */
class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    private const STATE = 'test-state-0123456789abcdefghijklmnopqrstuv';

    private const ORCID = '0000-0002-1825-0097';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client',
            'services.google.client_secret' => 'google-secret',
            'services.orcid.client_id' => 'APP-ORCID',
            'services.orcid.client_secret' => 'orcid-secret',
            'services.orcid.sandbox' => false,
        ]);

        Http::preventStrayRequests();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function fakeGoogle(array $profile = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-token', 'token_type' => 'Bearer']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => '1098765',
                'email' => 'olim@gmail.com',
                'email_verified' => true,
                'given_name' => 'Olim',
                'family_name' => 'Karimov',
                'name' => 'Olim Karimov',
                ...$profile,
            ]),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $emails
     */
    private function fakeOrcid(array $emails = []): void
    {
        Http::fake([
            'orcid.org/oauth/token' => Http::response([
                'access_token' => 'orcid-token',
                'token_type' => 'bearer',
                'orcid' => self::ORCID,
                'name' => 'Dilnoza Rahimova',
            ]),
            'pub.orcid.org/v3.0/'.self::ORCID.'/person' => Http::response([
                'name' => [
                    'given-names' => ['value' => 'Dilnoza'],
                    'family-name' => ['value' => 'Rahimova'],
                ],
                'emails' => ['email' => $emails],
            ]),
        ]);
    }

    /** Provayderdan qaytish (oldin redirect() sessiyaga yozgan state bilan) */
    private function returnFrom(SocialProvider $provider, array $query = []): TestResponse
    {
        return $this->withSession([SocialAuthController::FLOW_SESSION_KEY => [
            'provider' => $provider->value,
            'state' => self::STATE,
            'verifier' => $provider === SocialProvider::Google ? str_repeat('v', 64) : null,
            'at' => now()->getTimestamp(),
        ]])->get(route('social.callback', [
            'provider' => $provider->value,
            'state' => self::STATE,
            'code' => 'auth-code',
            ...$query,
        ]));
    }

    public function test_login_page_shows_only_configured_providers(): void
    {
        config(['services.orcid.client_id' => null]);

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->has('socialProviders', 1)
            ->where('socialProviders.0.key', 'google')
        );

        $this->get(route('social.redirect', 'orcid'))->assertNotFound();
        $this->get(route('social.redirect', 'facebook'))->assertNotFound();
    }

    public function test_redirect_sends_user_to_google_with_state_and_pkce(): void
    {
        $response = $this->get(route('social.redirect', 'google'));

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $flow = session(SocialAuthController::FLOW_SESSION_KEY);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertSame('google-client', $query['client_id']);
        $this->assertSame(url('/auth/google/callback'), $query['redirect_uri']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame($flow['state'], $query['state']);
        $this->assertSame(64, strlen($flow['verifier']));
    }

    public function test_new_google_user_completes_registration_without_password(): void
    {
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google)->assertRedirect(route('social.register'));
        $this->assertGuest();

        $this->get(route('social.register'))->assertInertia(fn (Assert $page) => $page
            ->component('auth/SocialRegister')
            ->where('provider.key', 'google')
            ->where('firstName', 'Olim')
            ->where('lastName', 'Karimov')
            ->where('email', 'olim@gmail.com')
            ->where('emailLocked', true)
        );

        $this->post(route('social.register.store'), [
            'first_name' => 'Olim',
            'last_name' => 'Karimov',
            'phone' => '+998 90 123 45 67',
            // Tasdiqlangan email o'zgartirilmaydi
            'email' => 'boshqa@example.com',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'olim@gmail.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasPassword());
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('+998901234567', $user->phone);
        $this->assertTrue($user->hasRole(RoleName::Author));
        $this->assertSame('Karimov', $user->authorProfile?->last_name);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => '1098765',
            'access_token' => null,
        ]);

        // Token so'rovi PKCE bilan ketgan
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['code'] === 'auth-code'
            && $request['code_verifier'] === str_repeat('v', 64));
    }

    public function test_existing_user_with_same_verified_email_is_linked_and_signed_in(): void
    {
        $user = User::factory()->author()->unverified()->create(['email' => 'olim@gmail.com']);
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->email_verified_at);
        $this->assertSame(1, $user->socialAccounts()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => AuditEvent::SocialLinked->value, 'user_id' => $user->id]);
    }

    public function test_linked_account_signs_in_directly(): void
    {
        $user = User::factory()->author()->create(['email' => 'eski@example.com']);
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => '1098765']);
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('olim@gmail.com', $user->socialAccounts()->value('email'));
    }

    public function test_unverified_google_email_is_not_used_to_link_existing_account(): void
    {
        User::factory()->author()->create(['email' => 'olim@gmail.com']);
        $this->fakeGoogle(['email_verified' => false]);

        $this->returnFrom(SocialProvider::Google)->assertRedirect(route('social.register'));
        $this->assertGuest();
        $this->assertSame(0, SocialAccount::count());

        // Shu email bilan ro'yxatdan o'tib bo'lmaydi — parol bilan kirib bog'lash kerak
        $this->post(route('social.register.store'), [
            'first_name' => 'Olim',
            'last_name' => 'Karimov',
            'phone' => '+998901234567',
            'email' => 'olim@gmail.com',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_state_cancelled_and_failed_exchange_return_to_login(): void
    {
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google, ['state' => 'forged'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('social');

        $this->returnFrom(SocialProvider::Google, ['error' => 'access_denied'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['social' => 'Kirish bekor qilindi.']);

        // Sessiyasiz (to'g'ridan-to'g'ri ochilgan) callback
        $this->get(route('social.callback', ['provider' => 'google', 'state' => self::STATE, 'code' => 'x']))
            ->assertSessionHasErrors('social');

        $this->assertGuest();
    }

    public function test_failed_token_exchange_returns_to_login(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $this->returnFrom(SocialProvider::Google)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('social');

        $this->assertGuest();
    }

    public function test_blocked_user_cannot_sign_in_with_google(): void
    {
        $user = User::factory()->author()->blocked()->create();
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => '1098765']);
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('social');

        $this->assertGuest();
    }

    public function test_two_factor_users_are_challenged(): void
    {
        $user = User::factory()->author()->withTwoFactor()->create();
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => '1098765']);
        $this->fakeGoogle();

        $this->returnFrom(SocialProvider::Google)
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('login.id', $user->id);

        $this->assertGuest();
    }

    public function test_new_orcid_user_without_public_email_verifies_entered_email(): void
    {
        Notification::fake();
        $this->fakeOrcid();

        $this->returnFrom(SocialProvider::Orcid)->assertRedirect(route('social.register'));

        $this->get(route('social.register'))->assertInertia(fn (Assert $page) => $page
            ->where('provider.key', 'orcid')
            ->where('firstName', 'Dilnoza')
            ->where('lastName', 'Rahimova')
            ->where('email', '')
            ->where('emailLocked', false)
            ->where('orcid', self::ORCID)
        );

        $this->post(route('social.register.store'), [
            'first_name' => 'Dilnoza',
            'last_name' => 'Rahimova',
            'phone' => '901234567',
            'email' => 'Dilnoza@Example.uz',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'dilnoza@example.uz')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertSame(self::ORCID, $user->authorProfile?->orcid);
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        // Tasdiqlanmagan — kabinet o'rniga tasdiqlash sahifasi
        $this->get(route('cabinet.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_orcid_verified_public_email_links_existing_author_and_fills_orcid(): void
    {
        $user = User::factory()->author()->create(['email' => 'dilnoza@example.uz']);
        $this->fakeOrcid([
            ['email' => 'eski@example.uz', 'verified' => false, 'primary' => false],
            ['email' => 'Dilnoza@example.uz', 'verified' => true, 'primary' => true],
        ]);

        $this->returnFrom(SocialProvider::Orcid)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(self::ORCID, $user->authorProfile()->value('orcid'));
    }

    public function test_orcid_is_not_copied_when_another_profile_already_has_it(): void
    {
        AuthorProfile::factory()->for(User::factory())->create(['orcid' => self::ORCID]);
        $user = User::factory()->author()->create();
        $this->fakeOrcid();

        $this->actingAs($user)->returnFrom(SocialProvider::Orcid)->assertRedirect(route('security.edit'));

        $this->assertSame(1, $user->socialAccounts()->count());
        $this->assertNotSame(self::ORCID, $user->authorProfile()->value('orcid'));
    }

    public function test_signed_in_user_links_account_from_security_settings(): void
    {
        $user = User::factory()->author()->create();
        $this->fakeGoogle();

        $this->actingAs($user)->returnFrom(SocialProvider::Google)
            ->assertRedirect(route('security.edit'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertSame('1098765', $user->socialAccounts()->value('provider_user_id'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_linked_to_another_user_cannot_be_linked_again(): void
    {
        $owner = User::factory()->author()->create();
        $owner->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => '1098765']);
        $user = User::factory()->author()->create();
        $this->fakeGoogle();

        $this->actingAs($user)->returnFrom(SocialProvider::Google)
            ->assertRedirect(route('security.edit'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertSame(0, $user->socialAccounts()->count());
        $this->assertSame($owner->id, SocialAccount::query()->value('user_id'));
    }

    public function test_passwordless_user_cannot_unlink_the_only_sign_in_method(): void
    {
        $user = User::factory()->author()->create(['password' => null]);
        $user->socialAccounts()->create(['provider' => SocialProvider::Google, 'provider_user_id' => '1098765']);

        $this->actingAs($user)
            ->delete(route('social.destroy', 'google'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertSame(1, $user->socialAccounts()->count());

        // Parol o'rnatilgach — uzish mumkin
        $this->put(route('user-password.update'), [
            'password' => 'Yangi-Parol-2026!',
            'password_confirmation' => 'Yangi-Parol-2026!',
        ])->assertSessionHasNoErrors();

        $this->delete(route('social.destroy', 'google'))->assertInertiaFlash('toast.type', 'success');

        $this->assertSame(0, $user->socialAccounts()->count());
        $this->assertTrue(AuditLog::query()->where('event', AuditEvent::SocialUnlinked)->exists());
    }

    public function test_passwordless_user_opens_security_page_without_password_confirmation(): void
    {
        $user = User::factory()->author()->create(['password' => null]);
        $user->socialAccounts()->create(['provider' => SocialProvider::Orcid, 'provider_user_id' => self::ORCID]);

        $this->actingAs($user)->get(route('security.edit'))->assertInertia(fn (Assert $page) => $page
            ->component('settings/Security')
            ->where('hasPassword', false)
            ->has('socialAccounts', 2)
            ->where('socialAccounts.0.key', 'google')
            ->where('socialAccounts.0.linked', false)
            ->where('socialAccounts.1.key', 'orcid')
            ->where('socialAccounts.1.linked', true)
            ->where('socialAccounts.1.identifier', self::ORCID)
        );

        // Parolli foydalanuvchi esa avval parolni tasdiqlaydi
        $this->actingAs(User::factory()->author()->create())
            ->get(route('security.edit'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_pending_registration_expires(): void
    {
        $this->withSession([SocialAuthController::PENDING_SESSION_KEY => [
            'provider' => 'google',
            'user' => ['id' => '1098765', 'email' => 'olim@gmail.com', 'emailVerified' => true],
            'at' => now()->subHour()->getTimestamp(),
        ]])->get(route('social.register'))->assertRedirect(route('login'));

        $this->post(route('social.register.store'), [])->assertRedirect(route('login'));
        $this->assertSame(0, User::count());
    }
}

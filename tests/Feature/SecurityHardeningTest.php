<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Enums\RoleName;
use App\Enums\SocialProvider;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Cabinet\ArticleSubmissionController;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ishga tushirishdan oldingi xavfsizlik auditi (70-bosqich) natijalari.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const STATE = 'test-state-0123456789abcdefghijklmnopqrstuv';

    private function googleLogin(string $email): TestResponse
    {
        config([
            'services.google.client_id' => 'google-client',
            'services.google.client_secret' => 'google-secret',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => '555', 'email' => $email, 'email_verified' => true,
                'given_name' => 'Olim', 'family_name' => 'Karimov',
            ]),
        ]);

        return $this->withSession([SocialAuthController::FLOW_SESSION_KEY => [
            'provider' => SocialProvider::Google->value,
            'state' => self::STATE,
            'verifier' => str_repeat('v', 64),
            'at' => now()->getTimestamp(),
        ]])->get(route('social.callback', ['provider' => 'google', 'state' => self::STATE, 'code' => 'c']));
    }

    public function test_unverified_account_created_by_someone_else_is_claimed_by_email_owner(): void
    {
        // Begona odam egasining emaili bilan ro'yxatdan o'tgan, lekin emailni tasdiqlay olmagan
        $user = User::factory()->author()->unverified()->withTwoFactor()->create(['email' => 'olim@gmail.com']);

        $this->googleLogin('olim@gmail.com')->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasPassword(), 'Begona qo\'ygan parol bekor qilinishi kerak');
        $this->assertNull($user->two_factor_secret);

        // Eski parol bilan endi kirib bo'lmaydi
        auth()->logout();
        $this->post(route('login'), ['email' => 'olim@gmail.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_accounts_are_not_auto_linked_by_email(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->create(['email' => 'muharrir@gmail.com']);

        $this->googleLogin('muharrir@gmail.com')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('social');

        $this->assertGuest();
        $this->assertSame(0, $editor->socialAccounts()->count());
    }

    public function test_editor_cannot_handle_own_article(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $own = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $editor->id,
            'submitted_at' => now()->subDay(),
        ]);
        $coauthored = Article::factory()->status(ArticleStatus::Submitted)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subDay(),
        ]);
        ArticleAuthor::factory()->for($coauthored)->create(['user_id' => $editor->id]);
        $other = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'submitted_at' => now()->subDay(),
        ]);

        // Ro'yxatda faqat begona maqola; o'z maqolasini ?article= bilan ham ocha olmaydi
        $this->actingAs($editor)
            ->get(route('admin.articles.index', ['queue' => 'all', 'article' => $own->uuid]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.all', 1)
                ->has('articles.data', 1)
                ->where('articles.data.0.uuid', $other->uuid)
                ->where('selected', null)
            );

        $this->post(route('admin.articles.decision', $own->uuid), ['decision' => 'accept'])->assertForbidden();
        $this->post(route('admin.articles.decision', $coauthored->uuid), ['decision' => 'reject', 'comment_to_author' => 'x'])->assertForbidden();
        $this->post(route('admin.articles.reviewers.store', $own->uuid), [
            'reviewer_ids' => [User::factory()->withRole(RoleName::Reviewer)->createOne()->id],
            'due_days' => 14,
        ])->assertForbidden();

        $this->assertSame(ArticleStatus::UnderReview, $own->refresh()->status);

        // Super Admin ham o'z maqolasiga qaror chiqara olmaydi
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $adminArticle = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $admin->id,
            'submitted_at' => now()->subDay(),
        ]);
        $this->actingAs($admin)
            ->post(route('admin.articles.decision', $adminArticle->uuid), ['decision' => 'accept'])
            ->assertForbidden();
    }

    public function test_account_forms_are_rate_limited_by_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => "nobody{$i}@example.com"]);
        }

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors(['email' => __('Juda ko\'p urinish. :minutes daqiqadan keyin qayta urinib ko\'ring.', ['minutes' => 10])]);

        // Boshqa IP — cheklanmagan (va email mavjud emasligi oshkor qilinmaydi)
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_password_reset_does_not_reveal_whether_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->author()->create();

        $known = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'ghost@example.com']);
        // Shu foydalanuvchiga qayta (broker cheklovi) — baribir bir xil javob
        $again = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);

        foreach ([$known, $unknown, $again] as $response) {
            $response->assertRedirect(route('password.request'))
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', __('passwords.sent'));
        }

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    public function test_changing_email_requires_current_password(): void
    {
        $user = User::factory()->author()->create();
        $payload = ['last_name' => 'Karimov', 'first_name' => 'Ali', 'email' => 'yangi@example.com', 'locale' => 'uz'];

        $this->actingAs($user)->patch(route('profile.update'), $payload)
            ->assertSessionHasErrors('current_password');
        $this->patch(route('profile.update'), [...$payload, 'current_password' => 'xato-parol'])
            ->assertSessionHasErrors('current_password');
        $this->assertNotSame('yangi@example.com', $user->refresh()->email);

        // Email o'zgarmasa — parol so'ralmaydi
        $this->patch(route('profile.update'), [...$payload, 'email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    public function test_shared_user_props_are_whitelisted(): void
    {
        $user = User::factory()->author()->create(['last_login_ip' => '10.1.2.3']);

        $this->actingAs($user)->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.two_factor_enabled', false)
            ->missing('auth.user.last_login_ip')
            ->missing('auth.user.blocked_reason')
            ->missing('auth.user.ai_monthly_token_limit')
            ->missing('auth.user.roles')
        );
    }

    public function test_security_headers_include_basic_csp(): void
    {
        $csp = (string) $this->get(route('home'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
    }

    public function test_notification_redirects_reject_backslash_tricks(): void
    {
        $this->assertNull(NotificationCenter::safeUrl('/\\evil.com'));
        $this->assertNull(NotificationCenter::safeUrl('\\\\evil.com'));
        $this->assertNull(NotificationCenter::safeUrl("/\tevil"));
        $this->assertSame('/cabinet', NotificationCenter::safeUrl('/cabinet'));
    }

    public function test_author_cannot_open_more_than_limited_drafts(): void
    {
        $author = User::factory()->author()->createOne();
        Article::factory()->status(ArticleStatus::Draft)->count(ArticleSubmissionController::MAX_OPEN_DRAFTS)->create([
            'submitter_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->post(route('cabinet.articles.store'), [])
            ->assertSessionHasErrors('title');

        $this->assertSame(ArticleSubmissionController::MAX_OPEN_DRAFTS, Article::query()->count());
    }

    public function test_author_cannot_download_reviewer_channel_attachment(): void
    {
        Storage::fake('local');
        $author = User::factory()->author()->createOne();
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $author->id,
            'submitted_at' => now()->subDay(),
        ]);
        Storage::disk('local')->put('messages/x.pdf', '%PDF');
        $message = $article->messages()->create([
            'channel' => MessageChannel::EditorReviewer,
            'sender_id' => User::factory()->withRole(RoleName::Editor)->createOne()->id,
            'body' => 'Ichki',
            'attachment_path' => 'messages/x.pdf',
            'attachment_name' => 'x.pdf',
        ]);

        $this->actingAs($author)
            ->get(route('cabinet.articles.messages.attachment', [$article->uuid, $message->id]))
            ->assertNotFound();
    }

    public function test_private_disk_is_not_served_directly(): void
    {
        $this->assertFalse((bool) config('filesystems.disks.local.serve'));
    }
}

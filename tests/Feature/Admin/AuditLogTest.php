<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Audit log: hodisalarni yozish, admin sahifasi, filtrlar, eksport va eski yozuvlarni tozalash.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    public function test_login_logout_and_failed_attempts_are_logged(): void
    {
        $user = User::factory()->createOne(['email' => 'aziz@example.com']);

        $this->post(route('login.store'), ['email' => 'aziz@example.com', 'password' => 'noto-g-ri']);

        $failed = AuditLog::query()->where('event', AuditEvent::LoginFailed->value)->firstOrFail();
        $this->assertNull($failed->user_id);
        $this->assertSame(['email' => 'aziz@example.com'], $failed->properties);

        $this->post(route('login.store'), ['email' => 'aziz@example.com', 'password' => 'password']);
        $this->assertAuthenticated();

        $login = AuditLog::query()->where('event', AuditEvent::Login->value)->firstOrFail();
        $this->assertSame($user->id, $login->user_id);
        $this->assertSame('user', $login->subject_type);
        $this->assertNotNull($login->ip_address);

        $this->post(route('logout'));
        $this->assertDatabaseHas('audit_logs', ['event' => AuditEvent::Logout->value, 'user_id' => $user->id]);
    }

    public function test_status_changes_and_admin_actions_are_logged_without_secrets(): void
    {
        $admin = $this->admin();
        $article = Article::factory()->createOne(['status' => ArticleStatus::Submitted, 'submitted_at' => now()]);

        app(ArticleWorkflow::class)->transition($article, ArticleStatus::UnderReview, $admin);

        $status = AuditLog::query()->where('event', AuditEvent::ArticleStatusChanged->value)->firstOrFail();
        $this->assertSame($admin->id, $status->user_id);
        $this->assertSame('article', $status->subject_type);
        $this->assertSame($article->id, $status->subject_id);
        $this->assertSame('submitted', $status->properties['from'] ?? null);
        $this->assertSame('under_review', $status->properties['to'] ?? null);

        $user = User::factory()->withRole(RoleName::Editor)->createOne();

        $this->actingAs($admin)
            ->post(route('admin.users.block', $user), ['reason' => 'Spam'])
            ->assertSessionHasNoErrors();

        $blocked = AuditLog::query()->where('event', AuditEvent::UserBlocked->value)->firstOrFail();
        $this->assertSame($admin->id, $blocked->user_id);
        $this->assertSame($user->id, $blocked->subject_id);
        $this->assertSame(['reason' => 'Spam'], $blocked->properties);

        $log = app(AuditLogger::class)->log(AuditEvent::UserPasswordChanged, $user, ['password' => 'secret', 'note' => 'x']);
        $this->assertSame(['note' => 'x'], $log?->properties);
    }

    public function test_only_super_admin_can_view_audit_log_with_filters(): void
    {
        $admin = $this->admin();
        $logger = app(AuditLogger::class);
        $editor = User::factory()->withRole(RoleName::Editor)->createOne(['name' => 'Toshev Bek']);

        $logger->log(AuditEvent::UserBlocked, $editor, ['reason' => 'Spam'], actor: $admin);
        $logger->log(AuditEvent::Login, $editor, actor: $editor);
        $logger->log(AuditEvent::LoginFailed, properties: ['email' => 'x@example.com']);

        $this->actingAs(User::factory()->withRole(RoleName::ChiefEditor)->createOne())
            ->get(route('admin.audit.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit/Index')
                ->where('logs.meta.total', 3)
                ->where('stats.failedLogins', 1)
                ->has('categories', count(AuditEvent::categories()))
                ->where('logs.data.0.event', AuditEvent::LoginFailed->value)
                ->where('logs.data.0.user', null)
            );

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['category' => 'user']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.category', 'user')
                ->where('logs.meta.total', 1)
                ->where('logs.data.0.subject.url', route('admin.users.show', $editor))
                ->where('logs.data.0.severity', 'danger')
            );

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['user' => $editor->id]))
            ->assertInertia(fn (Assert $page) => $page->where('logs.meta.total', 1));

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['q' => 'Toshev', 'category' => 'nonsense']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.category', null)
                ->where('logs.meta.total', 2)
            );
    }

    public function test_audit_export_and_pruning(): void
    {
        $admin = $this->admin();
        app(AuditLogger::class)->log(AuditEvent::Login, $admin, actor: $admin);

        $csv = $this->actingAs($admin)->get(route('admin.audit.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Tizimga kirdi', $csv);
        $this->assertDatabaseHas('audit_logs', ['event' => AuditEvent::ReportExported->value]);

        AuditLog::query()->create([
            'event' => AuditEvent::Login,
            'created_at' => now()->subDays((int) config('journal.audit.retention_days') + 1),
        ]);

        $this->assertSame(3, AuditLog::query()->count());
        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();
        $this->assertSame(2, AuditLog::query()->count());
    }

    public function test_article_page_view_is_recorded_in_daily_stats(): void
    {
        $article = Article::factory()->published()->createOne();

        $this->get(route('articles.show', (string) $article->slug))->assertOk();
        $this->get(route('articles.show', (string) $article->slug))->assertOk(); // sessiyada qayta — hisoblanmaydi

        $this->assertDatabaseHas('article_daily_stats', [
            'article_id' => $article->id,
            'date' => now()->toDateString(),
            'views' => 1,
        ]);
    }
}

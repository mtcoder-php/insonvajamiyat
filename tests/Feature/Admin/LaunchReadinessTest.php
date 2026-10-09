<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Jobs\QueueHeartbeat;
use App\Models\ArticleType;
use App\Models\Subject;
use App\Models\User;
use App\Services\Settings\LaunchReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Ishga tushirishga tayyorlik": tizim holati tabi, app:launch-check va tiriklik belgilari.
 */
class LaunchReadinessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{state: string, value: string}>
     */
    private function checks(): array
    {
        $flat = [];

        foreach (app(LaunchReadiness::class)->report()['groups'] as $group) {
            foreach ($group['checks'] as $check) {
                $flat[$group['key'].'.'.$check['key']] = ['state' => $check['state'], 'value' => $check['value']];
            }
        }

        return $flat;
    }

    public function test_status_tab_shows_readiness_report(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();

        $this->actingAs($admin)
            ->get(route('admin.system.index', ['tab' => 'status']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('readiness.groups')
                ->has('readiness.counts.error')
                ->where('readiness.ready', fn ($ready) => is_bool($ready))
            );

        $this->actingAs($admin)
            ->get(route('admin.system.index', ['tab' => 'journal']))
            ->assertInertia(fn (Assert $page) => $page->where('readiness', null));
    }

    public function test_heartbeats_detect_scheduler_and_worker(): void
    {
        $checks = $this->checks();
        $this->assertSame('warning', $checks['background.scheduler']['state']); // testing muhiti — xato emas, ogohlantirish
        $this->assertSame('warning', $checks['background.worker']['state']);

        Cache::forever(LaunchReadiness::SCHEDULER_HEARTBEAT, now()->getTimestamp());
        (new QueueHeartbeat)->handle();

        $checks = $this->checks();
        $this->assertSame('ok', $checks['background.scheduler']['state']);
        $this->assertSame('ok', $checks['background.worker']['state']);

        $this->travel(20)->minutes();
        $this->assertSame('warning', $this->checks()['background.worker']['state']);
    }

    public function test_production_blockers_are_errors(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.debug' => true,
            'app.url' => 'http://insonvajamiyat.uz',
            'journal.contact.email' => '',
            'payments.payme.enabled' => true,
            'payments.payme.merchant_id' => 'm',
            'payments.payme.key' => 'k',
            'payments.payme.test_mode' => true,
        ]);
        User::factory()->createOne(['email' => 'admin@insonvajamiyat.test']);

        $checks = $this->checks();

        foreach (['server.debug', 'server.https', 'journal.email', 'payment.payme_mode', 'security.demo', 'security.super_admin', 'background.scheduler'] as $key) {
            $this->assertSame('error', $checks[$key]['state'], $key);
        }

        $this->artisan('app:launch-check')->assertFailed();
    }

    public function test_command_succeeds_when_nothing_blocks_launch(): void
    {
        User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        Cache::forever(LaunchReadiness::SCHEDULER_HEARTBEAT, now()->getTimestamp());
        Cache::forever(LaunchReadiness::QUEUE_HEARTBEAT, now()->getTimestamp());
        config([
            'app.url' => 'https://insonvajamiyat.uz',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'queue.default' => 'database',
        ]);
        Subject::factory()->createOne();
        ArticleType::factory()->createOne(['price' => 0]);

        // storage:link sinov muhitida bo'lmasligi mumkin — undan boshqa xato qolmasligi kerak
        $errors = array_keys(array_filter($this->checks(), fn (array $c): bool => $c['state'] === 'error'));
        $this->assertSame([], array_values(array_diff($errors, ['server.storage'])));

        $this->assertSame('hozirgina', $this->checks()['background.scheduler']['value']);

        if ($errors === []) {
            $this->artisan('app:launch-check')->assertSuccessful();
        }

        $this->artisan('app:launch-check --json')->expectsOutputToContain('"groups"');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentProvider;
use App\Enums\RoleName;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → To'lovlar → "So'rovlar jurnali" (Click / Payme webhook loglari).
 */
class PaymentLogsTest extends TestCase
{
    use RefreshDatabase;

    private function log(array $attributes = []): PaymentLog
    {
        return PaymentLog::query()->create([
            'provider' => PaymentProvider::Click,
            'action' => 'prepare',
            'request' => ['click_trans_id' => '777', 'merchant_trans_id' => '15', 'sign_string' => 'abc123', 'nested' => ['secret_key' => 'zzz']],
            'response' => ['error' => 0, 'error_note' => 'Success'],
            'http_status' => 200,
            'error_code' => 0,
            'signature_valid' => true,
            'ip' => '185.8.212.10',
            'duration_ms' => 42,
            'created_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_logs_tab_lists_requests_with_secrets_masked(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $payment = Payment::factory()->createOne(['receipt_number' => 'PAY-00042']);
        $this->log(['payment_id' => $payment->id]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'logs']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'logs')
                ->where('filters.log', 'all')
                ->where('counts.logs', 1)
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'prepare')
                ->where('logs.data.0.ok', true)
                ->where('logs.data.0.payment.receipt', 'PAY-00042')
                ->where('logs.data.0.request.click_trans_id', '777')
                ->where('logs.data.0.request.sign_string', '•••')
                ->where('logs.data.0.request.nested.secret_key', '•••')
                ->where('payments', null)
            );
    }

    public function test_logs_can_be_filtered_and_searched(): void
    {
        $admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
        $this->log();
        $this->log(['provider' => PaymentProvider::Payme, 'action' => 'CheckPerformTransaction', 'error_code' => -31050, 'ip' => '10.0.0.1']);
        $this->log(['action' => 'complete', 'error_code' => -1, 'signature_valid' => false, 'ip' => '203.0.113.5']);

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'logs', 'log' => 'errors']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.log', 'errors')->has('logs.data', 2));

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'logs', 'log' => 'signature']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.signatureValid', false)
                ->where('logs.data.0.ok', false)
                ->where('logs.data.0.errorCode', -1)
            );

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'logs', 'search' => 'CheckPerform']))
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)->where('logs.data.0.provider', 'payme'));

        // Noma'lum filtr — hammasi
        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['tab' => 'logs', 'log' => 'drop table']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.log', 'all')->has('logs.data', 3));
    }

    public function test_old_logs_are_pruned(): void
    {
        $this->log(['created_at' => now()->subDays(PaymentLog::RETENTION_DAYS + 1)]);
        $fresh = $this->log();

        $this->artisan('model:prune', ['--model' => [PaymentLog::class]])->assertSuccessful();

        $this->assertSame([$fresh->id], PaymentLog::query()->pluck('id')->all());
    }

    public function test_logs_require_payments_permission(): void
    {
        $author = User::factory()->author()->createOne();

        $this->actingAs($author)->get(route('admin.payments.index', ['tab' => 'logs']))->assertForbidden();
    }
}

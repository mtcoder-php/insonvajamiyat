<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Payments\Payme\PaymeException;
use App\Services\Payments\Payme\PaymeMerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * To'lovni qaytarish (TZ 4.1.4, 4.2.8): Click API, Payme kabinet + CancelTransaction, qo'lda (bank).
 */
class RefundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'payments.click.service_id' => '12345',
            'payments.click.merchant_user_id' => '777',
            'payments.click.secret_key' => 'click-secret',
            'payments.payme.enabled' => true,
            'payments.payme.merchant_id' => '64f0c1a2b3c4d5e6f7a8b9c0',
            'payments.payme.key' => 'payme-test-key',
            'payments.payme.account_key' => 'payment_id',
        ]);
        $this->admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    private function paidPayment(PaymentProvider $provider, ArticleStatus $articleStatus = ArticleStatus::Rejected): Payment
    {
        $article = Article::factory()->status($articleStatus)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'article_type_id' => ArticleType::factory()->createOne(['price' => 150000])->id,
            'payment_status' => ArticlePaymentStatus::Paid,
        ]);

        $payment = Payment::factory()->paid()->createOne([
            'user_id' => $article->submitter_id,
            'article_id' => $article->id,
            'purpose' => PaymentPurpose::Publication,
            'provider' => $provider,
            'amount' => 150000,
            'receipt_number' => 'PAY-'.fake()->unique()->numberBetween(1000, 9999),
            'provider_transaction_id' => $provider === PaymentProvider::Manual ? null : 'tx-'.fake()->unique()->numberBetween(1000, 9999),
        ]);

        if ($provider === PaymentProvider::Payme) {
            $payment->forceFill(['provider_state' => 2, 'provider_create_time' => now()->getTimestampMs(), 'provider_perform_time' => now()->getTimestampMs()])->save();
        }

        return $payment;
    }

    /**
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return ['reason' => 'Maqola rad etildi, muallif bilan kelishildi', 'confirm' => true, ...$overrides];
    }

    public function test_click_refund_calls_reversal_api_and_settles_everything(): void
    {
        Http::fake(['api.click.uz/*' => Http::response(['error_code' => 0, 'error_note' => 'Success', 'payment_id' => 998877])]);
        $payment = $this->paidPayment(PaymentProvider::Click);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form())
            ->assertSessionHasNoErrors();

        Http::assertSent(function (HttpRequest $request) use ($payment): bool {
            [$user, $digest, $timestamp] = explode(':', $request->header('Auth')[0]);

            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.click.uz/v2/merchant/payment/reversal/12345/'.$payment->provider_transaction_id
                && $user === '777'
                && $digest === sha1($timestamp.'click-secret');
        });

        $payment->refresh();
        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertNotNull($payment->refunded_at);
        $this->assertSame(ArticlePaymentStatus::Refunded, $payment->article?->payment_status);

        $refund = Refund::query()->sole();
        $this->assertSame(RefundStatus::Completed, $refund->status);
        $this->assertSame('998877', $refund->provider_refund_id);
        $this->assertSame($this->admin->id, $refund->processed_by);

        Notification::assertSentTo($payment->user, ArticleUpdateNotification::class, fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::PAYMENT);
        $this->assertTrue(AuditLog::query()->where('event', 'payment.refunded')->exists());

        // Ikkinchi marta — mumkin emas
        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form())
            ->assertSessionHasErrors('reason');
        Http::assertSentCount(1);

        // Muallif kabinetida — "Qaytarilgan"
        $this->actingAs($payment->user)
            ->get(route('cabinet.articles.show', $payment->article?->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.status', 'refunded')
                ->where('payment.refundedAt', fn ($at) => is_string($at))
            );
    }

    public function test_click_error_keeps_payment_paid(): void
    {
        Http::fake(['api.click.uz/*' => Http::response(['error_code' => -5017, 'error_note' => 'Reversal period expired'])]);
        $payment = $this->paidPayment(PaymentProvider::Click);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form())
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $refund = Refund::query()->sole();
        $this->assertSame(RefundStatus::Failed, $refund->status);
        $this->assertStringContainsString('-5017', (string) $refund->error_message);
        Notification::assertNothingSent();

        // Xatodan keyin qayta urinish mumkin
        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['tab' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payments.data.0.refund.blocked', null)
                ->has('payments.data.0.refund.history', 1)
                ->where('can.refund', true)
            );
    }

    public function test_payme_refund_waits_for_cancel_transaction(): void
    {
        $payment = $this->paidPayment(PaymentProvider::Payme);
        $rpc = fn (string $method, array $params) => $this->postJson(route('payments.payme'), [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ], ['Authorization' => 'Basic '.base64_encode('Paycom:payme-test-key')]);

        // So'rovsiz — Payme bekor qila olmaydi
        $rpc('CancelTransaction', ['id' => $payment->provider_transaction_id, 'reason' => 5])
            ->assertJsonPath('error.code', PaymeException::CANNOT_CANCEL);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form())
            ->assertSessionHasNoErrors();

        $refund = Refund::query()->sole();
        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['tab' => 'refunds']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('refunds.data', 1)
                ->where('refunds.data.0.awaitingProvider', true)
                ->where('counts.refunds', 1)
            );

        // Payme kabinetida bekor qilindi → CancelTransaction (state 2 → -2)
        $rpc('CancelTransaction', ['id' => $payment->provider_transaction_id, 'reason' => 5])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_CANCELLED_AFTER_PERFORM);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame(-2, $payment->provider_state);
        $this->assertSame(5, $payment->cancel_reason);
        $this->assertSame(RefundStatus::Completed, $refund->refresh()->status);
        $this->assertSame(ArticlePaymentStatus::Refunded, $payment->article?->payment_status);

        // Takroriy so'rov — o'sha javob
        $rpc('CancelTransaction', ['id' => $payment->provider_transaction_id, 'reason' => 5])
            ->assertJsonPath('result.state', -2);
        $rpc('CheckTransaction', ['id' => $payment->provider_transaction_id])
            ->assertJsonPath('result.state', -2)
            ->assertJsonPath('result.reason', 5);
    }

    public function test_open_payme_request_can_be_cancelled(): void
    {
        $payment = $this->paidPayment(PaymentProvider::Payme);

        $this->actingAs($this->admin)->post(route('admin.payments.refund', $payment->uuid), $this->form());
        $refund = Refund::query()->sole();

        $this->actingAs($this->admin)
            ->delete(route('admin.payments.refunds.cancel', $refund))
            ->assertSessionHasNoErrors();

        $this->assertSame(RefundStatus::Failed, $refund->refresh()->status);

        $this->postJson(route('payments.payme'), [
            'jsonrpc' => '2.0', 'id' => 2, 'method' => 'CancelTransaction',
            'params' => ['id' => $payment->provider_transaction_id, 'reason' => 5],
        ], ['Authorization' => 'Basic '.base64_encode('Paycom:payme-test-key')])
            ->assertJsonPath('error.code', PaymeException::CANNOT_CANCEL);
    }

    public function test_manual_refund_needs_bank_reference_and_completes_immediately(): void
    {
        $payment = $this->paidPayment(PaymentProvider::Manual, ArticleStatus::Withdrawn);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form())
            ->assertSessionHasErrors('reference');

        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $payment->uuid), $this->form(['reference' => 'TP-2026-0187']))
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertSame('TP-2026-0187', Refund::query()->sole()->reference);
    }

    public function test_rules_and_permissions(): void
    {
        // Maqola rad etilmagan — qaytarilmaydi
        $active = $this->paidPayment(PaymentProvider::Click, ArticleStatus::UnderReview);
        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $active->uuid), $this->form())
            ->assertSessionHasErrors('reason');

        // Tasdiq belgisi va sabab majburiy
        $rejected = $this->paidPayment(PaymentProvider::Click);
        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $rejected->uuid), ['reason' => 'qisqa'])
            ->assertSessionHasErrors(['reason', 'confirm']);

        // Click kalitlari yo'q
        config(['payments.click.merchant_user_id' => null]);
        $this->actingAs($this->admin)
            ->post(route('admin.payments.refund', $rejected->uuid), $this->form())
            ->assertSessionHasErrors('reason');

        // Muharrirda payments.refund ruxsati yo'q
        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->post(route('admin.payments.refund', $rejected->uuid), $this->form())
            ->assertForbidden();

        $this->assertSame(0, Refund::query()->count());
    }
}

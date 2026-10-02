<?php

namespace Tests\Feature\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Payments\Click\ClickMerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Click SHOP API: muallif to'lovni boshlaydi → Prepare → Complete → maqola tahririyat navbatida.
 */
class ClickPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['payments.click' => [
            'enabled' => true,
            'service_id' => '12345',
            'merchant_id' => '6789',
            'merchant_user_id' => '111',
            'secret_key' => 'click-secret',
            'checkout_url' => 'https://my.click.uz/services/pay',
        ]]);
    }

    private function awaitingArticle(int $price = 150000): Article
    {
        return Article::factory()->status(ArticleStatus::AwaitingPayment)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'article_type_id' => ArticleType::factory()->createOne(['price' => $price])->id,
            'payment_status' => ArticlePaymentStatus::Unpaid,
            'submitted_at' => now()->subDay(),
        ]);
    }

    private function startPayment(Article $article): Payment
    {
        $this->actingAs($article->submitter)
            ->post(route('cabinet.articles.pay', $article->uuid), ['provider' => 'click'])
            ->assertRedirectContains('my.click.uz/services/pay');

        return Payment::query()->where('article_id', $article->id)->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function prepareData(Payment $payment, array $overrides = []): array
    {
        $data = [
            'click_trans_id' => '900001',
            'service_id' => '12345',
            'click_paydoc_id' => '55501',
            'merchant_trans_id' => (string) $payment->id,
            'amount' => '150000.00',
            'action' => '0',
            'error' => '0',
            'error_note' => 'Success',
            'sign_time' => '2026-10-03 10:00:00',
            ...$overrides,
        ];
        $data['sign_string'] ??= ClickMerchantService::sign(
            (string) $data['click_trans_id'], (string) $data['merchant_trans_id'], (string) $data['amount'], 0, (string) $data['sign_time'],
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function completeData(Payment $payment, array $overrides = []): array
    {
        $data = [
            ...$this->prepareData($payment),
            'action' => '1',
            'merchant_prepare_id' => (string) $payment->id,
            ...$overrides,
        ];
        unset($data['sign_string']);
        $data['sign_string'] = $overrides['sign_string'] ?? ClickMerchantService::sign(
            (string) $data['click_trans_id'], (string) $data['merchant_trans_id'], (string) $data['amount'], 1, (string) $data['sign_time'], (string) $data['merchant_prepare_id'],
        );

        return $data;
    }

    public function test_full_click_flow_moves_article_to_editorial_queue(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->assertSame(PaymentProvider::Click, $payment->provider);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(1, $payment->items()->count());

        // Qayta bosilsa — o'sha urinish ishlatiladi
        $this->assertSame($payment->id, $this->startPayment($article)->id);

        $this->post(route('payments.click.prepare'), $this->prepareData($payment))
            ->assertOk()
            ->assertJson(['error' => 0, 'merchant_prepare_id' => $payment->id]);

        $this->assertSame(PaymentStatus::Processing, $payment->refresh()->status);
        $this->assertSame(ArticlePaymentStatus::Pending, $article->refresh()->payment_status);

        $this->post(route('payments.click.complete'), $this->completeData($payment))
            ->assertOk()
            ->assertJson(['error' => 0, 'merchant_confirm_id' => $payment->id]);

        $payment->refresh();
        $article->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame('900001', $payment->provider_transaction_id);
        $this->assertSame(ArticleStatus::Submitted, $article->status);
        $this->assertSame(ArticlePaymentStatus::Paid, $article->payment_status);

        Notification::assertSentTo($article->submitter, ArticleUpdateNotification::class,
            fn (ArticleUpdateNotification $n): bool => $n->kind === ArticleUpdateNotification::PAYMENT);

        // Takroriy Complete — "Already paid"
        $this->post(route('payments.click.complete'), $this->completeData($payment))
            ->assertJson(['error' => ClickMerchantService::ALREADY_PAID]);

        $this->assertSame(3, PaymentLog::query()->where('payment_id', $payment->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.paid_online']);
    }

    public function test_signature_amount_and_order_are_validated(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->post(route('payments.click.prepare'), $this->prepareData($payment, ['sign_string' => md5('fake')]))
            ->assertJson(['error' => ClickMerchantService::SIGN_FAILED]);

        $this->post(route('payments.click.prepare'), $this->prepareData($payment, ['amount' => '1000.00']))
            ->assertJson(['error' => ClickMerchantService::INCORRECT_AMOUNT]);

        $this->post(route('payments.click.prepare'), $this->prepareData($payment, ['merchant_trans_id' => '999999']))
            ->assertJson(['error' => ClickMerchantService::ORDER_NOT_FOUND]);

        $this->post(route('payments.click.prepare'), ['click_trans_id' => '1'])
            ->assertJson(['error' => ClickMerchantService::BAD_REQUEST]);

        // Prepare qilinmagan tranzaksiyani yakunlab bo'lmaydi
        $this->post(route('payments.click.complete'), $this->completeData($payment))
            ->assertJson(['error' => ClickMerchantService::TRANSACTION_NOT_FOUND]);

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
        $this->assertSame(ArticleStatus::AwaitingPayment, $article->refresh()->status);
        $this->assertSame(1, PaymentLog::query()->where('signature_valid', false)->count());
    }

    public function test_click_error_cancels_transaction_and_releases_article(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->post(route('payments.click.prepare'), $this->prepareData($payment))->assertJson(['error' => 0]);

        $this->post(route('payments.click.complete'), $this->completeData($payment, ['error' => '-5017', 'error_note' => 'Insufficient funds']))
            ->assertJson(['error' => ClickMerchantService::CANCELLED]);

        $this->assertSame(PaymentStatus::Cancelled, $payment->refresh()->status);
        $this->assertSame(ArticlePaymentStatus::Unpaid, $article->refresh()->payment_status);
        $this->assertSame(ArticleStatus::AwaitingPayment, $article->status);

        // Keyingi urinish — yangi to'lov yozuvi
        $this->assertNotSame($payment->id, $this->startPayment($article)->id);
    }

    public function test_manually_confirmed_article_rejects_click_payment(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $article->forceFill(['payment_status' => ArticlePaymentStatus::Paid, 'status' => ArticleStatus::Submitted])->save();

        $this->post(route('payments.click.prepare'), $this->prepareData($payment))
            ->assertJson(['error' => ClickMerchantService::ALREADY_PAID]);
    }

    public function test_only_submitter_can_start_and_disabled_provider_is_rejected(): void
    {
        $article = $this->awaitingArticle();

        $this->actingAs(User::factory()->author()->createOne())
            ->post(route('cabinet.articles.pay', $article->uuid), ['provider' => 'click'])
            ->assertForbidden();

        config(['payments.click.enabled' => false]);

        $this->actingAs($article->submitter)
            ->post(route('cabinet.articles.pay', $article->uuid), ['provider' => 'click'])
            ->assertSessionHasErrors('provider');

        $this->assertSame(0, Payment::query()->count());
    }
}

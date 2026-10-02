<?php

namespace Tests\Feature\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Payme\PaymeException;
use App\Services\Payments\Payme\PaymeMerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Payme Merchant API (JSON-RPC): avtorizatsiya, CheckPerform → Create → Perform, bekor qilish, muddat.
 */
class PaymePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'payme-test-key';

    private int $rpcId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'payments.payme.enabled' => true,
            'payments.payme.merchant_id' => '64f0c1a2b3c4d5e6f7a8b9c0',
            'payments.payme.key' => self::KEY,
            'payments.payme.test_mode' => true,
            'payments.payme.account_key' => 'payment_id',
        ]);
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
        $response = $this->actingAs($article->submitter)
            ->post(route('cabinet.articles.pay', $article->uuid), ['provider' => 'payme'])
            ->assertRedirectContains('checkout.test.paycom.uz/');

        $payment = Payment::query()->where('article_id', $article->id)->latest('id')->firstOrFail();

        $encoded = basename((string) $response->headers->get('Location'));
        $this->assertStringContainsString('ac.payment_id='.$payment->id.';a=15000000', (string) base64_decode($encoded));

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function rpc(string $method, array $params, ?string $key = self::KEY): TestResponse
    {
        $headers = $key !== null ? ['Authorization' => 'Basic '.base64_encode('Paycom:'.$key)] : [];

        return $this->postJson(route('payments.payme'), [
            'jsonrpc' => '2.0',
            'id' => ++$this->rpcId,
            'method' => $method,
            'params' => $params,
        ], $headers)->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function account(Payment $payment): array
    {
        return ['payment_id' => (string) $payment->id];
    }

    public function test_authorization_is_required(): void
    {
        $this->rpc('CheckPerformTransaction', [], key: 'wrong')
            ->assertJsonPath('error.code', PaymeException::INSUFFICIENT_PRIVILEGE);

        $this->rpc('CheckPerformTransaction', [], key: null)
            ->assertJsonPath('error.code', PaymeException::INSUFFICIENT_PRIVILEGE);

        $this->rpc('ChangePassword', ['password' => 'x'])
            ->assertJsonPath('error.code', PaymeException::METHOD_NOT_FOUND);
    }

    public function test_full_payme_flow(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->rpc('CheckPerformTransaction', ['amount' => 15000000, 'account' => $this->account($payment)])
            ->assertJsonPath('result.allow', true);

        $this->rpc('CheckPerformTransaction', ['amount' => 100, 'account' => $this->account($payment)])
            ->assertJsonPath('error.code', PaymeException::WRONG_AMOUNT);

        $this->rpc('CheckPerformTransaction', ['amount' => 15000000, 'account' => ['payment_id' => '999999']])
            ->assertJsonPath('error.code', PaymeException::ORDER_NOT_FOUND);

        $create = ['id' => 'payme-tx-1', 'time' => now()->getTimestampMs(), 'amount' => 15000000, 'account' => $this->account($payment)];

        $created = $this->rpc('CreateTransaction', $create)
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_CREATED)
            ->assertJsonPath('result.transaction', (string) $payment->id)
            ->json('result');

        // Takroriy Create — o'sha javob
        $this->rpc('CreateTransaction', $create)->assertJsonPath('result.create_time', $created['create_time']);

        // Boshqa tranzaksiya shu buyurtmaga — band
        $this->rpc('CreateTransaction', [...$create, 'id' => 'payme-tx-2'])
            ->assertJsonPath('error.code', PaymeException::ORDER_BUSY);

        $this->assertSame(ArticlePaymentStatus::Pending, $article->refresh()->payment_status);

        $performed = $this->rpc('PerformTransaction', ['id' => 'payme-tx-1'])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_PERFORMED)
            ->json('result');

        $this->rpc('PerformTransaction', ['id' => 'payme-tx-1'])
            ->assertJsonPath('result.perform_time', $performed['perform_time']);

        $article->refresh();
        $this->assertSame(ArticleStatus::Submitted, $article->status);
        $this->assertSame(ArticlePaymentStatus::Paid, $article->payment_status);
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);

        $this->rpc('CancelTransaction', ['id' => 'payme-tx-1', 'reason' => 5])
            ->assertJsonPath('error.code', PaymeException::CANNOT_CANCEL);

        $this->rpc('CheckTransaction', ['id' => 'payme-tx-1'])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_PERFORMED)
            ->assertJsonPath('result.cancel_time', 0);

        $this->rpc('CheckTransaction', ['id' => 'nope'])
            ->assertJsonPath('error.code', PaymeException::TRANSACTION_NOT_FOUND);

        $this->rpc('GetStatement', ['from' => now()->subHour()->getTimestampMs(), 'to' => now()->addHour()->getTimestampMs()])
            ->assertJsonCount(1, 'result.transactions')
            ->assertJsonPath('result.transactions.0.id', 'payme-tx-1')
            ->assertJsonPath('result.transactions.0.amount', 15000000);

        // To'langan buyurtma qayta tekshirilsa
        $this->rpc('CheckPerformTransaction', ['amount' => 15000000, 'account' => $this->account($payment)])
            ->assertJsonPath('error.code', PaymeException::ORDER_UNAVAILABLE);
    }

    public function test_cancel_before_perform_releases_article(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->rpc('CreateTransaction', ['id' => 'tx-c', 'time' => now()->getTimestampMs(), 'amount' => 15000000, 'account' => $this->account($payment)])
            ->assertJsonPath('result.state', 1);

        $this->rpc('CancelTransaction', ['id' => 'tx-c', 'reason' => 3])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_CANCELLED);

        // Takroriy bekor qilish — o'sha holat
        $this->rpc('CancelTransaction', ['id' => 'tx-c', 'reason' => 3])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_CANCELLED);

        $this->rpc('PerformTransaction', ['id' => 'tx-c'])
            ->assertJsonPath('error.code', PaymeException::CANNOT_PERFORM);

        $this->assertSame(PaymentStatus::Cancelled, $payment->refresh()->status);
        $this->assertSame(3, $payment->cancel_reason);
        $this->assertSame(ArticlePaymentStatus::Unpaid, $article->refresh()->payment_status);
        $this->assertSame(ArticleStatus::AwaitingPayment, $article->status);
    }

    public function test_expired_transaction_cannot_be_performed(): void
    {
        $article = $this->awaitingArticle();
        $payment = $this->startPayment($article);

        $this->rpc('CreateTransaction', ['id' => 'tx-old', 'time' => now()->getTimestampMs(), 'amount' => 15000000, 'account' => $this->account($payment)]);

        $this->travel(13)->hours();

        $this->rpc('PerformTransaction', ['id' => 'tx-old'])
            ->assertJsonPath('error.code', PaymeException::CANNOT_PERFORM);

        $this->rpc('CheckTransaction', ['id' => 'tx-old'])
            ->assertJsonPath('result.state', PaymeMerchantService::STATE_CANCELLED)
            ->assertJsonPath('result.reason', PaymeMerchantService::REASON_TIMEOUT);

        $this->assertSame(ArticleStatus::AwaitingPayment, $article->refresh()->status);
    }

    public function test_cabinet_shows_online_options_and_return_state(): void
    {
        config(['payments.click.enabled' => false]);
        $article = $this->awaitingArticle();

        $this->actingAs($article->submitter)
            ->get(route('cabinet.articles.show', ['article' => $article->uuid, 'payment' => 'return']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.awaiting', true)
                ->where('payment.online.providers.0.value', 'payme')
                ->has('payment.online.providers', 1)
                ->where('payment.online.returned', true)
                ->where('payment.online.processing', false)
            );
    }
}

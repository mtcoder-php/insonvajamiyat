<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → To'lovlar: ro'yxat, qo'lda tasdiqlash, to'lovdan ozod qilish va muallif tomonidagi ko'rinish.
 */
class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * Summa JSON orqali o'tganda 150000.0 → 150000 bo'lib qoladi — son sifatida solishtiramiz.
     *
     * @return \Closure(mixed): bool
     */
    private static function money(int $expected): \Closure
    {
        return fn (mixed $value): bool => is_numeric($value) && (float) $value === (float) $expected;
    }

    private function admin(): User
    {
        return User::factory()->withRole(RoleName::SuperAdmin)->createOne();
    }

    private function awaitingArticle(int $price = 150000): Article
    {
        $author = User::factory()->author()->createOne();

        return Article::factory()->status(ArticleStatus::AwaitingPayment)->createOne([
            'submitter_id' => $author->id,
            'article_type_id' => ArticleType::factory()->createOne(['price' => $price])->id,
            'payment_status' => ArticlePaymentStatus::Unpaid,
            'submitted_at' => now()->subDays(3),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function confirmation(array $overrides = []): array
    {
        return [
            'amount' => 150000,
            'paid_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'reference' => 'TP-2026-0042',
            'note' => "Bank o'tkazmasi",
            ...$overrides,
        ];
    }

    public function test_index_opens_awaiting_tab_by_default(): void
    {
        $article = $this->awaitingArticle();
        Payment::factory()->paid()->create(['provider' => PaymentProvider::Click, 'amount' => 200000]);

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payments/Index')
                ->where('filters.tab', 'awaiting')
                ->where('counts.awaiting', 1)
                ->where('counts.all', 1)
                ->has('awaiting.data', 1)
                ->where('awaiting.data.0.uuid', $article->uuid)
                ->where('awaiting.data.0.amountDue', self::money(150000))
                ->where('payments', null)
                ->where('stats.click.amount', self::money(200000))
                ->where('stats.awaiting.count', 1)
                ->where('stats.awaiting.amount', self::money(150000))
                ->where('can.confirm', true)
            );
    }

    public function test_payments_tab_lists_payments(): void
    {
        Payment::factory()->paid()->count(2)->create(['provider' => PaymentProvider::Payme]);
        Payment::factory()->create(['provider' => PaymentProvider::Click]);

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index', ['tab' => 'payme']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'payme')
                ->has('payments.data', 2)
                ->where('awaiting', null)
            );
    }

    public function test_admin_confirms_payment_manually(): void
    {
        $admin = $this->admin();
        $article = $this->awaitingArticle();

        $this->actingAs($admin)
            ->post(route('admin.payments.confirm', $article->uuid), [
                ...$this->confirmation(),
                'proof' => UploadedFile::fake()->create('kvitansiya.pdf', 100),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payment = Payment::query()->with('items')->firstOrFail();
        $this->assertSame(PaymentProvider::Manual, $payment->provider);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentPurpose::Publication, $payment->purpose);
        $this->assertSame($article->submitter_id, $payment->user_id);
        $this->assertSame($admin->id, $payment->confirmed_by);
        $this->assertSame('TP-2026-0042', $payment->provider_transaction_id);
        $this->assertSame(sprintf('PAY-%05d', $payment->id), $payment->receipt_number);
        $this->assertCount(1, $payment->items);
        $this->assertSame(150000.0, (float) $payment->items[0]->total);

        $proof = $payment->meta['proof_path'] ?? null;
        $this->assertIsString($proof);
        Storage::disk('local')->assertExists($proof);

        $article->refresh();
        $this->assertSame(ArticleStatus::Submitted, $article->status);
        $this->assertSame(ArticlePaymentStatus::Paid, $article->payment_status);
        $this->assertNotNull($article->paid_at);
        $this->assertDatabaseHas('article_status_histories', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::AwaitingPayment->value,
            'to_status' => ArticleStatus::Submitted->value,
            'changed_by' => $admin->id,
        ]);

        // Kvitansiyani yuklab olish
        $this->actingAs($admin)
            ->get(route('admin.payments.proof', $payment->uuid))
            ->assertOk()
            ->assertDownload('kvitansiya.pdf');
    }

    public function test_confirmation_is_validated(): void
    {
        $article = $this->awaitingArticle();
        Payment::factory()->paid()->create([
            'provider' => PaymentProvider::Manual,
            'provider_transaction_id' => 'TP-2026-0042',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.confirm', $article->uuid), $this->confirmation([
                'amount' => 0,
                'paid_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'proof' => UploadedFile::fake()->create('virus.exe', 10),
            ]))
            ->assertSessionHasErrors(['amount', 'paid_at', 'reference', 'proof']);

        $this->assertSame(ArticleStatus::AwaitingPayment, $article->refresh()->status);
    }

    public function test_article_not_awaiting_payment_cannot_be_confirmed(): void
    {
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne();

        $this->actingAs($this->admin())
            ->post(route('admin.payments.confirm', $article->uuid), $this->confirmation())
            ->assertSessionHasErrors(['article']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_waives_payment(): void
    {
        $admin = $this->admin();
        $article = $this->awaitingArticle();

        $this->actingAs($admin)
            ->post(route('admin.payments.waive', $article->uuid), ['reason' => 'Tahririyat taklifi bilan yozilgan'])
            ->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::Submitted, $article->status);
        $this->assertSame(ArticlePaymentStatus::Waived, $article->payment_status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_editor_can_view_but_not_confirm(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->createOne();
        $article = $this->awaitingArticle();

        $this->actingAs($editor)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('can.confirm', false));

        $this->actingAs($editor)
            ->post(route('admin.payments.confirm', $article->uuid), $this->confirmation())
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('admin.payments.waive', $article->uuid), ['reason' => 'Sinov uchun'])
            ->assertForbidden();
    }

    public function test_author_sees_payment_block_with_requisites(): void
    {
        config(['journal.payment' => ['bank' => 'Xalq banki', 'account' => '20212000000000000001', 'mfo' => null]]);

        $article = $this->awaitingArticle();

        $this->actingAs($article->submitter)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.awaiting', true)
                ->where('payment.amount', self::money(150000))
                ->where('payment.requisites.bank', 'Xalq banki')
                ->missing('payment.requisites.mfo')
            );

        $this->actingAs($this->admin())->post(route('admin.payments.confirm', $article->uuid), $this->confirmation());

        $this->actingAs($article->submitter)
            ->get(route('cabinet.articles.show', $article->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.awaiting', false)
                ->where('payment.status', ArticlePaymentStatus::Paid->value)
                ->where('payment.receipt', Payment::query()->firstOrFail()->receipt_number)
            );
    }
}

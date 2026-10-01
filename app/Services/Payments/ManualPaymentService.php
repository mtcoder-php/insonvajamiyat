<?php

namespace App\Services\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use App\Services\Articles\ArticleWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nashr to'lovini admin tomonidan qo'lda tasdiqlash (bank o'tkazmasi, kassa va h.k.)
 * yoki maqolani to'lovdan ozod qilish.
 *
 * Tasdiqlashda: payments (provider=manual, status=paid) + payment_items (maqola turi snapshot),
 * maqola payment_status=paid, holat "To'lov kutilmoqda" → "Yuborildi" (tahririyat navbati).
 * Click/Payme integratsiyasi alohida servisda bo'ladi; bu servis ular bilan bir xil jadvallarni ishlatadi.
 */
class ManualPaymentService
{
    /** To'lov hujjati (kvitansiya) uchun maxfiy disk */
    public const DISK = 'local';

    public function __construct(private readonly ArticleWorkflow $workflow) {}

    /** Maqola turi bo'yicha to'lanishi kerak bo'lgan summa (so'm) */
    public function amountDue(Article $article): float
    {
        return (float) $article->articleType()->value('price');
    }

    /**
     * @param  array{amount: float, paid_at: CarbonInterface, reference: string|null, note: string|null}  $data
     */
    public function confirm(Article $article, User $admin, array $data, ?UploadedFile $proof = null): Payment
    {
        $this->ensureAwaitingPayment($article);

        return DB::transaction(function () use ($article, $admin, $data, $proof): Payment {
            $type = $article->articleType()->firstOrFail();

            $payment = new Payment([
                'user_id' => $article->submitter_id,
                'purpose' => PaymentPurpose::Publication,
                'article_id' => $article->id,
                'amount' => $data['amount'],
                'currency' => $type->currency,
                'provider' => PaymentProvider::Manual,
            ]);
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'provider_transaction_id' => $data['reference'],
                'paid_at' => $data['paid_at'],
                'confirmed_by' => $admin->id,
                'confirmation_note' => $data['note'],
            ])->save();

            if ($proof !== null) {
                $proofPath = $this->storeProof($payment, $proof);
                $payment->forceFill(['meta' => [
                    'proof_path' => $proofPath,
                    'proof_name' => $proof->getClientOriginalName(),
                ]]);
            }

            $payment->forceFill(['receipt_number' => self::receiptNumber($payment)])->save();

            $payment->items()->create([
                'article_type_id' => $type->id,
                'name' => $type->getTranslations('name'),
                'quantity' => 1,
                'unit_price' => $data['amount'],
                'total' => $data['amount'],
            ]);

            $article->forceFill([
                'payment_status' => ArticlePaymentStatus::Paid,
                'paid_at' => $data['paid_at'],
            ]);

            $this->workflow->transition(
                $article,
                ArticleStatus::Submitted,
                $admin,
                __("To'lov tasdiqlandi (:receipt). Maqola tahririyat navbatiga qo'shildi.", [
                    'receipt' => $payment->receipt_number,
                ]),
            );

            return $payment;
        });
    }

    /**
     * Maqolani to'lovdan ozod qilish (masalan, tahririyat taklifi bilan yozilgan maqola).
     */
    public function waive(Article $article, User $admin, string $reason): void
    {
        $this->ensureAwaitingPayment($article);

        DB::transaction(function () use ($article, $admin, $reason): void {
            $article->forceFill(['payment_status' => ArticlePaymentStatus::Waived]);

            $this->workflow->transition(
                $article,
                ArticleStatus::Submitted,
                $admin,
                __("Maqola nashr to'lovidan ozod qilindi: :reason", ['reason' => $reason]),
            );
        });
    }

    public function downloadProof(Payment $payment): StreamedResponse
    {
        $path = $payment->meta['proof_path'] ?? null;
        $name = $payment->meta['proof_name'] ?? null;

        abort_unless(is_string($path) && Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->download($path, is_string($name) ? $name : null);
    }

    /** PAY-00042 */
    public static function receiptNumber(Payment $payment): string
    {
        return sprintf('PAY-%05d', $payment->id);
    }

    private function ensureAwaitingPayment(Article $article): void
    {
        if ($article->status !== ArticleStatus::AwaitingPayment || $article->payment_status->isSettled()) {
            throw ValidationException::withMessages([
                'article' => __("Bu maqola to'lov kutilayotgan holatda emas."),
            ]);
        }
    }

    private function storeProof(Payment $payment, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $path = $file->storeAs("payments/{$payment->uuid}", 'proof.'.$extension, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Payment proof could not be stored.');
        }

        return $path;
    }
}

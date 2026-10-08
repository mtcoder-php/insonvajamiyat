<?php

namespace App\Services\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Article;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Payments\Click\ClickReversalClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * To'lovni qaytarish (TZ 4.1.4 "Qaytarish", 4.2.8): admin so'rovi → to'lov tizimi → yakun.
 *
 *  - Click:  Merchant API reversal darhol chaqiriladi; javobga qarab "Qaytarildi" yoki "Xato".
 *  - Payme:  so'rov "Jarayonda" bo'ladi; tranzaksiya Payme biznes kabinetida bekor qilinadi,
 *            Payme CancelTransaction (state 2 → -2) yuborganda yakunlanadi (completeFromPayme).
 *            Ochiq so'rov bo'lmasa Payme'ning bekor qilish urinishi rad etiladi (-31007).
 *  - Qo'lda: pul bank orqali qaytarilgach hujjat raqami bilan darhol yakunlanadi.
 *
 * Nashr to'lovi faqat rad etilgan yoki muallif qaytarib olgan maqola uchun qaytariladi.
 * Yakunlanganda: payments → refunded, maqola payment_status → refunded, muallifga xabar.
 */
class RefundService
{
    /** Nashr to'lovi qaytariladigan maqola holatlari */
    public const ARTICLE_STATUSES = [ArticleStatus::Rejected, ArticleStatus::Withdrawn];

    public function __construct(
        private readonly ClickReversalClient $click,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Qaytarish mumkin emasligi sababi (null — mumkin).
     */
    public static function blockedReason(Payment $payment): ?string
    {
        if ($payment->status !== PaymentStatus::Paid) {
            return self::text("Faqat muvaffaqiyatli to'lov qaytariladi.");
        }

        $open = $payment->relationLoaded('refunds')
            ? $payment->refunds->contains(fn (Refund $r): bool => $r->status === RefundStatus::Requested || $r->status === RefundStatus::Processing)
            : $payment->refunds()->open()->exists();

        if ($open) {
            return self::text("Bu to'lov bo'yicha qaytarish so'rovi allaqachon ochilgan.");
        }

        if ($payment->purpose === PaymentPurpose::Publication) {
            $article = $payment->article;

            if ($article !== null && ! in_array($article->status, self::ARTICLE_STATUSES, true)) {
                return self::text("Nashr to'lovi faqat rad etilgan yoki muallif qaytarib olgan maqola uchun qaytariladi.");
            }
        }

        if ($payment->provider === PaymentProvider::Click) {
            if (! ClickReversalClient::configured()) {
                return self::text("Click sozlamalari to'liq emas (CLICK_MERCHANT_USER_ID, CLICK_SECRET_KEY).");
            }

            if (trim((string) $payment->provider_transaction_id) === '') {
                return self::text("Click to'lov raqami topilmadi.");
            }
        }

        if ($payment->provider === PaymentProvider::Payme && trim((string) $payment->provider_transaction_id) === '') {
            return self::text('Payme tranzaksiya raqami topilmadi.');
        }

        return null;
    }

    /**
     * Admin so'rovi. Click — darhol API, Payme — kabinetda bekor qilishni kutadi, qo'lda — darhol yakun.
     *
     * @throws ValidationException
     */
    public function request(Payment $payment, User $admin, string $reason, ?string $reference = null): Refund
    {
        $refund = DB::transaction(function () use ($payment, $admin, $reason, $reference): Refund {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $blocked = self::blockedReason($locked);

            if ($blocked !== null) {
                throw ValidationException::withMessages(['reason' => $blocked]);
            }

            $refund = new Refund;
            $refund->forceFill([
                'payment_id' => $locked->id,
                'requested_by' => $admin->id,
                'amount' => $locked->amount,
                'reason' => $reason,
                'reference' => $reference,
                'status' => $locked->provider === PaymentProvider::Manual ? RefundStatus::Requested : RefundStatus::Processing,
            ])->save();

            return $refund;
        });

        $refund->setRelation('payment', $payment);

        $this->audit->log(AuditEvent::RefundRequested, $payment, [
            'provider' => $payment->provider->value,
            'amount' => (float) $payment->amount,
            'receipt' => $payment->receipt_number,
            'reason' => $reason,
        ], actor: $admin);

        match ($payment->provider) {
            PaymentProvider::Click => $this->reverseViaClick($refund, $admin),
            PaymentProvider::Manual => $this->complete($refund, $admin),
            PaymentProvider::Payme => null, // CancelTransaction'ni kutadi
        };

        return $refund->refresh();
    }

    /**
     * Payme kabinetida bekor qilinmagan so'rovni yopish (admin fikridan qaytdi).
     */
    public function cancel(Refund $refund, User $admin): void
    {
        if ($refund->status !== RefundStatus::Processing && $refund->status !== RefundStatus::Requested) {
            return;
        }

        $refund->forceFill([
            'status' => RefundStatus::Failed,
            'error_message' => self::text("So'rov tahririyat tomonidan bekor qilindi."),
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ])->save();

        $this->audit->log(AuditEvent::RefundFailed, $refund->payment, [
            'refund' => $refund->id,
            'cancelled' => true,
        ], actor: $admin);
    }

    /**
     * Payme CancelTransaction: bajarilgan (state 2) tranzaksiyani bekor qilish.
     * Ochiq qaytarish so'rovi bo'lsagina ruxsat — aks holda false (Payme'ga -31007).
     *
     * @param  array<string, mixed>  $attributes  provider_state, provider_cancel_time, cancel_reason
     */
    public function completeFromPayme(Payment $payment, array $attributes): bool
    {
        $refund = Refund::query()
            ->where('payment_id', $payment->id)
            ->open()
            ->latest('id')
            ->first();

        if ($refund === null) {
            return false;
        }

        $refund->setRelation('payment', $payment);
        $this->complete($refund, null, $attributes);

        return true;
    }

    /**
     * Qaytarishni yakunlash: to'lov, maqola, so'rov holati; audit va muallifga xabar.
     *
     * @param  array<string, mixed>  $paymentAttributes
     * @param  array<string, mixed>  $response
     */
    public function complete(Refund $refund, ?User $actor, array $paymentAttributes = [], array $response = []): void
    {
        $payment = $refund->payment;

        $article = DB::transaction(function () use ($refund, $payment, $actor, $paymentAttributes, $response): ?Article {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                ...$paymentAttributes,
                'status' => PaymentStatus::Refunded,
                'refunded_at' => now(),
            ])->save();
            $payment->setRawAttributes($locked->getAttributes(), true);

            $refund->forceFill([
                'status' => RefundStatus::Completed,
                'processed_by' => $actor?->id,
                'processed_at' => now(),
                'provider_response' => $response !== [] ? $response : $refund->provider_response,
                'error_message' => null,
            ])->save();

            if ($locked->purpose !== PaymentPurpose::Publication || $locked->article_id === null) {
                return null;
            }

            $article = Article::query()->whereKey($locked->article_id)->lockForUpdate()->first();

            if ($article !== null && $article->payment_status === ArticlePaymentStatus::Paid) {
                $article->forceFill(['payment_status' => ArticlePaymentStatus::Refunded])->save();
            }

            return $article;
        });

        $this->audit->log(AuditEvent::PaymentRefunded, $payment, [
            'provider' => $payment->provider->value,
            'amount' => (float) $refund->amount,
            'receipt' => $payment->receipt_number,
            'reference' => $refund->reference ?? $refund->provider_refund_id,
        ], actor: $actor);

        if ($article === null) {
            return;
        }

        try {
            $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
                $article,
                ArticleUpdateNotification::PAYMENT,
                __("Nashr to'lovi qaytarildi"),
                self::text("Summa: :amount so'm (:provider). Mablag' to'lov qilingan karta yoki hisob raqamiga qaytariladi; bank muddatlari 1–10 ish kuni bo'lishi mumkin.", [
                    'amount' => number_format((float) $refund->amount, 0, '.', ' '),
                    'provider' => $payment->provider->label(),
                ]),
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function reverseViaClick(Refund $refund, User $admin): void
    {
        $result = $this->click->reverse($refund->payment);

        if ($result['ok']) {
            $refund->forceFill(['provider_refund_id' => (string) ($result['response']['payment_id'] ?? $refund->payment->provider_transaction_id)])->save();
            $this->complete($refund, $admin, [], $result['response']);

            return;
        }

        $refund->forceFill([
            'status' => RefundStatus::Failed,
            'provider_response' => $result['response'],
            'error_message' => mb_substr(($result['code'] !== null ? '['.$result['code'].'] ' : '').$result['note'], 0, 1000),
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ])->save();

        $this->audit->log(AuditEvent::RefundFailed, $refund->payment, [
            'refund' => $refund->id,
            'code' => $result['code'],
            'note' => $result['note'],
        ], actor: $admin);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function text(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }
}

<?php

namespace App\Services\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\Payment;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Onlayn to'lovni yakunlash va bekor qilish — Click va Payme uchun umumiy.
 *
 * markPaid: payments → paid (+ chek raqami), maqola payment_status → paid,
 * holat "To'lov kutilmoqda" → "Yuborildi" (tahririyat navbati), muallifga bildirishnoma.
 * Bildirishnoma/email xatosi to'lov javobini buzmaydi (to'lov tizimiga muvaffaqiyat qaytadi).
 */
class PaymentSettlement
{
    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly AuditLogger $audit,
    ) {}

    /** Maqola hozir to'lov qabul qila oladimi */
    public static function isPayable(?Article $article): bool
    {
        return $article !== null
            && $article->status === ArticleStatus::AwaitingPayment
            && ! $article->payment_status->isSettled();
    }

    /**
     * @param  array<string, mixed>  $attributes  Tizimga xos maydonlar (provider_state, provider_perform_time…)
     *
     * @throws PaymentNotPayable
     */
    public function markPaid(Payment $payment, array $attributes = []): Payment
    {
        $article = DB::transaction(function () use ($payment, $attributes): ?Article {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PaymentStatus::Paid) {
                $payment->setRawAttributes($locked->getAttributes(), true);

                return null;
            }

            $article = $locked->purpose === PaymentPurpose::Publication && $locked->article_id !== null
                ? Article::query()->whereKey($locked->article_id)->lockForUpdate()->first()
                : null;

            if (! self::isPayable($article)) {
                throw new PaymentNotPayable(alreadyPaid: $article !== null && $article->payment_status->isSettled());
            }

            $locked->forceFill([
                ...$attributes,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ])->save();
            $locked->forceFill(['receipt_number' => ManualPaymentService::receiptNumber($locked)])->save();

            /** @var Article $article */
            $article->forceFill([
                'payment_status' => ArticlePaymentStatus::Paid,
                'paid_at' => $locked->paid_at,
            ])->save();

            // Shu maqola uchun ochilib qolgan, lekin to'lov tizimiga yetmagan urinishlar yopiladi
            Payment::query()
                ->where('article_id', $article->id)
                ->whereKeyNot($locked->id)
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value, 'cancelled_at' => now()]);

            $payment->setRawAttributes($locked->getAttributes(), true);

            return $article;
        });

        if ($article === null) {
            return $payment;
        }

        $this->afterPaid($payment, $article);

        return $payment;
    }

    /**
     * To'lov tizimi tomonidan bekor qilingan tranzaksiya.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function cancel(Payment $payment, array $attributes = []): void
    {
        DB::transaction(function () use ($payment, $attributes): void {
            $payment->forceFill([
                ...$attributes,
                'status' => PaymentStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $this->releaseArticle($payment);
        });
    }

    /**
     * Onlayn tranzaksiya boshlanganda maqola "To'lov jarayonda" bo'ladi.
     */
    public function markArticlePending(Payment $payment): void
    {
        $article = $payment->article;

        if ($article !== null && $article->payment_status === ArticlePaymentStatus::Unpaid) {
            $article->forceFill(['payment_status' => ArticlePaymentStatus::Pending])->save();
        }
    }

    /** Boshqa faol tranzaksiya qolmagan bo'lsa — maqola yana "To'lanmagan" */
    private function releaseArticle(Payment $payment): void
    {
        $article = $payment->article;

        if ($article === null || $article->payment_status !== ArticlePaymentStatus::Pending) {
            return;
        }

        $active = Payment::query()
            ->where('article_id', $article->id)
            ->where('status', PaymentStatus::Processing->value)
            ->exists();

        if (! $active) {
            $article->forceFill(['payment_status' => ArticlePaymentStatus::Unpaid])->save();
        }
    }

    private function afterPaid(Payment $payment, Article $article): void
    {
        $this->audit->log(AuditEvent::PaymentPaidOnline, $payment, [
            'article' => EditorialWorkspace::code($article),
            'provider' => $payment->provider->value,
            'amount' => (float) $payment->amount,
            'receipt' => $payment->receipt_number,
            'transaction' => $payment->provider_transaction_id,
        ]);

        try {
            $this->workflow->transition(
                $article,
                ArticleStatus::Submitted,
                null,
                self::text("Onlayn to'lov qabul qilindi (:provider, :receipt). Maqola tahririyat navbatiga qo'shildi.", [
                    'provider' => $payment->provider->label(),
                    'receipt' => (string) $payment->receipt_number,
                ]),
            );
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
                $article,
                ArticleUpdateNotification::PAYMENT,
                __("Nashr to'lovi qabul qilindi"),
                self::text('Chek: :receipt. Summa: :amount so\'m. Maqolangiz tahririyat navbatiga qo\'shildi.', [
                    'receipt' => (string) $payment->receipt_number,
                    'amount' => number_format((float) $payment->amount, 0, '.', ' '),
                ]),
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function text(string $key, array $replace): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }
}

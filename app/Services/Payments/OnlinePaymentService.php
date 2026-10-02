<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Muallif tomonidan onlayn to'lovni boshlash (Click / Payme to'lov sahifasiga yo'naltirish).
 *
 * Har urinishda payments'da "pending" yozuv (+ payment_items snapshot) bo'ladi; uning id si
 * Click merchant_trans_id va Payme account[payment_id] sifatida yuboriladi. Bir xil tizim va
 * summa uchun ochiq urinish qayta ishlatiladi (bazada keraksiz yozuvlar ko'paymaydi).
 */
class OnlinePaymentService
{
    /**
     * Sozlangan va yoqilgan tizimlar.
     *
     * @return array<int, PaymentProvider>
     */
    public function providers(): array
    {
        return array_values(array_filter(
            PaymentProvider::online(),
            fn (PaymentProvider $provider): bool => $this->isEnabled($provider),
        ));
    }

    public function isEnabled(PaymentProvider $provider): bool
    {
        return match ($provider) {
            PaymentProvider::Click => (bool) config('payments.click.enabled')
                && self::filled('payments.click.service_id')
                && self::filled('payments.click.merchant_id')
                && self::filled('payments.click.secret_key'),
            PaymentProvider::Payme => (bool) config('payments.payme.enabled')
                && self::filled('payments.payme.merchant_id')
                && self::filled('payments.payme.key'),
            PaymentProvider::Manual => false,
        };
    }

    /**
     * @return string To'lov tizimi sahifasi manzili
     */
    public function start(Article $article, User $user, PaymentProvider $provider): string
    {
        if (! $this->isEnabled($provider)) {
            throw ValidationException::withMessages([
                'provider' => __(":provider orqali to'lov hozircha mavjud emas.", ['provider' => $provider->label()]),
            ]);
        }

        if (! PaymentSettlement::isPayable($article)) {
            throw ValidationException::withMessages([
                'provider' => __("Bu maqola to'lov kutilayotgan holatda emas."),
            ]);
        }

        $type = $article->articleType;
        $amount = round((float) $type->price, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'provider' => __("Maqola turi uchun narx belgilanmagan. Tahririyat bilan bog'laning."),
            ]);
        }

        $payment = Payment::query()
            ->where('article_id', $article->id)
            ->where('purpose', PaymentPurpose::Publication->value)
            ->where('provider', $provider->value)
            ->where('status', PaymentStatus::Pending->value)
            ->where('amount', $amount)
            ->latest('id')
            ->first();

        $payment ??= DB::transaction(function () use ($article, $user, $provider, $type, $amount): Payment {
            $payment = new Payment([
                'user_id' => $user->id,
                'purpose' => PaymentPurpose::Publication,
                'article_id' => $article->id,
                'amount' => $amount,
                'currency' => $type->currency,
                'provider' => $provider,
            ]);
            $payment->forceFill(['status' => PaymentStatus::Pending])->save();

            $payment->items()->create([
                'article_type_id' => $type->id,
                'name' => $type->getTranslations('name'),
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);

            return $payment;
        });

        return $this->checkoutUrl($payment);
    }

    public function checkoutUrl(Payment $payment): string
    {
        $return = $this->returnUrl($payment);

        if ($payment->provider === PaymentProvider::Click) {
            return self::str(config('payments.click.checkout_url')).'?'.http_build_query([
                'service_id' => config('payments.click.service_id'),
                'merchant_id' => config('payments.click.merchant_id'),
                'merchant_user_id' => config('payments.click.merchant_user_id'),
                'amount' => self::clickAmount($payment),
                'transaction_param' => (string) $payment->id,
                'return_url' => $return,
            ]);
        }

        $base = config('payments.payme.test_mode')
            ? config('payments.payme.test_checkout_url')
            : config('payments.payme.checkout_url');

        $params = implode(';', [
            'm='.self::str(config('payments.payme.merchant_id')),
            'ac.'.self::str(config('payments.payme.account_key')).'='.$payment->id,
            'a='.self::tiyin($payment),
            'c='.$return,
            'l='.self::paymeLocale(),
        ]);

        return rtrim(self::str($base), '/').'/'.base64_encode($params);
    }

    public function returnUrl(Payment $payment): string
    {
        $article = $payment->article;

        return $article !== null
            ? route('cabinet.articles.show', ['article' => $article->uuid, 'payment' => 'return'])
            : route('cabinet.dashboard');
    }

    /**
     * Maqola bo'yicha tugallanmagan oxirgi onlayn urinish (kabinetda "tekshirilmoqda" holati uchun).
     */
    public function lastAttempt(Article $article): ?Payment
    {
        return Payment::query()
            ->where('article_id', $article->id)
            ->where('purpose', PaymentPurpose::Publication->value)
            ->whereIn('provider', array_map(fn (PaymentProvider $p): string => $p->value, PaymentProvider::online()))
            ->latest('id')
            ->first();
    }

    /** Payme summasi tiyinda */
    public static function tiyin(Payment $payment): int
    {
        return (int) round((float) $payment->amount * 100);
    }

    /** Click summasi: "150000.00" */
    public static function clickAmount(Payment $payment): string
    {
        return number_format((float) $payment->amount, 2, '.', '');
    }

    private static function paymeLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['uz', 'ru', 'en'], true) ? $locale : 'uz';
    }

    private static function filled(string $key): bool
    {
        $value = config($key);

        return (is_string($value) && trim($value) !== '') || is_int($value);
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}

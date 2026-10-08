<?php

namespace App\Services\Payments\Click;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Click Merchant API — to'lovni bekor qilish (reversal):
 *   DELETE https://api.click.uz/v2/merchant/payment/reversal/{service_id}/{payment_id}
 *   Auth: merchant_user_id:sha1(timestamp + secret_key):timestamp
 *
 * payment_id — Click to'lov raqami (SHOP API'dagi click_trans_id = payments.provider_transaction_id).
 * Cheklovlar (Click hujjati): joriy hisobot oyidagi to'lovlar; o'tgan oy to'lovi faqat oyning
 * 1-kunida va faqat onlayn karta bilan to'langan bo'lsa; UZCARD rad etishi mumkin.
 */
class ClickReversalClient
{
    public const BASE_URL = 'https://api.click.uz/v2/merchant';

    public static function configured(): bool
    {
        foreach (['service_id', 'merchant_user_id', 'secret_key'] as $key) {
            $value = config('payments.click.'.$key);

            if (! is_scalar($value) || trim((string) $value) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok: bool, code: int|null, note: string, response: array<string, mixed>}
     */
    public function reverse(Payment $payment): array
    {
        $serviceId = self::config('service_id');
        $paymentId = (string) $payment->provider_transaction_id;
        $timestamp = (string) now()->getTimestamp();
        $digest = sha1($timestamp.self::config('secret_key'));

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['Auth' => self::config('merchant_user_id').':'.$digest.':'.$timestamp])
                ->timeout(20)
                ->connectTimeout(10)
                ->delete(self::BASE_URL.'/payment/reversal/'.rawurlencode($serviceId).'/'.rawurlencode($paymentId));
        } catch (ConnectionException $e) {
            return ['ok' => false, 'code' => null, 'note' => 'Click bilan aloqa yo\'q: '.$e->getMessage(), 'response' => []];
        }

        /** @var array<string, mixed> $body */
        $body = is_array($response->json()) ? $response->json() : [];
        $code = isset($body['error_code']) && is_numeric($body['error_code']) ? (int) $body['error_code'] : null;
        $note = isset($body['error_note']) && is_scalar($body['error_note']) ? trim((string) $body['error_note']) : '';

        return [
            'ok' => $response->successful() && $code === 0,
            'code' => $code,
            'note' => $note !== '' ? $note : 'HTTP '.$response->status(),
            'response' => ['http_status' => $response->status(), ...$body],
        ];
    }

    private static function config(string $key): string
    {
        $value = config('payments.click.'.$key);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}

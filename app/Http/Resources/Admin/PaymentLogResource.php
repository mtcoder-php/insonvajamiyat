<?php

namespace App\Http\Resources\Admin;

use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin → To'lovlar → "So'rovlar jurnali": Click / Payme webhook chaqiruvi va javobi.
 * Imzo va kalitga o'xshash maydonlar ko'rsatilmaydi (•••).
 *
 * @mixin PaymentLog
 */
class PaymentLogResource extends JsonResource
{
    /** Qiymati yashiriladigan kalitlar (kichik harfda, qisman moslik) */
    private const SECRET_KEYS = ['sign', 'password', 'secret', 'token', 'authorization', 'key'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ok = $this->error_code === null || $this->error_code === 0;

        return [
            'id' => $this->id,
            'provider' => $this->provider->value,
            'providerLabel' => $this->provider->label(),
            'action' => $this->action,
            'ok' => $ok,
            'errorCode' => $this->error_code,
            'signatureValid' => $this->signature_valid,
            'httpStatus' => $this->http_status,
            'ip' => $this->ip,
            'durationMs' => $this->duration_ms,
            'createdAt' => $this->created_at?->toIso8601String(),
            'payment' => $this->payment !== null ? [
                'uuid' => $this->payment->uuid,
                'receipt' => $this->payment->receipt_number ?? '#'.$this->payment->id,
                'transaction' => $this->payment->provider_transaction_id,
            ] : null,
            'request' => self::mask($this->request),
            'response' => $this->response !== null ? self::mask($this->response) : null,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::mask($value);

                continue;
            }

            if (is_string($key) && $value !== null && $value !== '' && self::isSecret($key)) {
                $data[$key] = '•••';
            }
        }

        return $data;
    }

    private static function isSecret(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SECRET_KEYS as $secret) {
            if (str_contains($key, $secret)) {
                return true;
            }
        }

        return false;
    }
}

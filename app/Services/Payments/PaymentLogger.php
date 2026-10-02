<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * Click / Payme so'rovlarini payment_logs jadvaliga yozish.
 * Jurnalga yozishdagi xato to'lov javobini hech qachon buzmaydi.
 */
class PaymentLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(PaymentProvider $provider, Request $request, array $payload, WebhookResult $result, float $startedAt): void
    {
        try {
            $paymentId = $result->paymentId !== null && Payment::query()->whereKey($result->paymentId)->exists()
                ? $result->paymentId
                : null;

            PaymentLog::query()->create([
                'payment_id' => $paymentId,
                'provider' => $provider,
                'action' => mb_substr($result->action, 0, 50),
                'request' => $payload,
                'response' => $result->body,
                'http_status' => 200,
                'error_code' => $result->errorCode,
                'signature_valid' => $result->signatureValid,
                'ip' => $request->ip(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

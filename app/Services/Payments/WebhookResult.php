<?php

namespace App\Services\Payments;

/**
 * To'lov tizimi so'roviga javob: javob tanasi + jurnal (payment_logs) uchun ma'lumot.
 */
final readonly class WebhookResult
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public array $body,
        public string $action,
        public ?int $paymentId = null,
        public ?int $errorCode = null,
        public ?bool $signatureValid = null,
    ) {}
}

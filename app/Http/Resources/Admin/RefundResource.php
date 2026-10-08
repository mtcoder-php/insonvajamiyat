<?php

namespace App\Http\Resources\Admin;

use App\Enums\PaymentProvider;
use App\Enums\RefundStatus;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin → To'lovlar → "Qaytarishlar" jadvali va to'lov panelidagi qaytarish tarixi.
 *
 * @mixin Refund
 */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->payment;
        $open = $this->status === RefundStatus::Requested || $this->status === RefundStatus::Processing;

        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'open' => $open,
            'reference' => $this->reference ?? $this->provider_refund_id,
            'error' => $this->error_message,
            'requestedBy' => $this->requester->name,
            'processedBy' => $this->processor?->name,
            'createdAt' => $this->created_at?->toIso8601String(),
            'processedAt' => $this->processed_at?->toIso8601String(),
            'payment' => [
                'uuid' => $payment->uuid,
                'receipt' => $payment->receipt_number ?? '#'.$payment->id,
                'transaction' => $payment->provider_transaction_id,
                'provider' => $payment->provider->value,
                'providerLabel' => $payment->provider->label(),
                'user' => ['name' => $payment->user->name, 'email' => $payment->user->email],
                'article' => $payment->article?->title,
            ],
            // Payme: kabinetda bekor qilinishi kutilmoqda
            'awaitingProvider' => $open && $payment->provider === PaymentProvider::Payme,
            'urls' => [
                'cancel' => $open ? route('admin.payments.refunds.cancel', $this->id) : null,
            ],
        ];
    }
}

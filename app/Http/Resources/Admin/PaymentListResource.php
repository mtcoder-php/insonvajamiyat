<?php

namespace App\Http\Resources\Admin;

use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin → To'lovlar jadvali qatori va "To'lov tafsilotlari" paneli.
 *
 * @mixin Payment
 */
class PaymentListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $proof = $this->meta['proof_path'] ?? null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'receipt' => $this->receipt_number ?? '#'.$this->id,
            'reference' => $this->provider_transaction_id,
            'purpose' => $this->purpose->value,
            'purposeLabel' => $this->purpose->label(),
            'article' => $this->article !== null ? [
                'uuid' => $this->article->uuid,
                'title' => $this->article->title,
            ] : null,
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'items' => $this->items->map(fn (PaymentItem $item): array => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
            ])->all(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider->value,
            'providerLabel' => $this->provider->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'paidAt' => $this->paid_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'confirmedBy' => $this->confirmedBy?->name,
            'note' => $this->confirmation_note,
            'proofName' => is_string($proof) ? ($this->meta['proof_name'] ?? 'kvitansiya') : null,
            'proofUrl' => is_string($proof) ? route('admin.payments.proof', $this->uuid) : null,
        ];
    }
}

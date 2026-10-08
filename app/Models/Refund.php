<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * To'lovni qaytarish (TZ 4.1.4 "Qaytarish", 4.2.8 "Qaytarilgan to'lovlar tarixi").
 *
 * Click — Merchant API reversal orqali darhol; Payme — biznes kabinetda bekor qilinadi va
 * Payme CancelTransaction (state -2) yuborganda yakunlanadi; qo'lda — bank hujjati bilan.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $requested_by
 * @property int|null $processed_by
 * @property string $amount
 * @property string $reason
 * @property RefundStatus $status
 * @property string|null $provider_refund_id
 * @property string|null $reference
 * @property array<string, mixed>|null $provider_response
 * @property string|null $error_message
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment $payment
 * @property-read User $requester
 * @property-read User|null $processor
 */
#[Fillable(['payment_id', 'requested_by', 'amount', 'reason', 'status', 'reference'])]
class Refund extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => 'decimal:2',
            'provider_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Hali yakunlanmagan (so'ralgan yoki to'lov tizimi javobini kutayotgan) so'rovlar.
     *
     * @param  Builder<Refund>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [RefundStatus::Requested->value, RefundStatus::Processing->value]);
    }
}

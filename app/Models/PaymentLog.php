<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Click / Payme so'rovlari jurnali (har bir webhook chaqiruvi va javobi).
 *
 * @property int $id
 * @property int|null $payment_id
 * @property PaymentProvider $provider
 * @property string $action
 * @property array<string, mixed> $request
 * @property array<string, mixed>|null $response
 * @property int|null $http_status
 * @property int|null $error_code
 * @property bool|null $signature_valid
 * @property string|null $ip
 * @property int|null $duration_ms
 * @property Carbon|null $created_at
 * @property-read Payment|null $payment
 */
#[Fillable(['payment_id', 'provider', 'action', 'request', 'response', 'http_status', 'error_code', 'signature_valid', 'ip', 'duration_ms', 'created_at'])]
class PaymentLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'request' => 'array',
            'response' => 'array',
            'signature_valid' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

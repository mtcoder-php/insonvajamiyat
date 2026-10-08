<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * To'lov (Click / Payme / qo'lda). To'lov oqimi — to'lovlar modulida.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property PaymentPurpose $purpose
 * @property int|null $article_id
 * @property int|null $journal_issue_id
 * @property string $amount
 * @property string $currency
 * @property PaymentProvider $provider
 * @property PaymentStatus $status
 * @property string|null $receipt_number
 * @property string|null $provider_transaction_id
 * @property string|null $provider_paydoc_id
 * @property int|null $provider_state
 * @property int|null $provider_create_time
 * @property int|null $provider_perform_time
 * @property int|null $provider_cancel_time
 * @property int|null $cancel_reason
 * @property array<string, mixed>|null $meta
 * @property int|null $confirmed_by
 * @property string|null $confirmation_note
 * @property Carbon|null $paid_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $refunded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Article|null $article
 * @property-read User|null $confirmedBy
 * @property-read Collection<int, PaymentItem> $items
 * @property-read Collection<int, PaymentLog> $logs
 * @property-read Collection<int, Refund> $refunds
 */
#[Fillable(['user_id', 'purpose', 'article_id', 'journal_issue_id', 'amount', 'currency', 'provider', 'receipt_number'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'provider_state' => 'integer',
            'provider_create_time' => 'integer',
            'provider_perform_time' => 'integer',
            'provider_cancel_time' => 'integer',
            'cancel_reason' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /** @return HasMany<PaymentItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** @return HasMany<PaymentLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid->value)->whereNotNull('paid_at');
    }
}

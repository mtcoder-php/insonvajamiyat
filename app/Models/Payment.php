<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Article|null $article
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

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid->value)->whereNotNull('paid_at');
    }
}

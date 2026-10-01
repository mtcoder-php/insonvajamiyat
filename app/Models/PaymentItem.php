<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * To'lov qatori: maqola turi yoki xizmat, nomi va narxi to'lov paytidagi holatda (snapshot).
 *
 * @property int $id
 * @property int $payment_id
 * @property int|null $article_type_id
 * @property int|null $service_id
 * @property string $name
 * @property int $quantity
 * @property string $unit_price
 * @property string $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment $payment
 */
#[Fillable(['payment_id', 'article_type_id', 'service_id', 'name', 'quantity', 'unit_price', 'total'])]
class PaymentItem extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

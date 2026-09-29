<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * articles.payment_status — maqola darajasidagi umumiy to'lov holati.
 * (Tranzaksiya darajasidagi holat — PaymentStatus.)
 */
enum ArticlePaymentStatus: string
{
    use EnumHelpers;

    case Unpaid = 'unpaid';       // To'lanmagan
    case Pending = 'pending';     // To'lov jarayonda (Click/Payme'da ochilgan)
    case Paid = 'paid';           // To'langan
    case Refunded = 'refunded';   // Qaytarilgan
    case Waived = 'waived';       // To'lovdan ozod (bepul tur yoki admin qarori)

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => __("To'lanmagan"),
            self::Pending => __("To'lov jarayonda"),
            self::Paid => __("To'langan"),
            self::Refunded => __('Qaytarilgan'),
            self::Waived => __("To'lovdan ozod"),
        };
    }

    public function isSettled(): bool
    {
        return $this->is(self::Paid, self::Waived);
    }
}

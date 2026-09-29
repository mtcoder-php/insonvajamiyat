<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * payments.status — bitta tranzaksiya holati (TZ 4.1.4).
 */
enum PaymentStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';         // Yaratildi, to'lov tizimiga yo'naltirildi
    case Processing = 'processing';   // Payme: CreateTransaction (state=1) / Click: Prepare o'tdi
    case Paid = 'paid';               // Muvaffaqiyatli (Payme state=2 / Click Complete)
    case Cancelled = 'cancelled';     // Bekor qilingan (Payme state=-1)
    case Failed = 'failed';           // Xato
    case Refunded = 'refunded';       // Qaytarilgan (Payme state=-2)

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Kutilmoqda'),
            self::Processing => __('Jarayonda'),
            self::Paid => __('Muvaffaqiyatli'),
            self::Cancelled => __('Bekor qilingan'),
            self::Failed => __('Xato'),
            self::Refunded => __('Qaytarilgan'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'amber',
            self::Paid => 'green',
            self::Cancelled => 'gray',
            self::Failed => 'red',
            self::Refunded => 'purple',
        };
    }

    public function isFinal(): bool
    {
        return $this->is(self::Paid, self::Cancelled, self::Failed, self::Refunded);
    }

    /** Payme protokolidagi "state" qiymatiga moslashtirish */
    public function paymeState(): ?int
    {
        return match ($this) {
            self::Processing => 1,
            self::Paid => 2,
            self::Cancelled => -1,
            self::Refunded => -2,
            default => null,
        };
    }
}

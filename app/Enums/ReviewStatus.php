<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** reviews.status — taqrizchi biriktiruvi holati */
enum ReviewStatus: string
{
    use EnumHelpers;

    case Invited = 'invited';       // Biriktirildi, javob kutilmoqda
    case Accepted = 'accepted';     // Taqrizchi qabul qildi, ishlamoqda
    case Declined = 'declined';     // Taqrizchi rad etdi
    case Completed = 'completed';   // Xulosa topshirildi
    case Cancelled = 'cancelled';   // Muharrir bekor qildi

    public function label(): string
    {
        return match ($this) {
            self::Invited => __('Taklif yuborildi'),
            self::Accepted => __('Jarayonda'),
            self::Declined => __('Rad etdi'),
            self::Completed => __('Yakunlandi'),
            self::Cancelled => __('Bekor qilindi'),
        };
    }

    public function isActive(): bool
    {
        return $this->is(self::Invited, self::Accepted);
    }
}

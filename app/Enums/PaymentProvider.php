<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PaymentProvider: string
{
    use EnumHelpers;

    case Click = 'click';
    case Payme = 'payme';
    case Manual = 'manual';   // Bank orqali to'langan, admin qo'lda tasdiqlagan

    public function label(): string
    {
        return match ($this) {
            self::Click => 'Click',
            self::Payme => 'Payme',
            self::Manual => __("Qo'lda tasdiqlangan"),
        };
    }

    /**
     * Muallif tanlay oladigan onlayn tizimlar
     *
     * @return array<int, self>
     */
    public static function online(): array
    {
        return [self::Click, self::Payme];
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PaymentProvider: string
{
    use EnumHelpers;

    case Click = 'click';
    case Payme = 'payme';
    case Manual = 'manual';   // Bank orqali to'langan, admin qo'lda tasdiqlagan

    /**
     * To'lov tizimining rasmiy logotipi: public/images/payments/ ichidagi nomida tizim kaliti
     * bo'lgan rasm (click.png, Payme-logo.svg …). Topilmasa — null (rangli belgi ko'rsatiladi).
     */
    public function logo(): ?string
    {
        if ($this === self::Manual) {
            return null;
        }

        $files = glob(public_path('images/payments/*.{png,svg,webp,jpg,jpeg}'), GLOB_BRACE) ?: [];
        sort($files);

        foreach ($files as $file) {
            if (str_contains(strtolower(pathinfo($file, PATHINFO_FILENAME)), $this->value)) {
                return asset('images/payments/'.rawurlencode(basename($file)));
            }
        }

        return null;
    }

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

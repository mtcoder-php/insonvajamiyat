<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Interfeys va kontent tillari (TZ 4.1.1: O'zbek / Rus / Ingliz) */
enum Language: string
{
    use EnumHelpers;

    case Uz = 'uz';
    case Ru = 'ru';
    case En = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Uz => "O'zbekcha",
            self::Ru => 'Русский',
            self::En => 'English',
        };
    }

    /** AI promptlarda ishlatiladigan to'liq nom */
    public function englishName(): string
    {
        return match ($this) {
            self::Uz => 'Uzbek',
            self::Ru => 'Russian',
            self::En => 'English',
        };
    }

    /** AI tarjimasi uchun maqsad tillar (manba — o'zbek) */
    public static function translationTargets(): array
    {
        return [self::Ru, self::En];
    }

    public static function default(): self
    {
        return self::Uz;
    }
}

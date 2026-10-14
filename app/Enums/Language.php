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

    /** Til nomi joriy interfeys tilida ("O'zbekcha" → "Узбекский"); label() — o'z tilidagi nomi */
    public function localName(): string
    {
        $key = match ($this) {
            self::Uz => "O'zbekcha",
            self::Ru => 'Ruscha',
            self::En => 'Inglizcha',
        };
        $text = __($key);

        return is_string($text) ? $text : $key;
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

    /**
     * AI tarjimasi uchun maqsad tillar (manba — o'zbek)
     *
     * @return array<int, self>
     */
    public static function translationTargets(): array
    {
        return [self::Ru, self::En];
    }

    public static function default(): self
    {
        return self::Uz;
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AiRequestType: string
{
    use EnumHelpers;

    case SpellCheck = 'spell_check';   // Imlo va uslub tekshiruvi (TZ 4.1.5)
    case Translation = 'translation';  // Ilmiy tarjima (TZ 4.1.6)
    case Analysis = 'analysis';        // Ilmiy uslub tahlili va baholash (AI Analytics)

    public function label(): string
    {
        return match ($this) {
            self::SpellCheck => __('Imlo va uslub tekshiruvi'),
            self::Translation => __('Ilmiy tarjima'),
            self::Analysis => __('Ilmiy uslub tahlili'),
        };
    }

    /** Dizayndagi AI Studio nomlari */
    public function studioName(): string
    {
        return match ($this) {
            self::SpellCheck => 'AI Proofreader',
            self::Translation => 'AI Translator',
            self::Analysis => 'AI Analytics',
        };
    }
}

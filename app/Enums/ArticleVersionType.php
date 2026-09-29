<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** article_versions.type — versiya qayerdan paydo bo'lgani */
enum ArticleVersionType: string
{
    use EnumHelpers;

    case Submission = 'submission';       // Dastlabki yuborish
    case Revision = 'revision';           // Muallif tuzatishi
    case AiCorrected = 'ai_corrected';    // AI imlo tekshiruvidan so'ng muallif tasdiqlagan matn
    case Translation = 'translation';     // AI tarjima (tasdiqlangan)
    case Layout = 'layout';               // Texnik xodim maketlagan variant

    public function label(): string
    {
        return match ($this) {
            self::Submission => __('Dastlabki'),
            self::Revision => __('Tuzatilgan'),
            self::AiCorrected => __('AI tekshiruvidan keyin'),
            self::Translation => __('Tarjima'),
            self::Layout => __('Maket'),
        };
    }
}

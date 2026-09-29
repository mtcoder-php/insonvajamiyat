<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Pullik xizmatlar katalogi (services.code).
 * Maqola nashri narxi article_types'da; bu yerda — qo'shimcha va mustaqil xizmatlar.
 */
enum ServiceCode: string
{
    use EnumHelpers;

    // Maqolaga qo'shimcha (addon) — maqola to'lovi bilan birga
    case FastTrack = 'fast_track';         // Tezkor ko'rib chiqish
    case ExtraPage = 'extra_page';         // Qo'shimcha sahifa (har 1 sahifa)

    // Mustaqil xizmatlar
    case Translation = 'translation';      // Ilmiy tarjima xizmati
    case AiCheck = 'ai_check';             // AI tekshiruv (limitdan ortiq)
    case IssuePdf = 'issue_pdf';           // Jurnal to'plami (PDF)

    public function label(): string
    {
        return match ($this) {
            self::FastTrack => __("Tezkor ko'rib chiqish"),
            self::ExtraPage => __("Qo'shimcha sahifa"),
            self::Translation => __('Tarjima xizmati'),
            self::AiCheck => __('AI tekshiruv'),
            self::IssuePdf => __("Jurnal to'plami (PDF)"),
        };
    }

    public function isArticleAddon(): bool
    {
        return $this->is(self::FastTrack, self::ExtraPage);
    }

    /** Narx birligi: sahifa bo'yicha yoki qat'iy */
    public function isPerUnit(): bool
    {
        return $this === self::ExtraPage;
    }
}

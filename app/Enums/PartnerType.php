<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** partners.type — bosh sahifadagi "Hamkorlarimiz" va indekslash bazalari */
enum PartnerType: string
{
    use EnumHelpers;

    case Partner = 'partner';     // Hamkor tashkilot
    case Indexing = 'indexing';   // Google Scholar, CrossRef va h.k.

    public function label(): string
    {
        return match ($this) {
            self::Partner => __('Hamkor'),
            self::Indexing => __('Indekslash bazasi'),
        };
    }
}

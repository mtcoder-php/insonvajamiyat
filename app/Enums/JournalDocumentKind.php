<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Mualliflar uchun yuklab olinadigan fayl turi (journal_documents.kind) */
enum JournalDocumentKind: string
{
    use EnumHelpers;

    case Template = 'template';   // Maqola shabloni (Word) — "Shablonni yuklab olish" tugmalari shunga olib boradi
    case Guide = 'guide';         // Yo'riqnoma, talablar (PDF)
    case Form = 'form';           // Ariza, mualliflik shartnomasi, ruxsatnoma shakllari
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Template => __('Maqola shabloni'),
            self::Guide => __("Yo'riqnoma va talablar"),
            self::Form => __('Ariza va shartnoma shakllari'),
            self::Other => __('Boshqa fayllar'),
        };
    }
}

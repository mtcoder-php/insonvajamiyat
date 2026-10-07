<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** backups.type — arxivga nima kiradi */
enum BackupType: string
{
    use EnumHelpers;

    case Full = 'full';           // Baza + fayllar
    case Database = 'database';   // Faqat baza
    case Files = 'files';         // Faqat yuklangan fayllar

    public function label(): string
    {
        return match ($this) {
            self::Full => __("To'liq (baza + fayllar)"),
            self::Database => __("Ma'lumotlar bazasi"),
            self::Files => __('Yuklangan fayllar'),
        };
    }

    public function includesDatabase(): bool
    {
        return $this !== self::Files;
    }

    public function includesFiles(): bool
    {
        return $this !== self::Database;
    }
}

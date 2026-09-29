<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** journal_issues.status */
enum IssueStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';           // Shakllantirilmoqda (faqat admin panelda)
    case Published = 'published';   // Chop etilgan (web qismda ko'rinadi)

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Qoralama'),
            self::Published => __('Chop etilgan'),
        };
    }
}

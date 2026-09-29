<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Taqrizchining tavsiyasi (reviews.recommendation) */
enum ReviewRecommendation: string
{
    use EnumHelpers;

    case Accept = 'accept';
    case MinorRevision = 'minor_revision';
    case MajorRevision = 'major_revision';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::Accept => __('Qabul qilish'),
            self::MinorRevision => __('Kichik tuzatishlar bilan'),
            self::MajorRevision => __('Jiddiy qayta ishlash kerak'),
            self::Reject => __('Rad etish'),
        };
    }
}

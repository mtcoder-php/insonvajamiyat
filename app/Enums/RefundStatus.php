<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum RefundStatus: string
{
    use EnumHelpers;

    case Requested = 'requested';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => __("So'raldi"),
            self::Processing => __('Jarayonda'),
            self::Completed => __('Qaytarildi'),
            self::Failed => __('Xato'),
        };
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AiRequestStatus: string
{
    use EnumHelpers;

    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('Navbatda'),
            self::Processing => __('Bajarilmoqda'),
            self::Completed => __('Tayyor'),
            self::Failed => __('Xato'),
        };
    }

    public function isFinished(): bool
    {
        return $this->is(self::Completed, self::Failed);
    }
}

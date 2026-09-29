<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** posts.type — Kontent-menejer boshqaradi (TZ 4.2.5) */
enum PostType: string
{
    use EnumHelpers;

    case News = 'news';
    case Announcement = 'announcement';

    public function label(): string
    {
        return match ($this) {
            self::News => __('Yangilik'),
            self::Announcement => __("E'lon"),
        };
    }
}

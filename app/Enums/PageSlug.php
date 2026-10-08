<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Admin'dan tahrirlanadigan statik sahifalar (pages.slug) */
enum PageSlug: string
{
    use EnumHelpers;

    case About = 'about';
    case Guidelines = 'guidelines';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::About => __('Jurnal haqida'),
            self::Guidelines => __("Mualliflar uchun yo'riqnoma"),
            self::Contact => __('Aloqa'),
        };
    }

    /** Saytdagi manzil (routes/web.php) */
    public function route(): string
    {
        return match ($this) {
            self::About => 'about',
            self::Guidelines => 'guidelines',
            self::Contact => 'contact',
        };
    }
}

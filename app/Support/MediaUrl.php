<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Yuklangan fayllar (muqova, banner, logo) uchun ommaviy URL.
 *
 * Fayllar `public` diskida saqlanadi (storage/app/public → public/storage,
 * `php artisan storage:link`). To'liq URL (http...) bo'lsa o'zgarishsiz qaytadi.
 */
final class MediaUrl
{
    public const DISK = 'public';

    public static function from(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /** Fayl hajmi (bayt) yoki fayl topilmasa null */
    public static function size(?string $path): ?int
    {
        if ($path === null || $path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->size($path);
    }
}

<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sayt kontenti (yangilik, tadbir, kitob, hamkor) uchun umumiy yordamchi:
 * rasmni public diskka saqlash / o'chirish va noyob slug yaratish.
 */
final class ContentMedia
{
    /**
     * Rasmni `{dir}/{prefix}-{tasodifiy}.{ext}` ko'rinishida saqlaydi.
     */
    public static function store(UploadedFile $file, string $dir, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $path = $file->storeAs($dir, $prefix.'-'.Str::lower(Str::random(10)).'.'.$extension, MediaUrl::DISK);

        if ($path === false) {
            throw new RuntimeException('Image could not be stored.');
        }

        return $path;
    }

    /**
     * Yuklangan faylni o'chiradi (tashqi http havolalar va bo'sh yo'llarga tegmaydi).
     */
    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk(MediaUrl::DISK)->delete($path);
    }

    /**
     * Rasmni almashtirish yoki olib tashlash: yangi yo'l (yoki null) qaytaradi, eskisini o'chiradi.
     */
    public static function replace(?string $current, ?UploadedFile $file, bool $remove, string $dir, string $prefix): ?string
    {
        if ($file !== null) {
            $path = self::store($file, $dir, $prefix);
            self::delete($current);

            return $path;
        }

        if ($remove) {
            self::delete($current);

            return null;
        }

        return $current;
    }

    /**
     * Sarlavhadan noyob slug: "yangi-son-chiqdi", band bo'lsa "yangi-son-chiqdi-2".
     * So'rov (masalan, withTrashed()) chaqiruvchi tomonidan beriladi.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function uniqueSlug(Builder $query, string $text, string $fallback): string
    {
        $base = rtrim(Str::substr(Str::slug(Str::ascii($text)), 0, 80), '-') ?: $fallback;
        $slug = $base;
        $i = 2;

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}

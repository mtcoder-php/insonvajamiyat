<?php

namespace App\Support;

/**
 * Serverning (php.ini) fayl yuklash chegarasi: upload_max_filesize va post_max_size ning kichigi.
 * Frontend shu bilan faylni yuborishdan oldin tekshiradi — aks holda PHP faylni jim tashlab yuboradi
 * va Laravel "The ... failed to upload." xatosini qaytaradi.
 */
final class UploadLimit
{
    public static function bytes(): int
    {
        $limits = array_filter([
            self::toBytes((string) ini_get('upload_max_filesize')),
            self::toBytes((string) ini_get('post_max_size')),
        ], fn (int $v): bool => $v > 0);

        return $limits === [] ? 0 : min($limits);
    }

    public static function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;

        return (int) match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}

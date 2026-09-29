<?php

namespace App\Support;

/**
 * Telefon raqamlarini yagona E.164 ko'rinishiga keltirish: +998901234567
 *
 * Qabul qilinadigan kiritish ko'rinishlari:
 *   "+998 90 123-45-67", "(+998) 90 1234567", "998901234567"  → +998901234567
 *   "90 123 45 67", "901234567" (O'zbekiston, 9 raqam)          → +998901234567
 *   "+7 701 123 45 67" (xorijiy)                               → +77011234567
 */
final class PhoneNumber
{
    /** E.164: "+" va 8–15 raqam, birinchisi 0 emas */
    public const PATTERN = '/^\+[1-9]\d{7,14}$/';

    private const UZ_COUNTRY_CODE = '998';

    private const UZ_NATIONAL_LENGTH = 9;

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $hasPlus = str_starts_with(ltrim($value), '+') || str_starts_with(ltrim($value), '(+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return $value;
        }

        if ($hasPlus) {
            return '+'.$digits;
        }

        // O'zbekiston milliy formati: 9 raqam (operator kodi + raqam)
        if (strlen($digits) === self::UZ_NATIONAL_LENGTH) {
            return '+'.self::UZ_COUNTRY_CODE.$digits;
        }

        // "998..." — "+" siz yozilgan xalqaro format
        return '+'.$digits;
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && preg_match(self::PATTERN, $value) === 1;
    }
}

<?php

namespace App\Support;

use App\Enums\Language;

/**
 * Tarjima qilinadigan maydonlar (spatie/laravel-translatable) uchun yordamchi:
 * forma {uz, ru, en} ko'rinishida yuboradi, bo'sh tillar saqlanmaydi.
 */
final class Translations
{
    /**
     * Interfeys matni (o'zbekcha kalit) — joriy tilga tarjima: lang/ru.json, lang/en.json.
     * Konfiguratsiyadagi yoki admin kiritgan matn lug'atda bo'lmasa — o'zgarishsiz qaytadi.
     */
    public static function line(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return is_string($value) ? $value : null;
        }

        $translated = __($value);

        return is_string($translated) ? $translated : $value;
    }

    /**
     * @return array<string, string>
     */
    public static function clean(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach (Language::cases() as $language) {
            $text = $value[$language->value] ?? null;

            if (is_string($text) && trim($text) !== '') {
                // Rasmli (multipart) formada brauzer qatorlarni \r\n ga aylantiradi — bir xil saqlaymiz
                $result[$language->value] = trim(str_replace(["\r\n", "\r"], "\n", $text));
            }
        }

        return $result;
    }

    /**
     * Validatsiya qoidalari: o'zbekcha majburiy (yoki ixtiyoriy), boshqalari ixtiyoriy.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(string $field, bool $required = true, int $max = 255): array
    {
        return [
            $field => [$required ? 'required' : 'nullable', 'array'],
            $field.'.uz' => [$required ? 'required' : 'nullable', 'string', 'max:'.$max],
            $field.'.ru' => ['nullable', 'string', 'max:'.$max],
            $field.'.en' => ['nullable', 'string', 'max:'.$max],
        ];
    }

    /**
     * Frontend formasi uchun: barcha tillar kalitlari bilan.
     *
     * @param  array<string, string>  $translations
     * @return array{uz: string, ru: string, en: string}
     */
    public static function form(array $translations): array
    {
        return [
            'uz' => $translations['uz'] ?? '',
            'ru' => $translations['ru'] ?? '',
            'en' => $translations['en'] ?? '',
        ];
    }

    /**
     * Validatsiya xabarlaridagi maydon nomlari: name.uz → "Nomi (o'zbekcha)"
     *
     * @return array<string, string>
     */
    public static function attributes(string $field, string $label): array
    {
        return [
            $field.'.uz' => $label." (o'zbekcha)",
            $field.'.ru' => $label.' (ruscha)',
            $field.'.en' => $label.' (inglizcha)',
        ];
    }
}

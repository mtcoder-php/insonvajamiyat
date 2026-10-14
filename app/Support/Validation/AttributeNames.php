<?php

namespace App\Support\Validation;

/**
 * FormRequest::attributes() dagi maydon nomlari o'zbekcha yoziladi ("boshlanish sanasi",
 * "Sarlavha (ruscha)"). Validator yaratilayotganda ular joriy tilga tarjima qilinadi —
 * xato xabari ("Поле «дата начала» обязательно") to'liq bir tilda chiqadi.
 * Lug'atda yo'q nom (masalan "ORCID") o'zgarishsiz qoladi.
 */
final class AttributeNames
{
    /**
     * @param  array<array-key, mixed>  $attributes
     * @return array<array-key, mixed>
     */
    public static function translate(array $attributes): array
    {
        if (app()->getLocale() === 'uz') {
            return $attributes;
        }

        foreach ($attributes as $field => $name) {
            if (is_string($name) && $name !== '') {
                $attributes[$field] = self::name($name);
            }
        }

        return $attributes;
    }

    public static function name(string $name): string
    {
        // Translations::attributes(): "Sarlavha (o'zbekcha)" — nom va til alohida tarjima qilinadi
        if (preg_match("/^(.+) \\((o'zbekcha|ruscha|inglizcha)\\)$/u", $name, $match) === 1) {
            return self::line($match[1]).' ('.self::line($match[2]).')';
        }

        return self::line($name);
    }

    private static function line(string $key): string
    {
        $text = __($key);

        return is_string($text) ? $text : $key;
    }
}

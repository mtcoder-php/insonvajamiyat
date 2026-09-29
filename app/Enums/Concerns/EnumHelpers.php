<?php

namespace App\Enums\Concerns;

/**
 * Barcha backed-enum'lar uchun umumiy yordamchi metodlar.
 * Enum'da label() metodi bo'lishi shart.
 */
trait EnumHelpers
{
    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Vue <select> komponentlari uchun: [['value' => 'x', 'label' => 'X'], ...]
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    /** Migratsiyalarda enum ustun yoki validatsiya uchun: "a,b,c" */
    public static function implode(string $separator = ','): string
    {
        return implode($separator, self::values());
    }

    public function is(self ...$cases): bool
    {
        return in_array($this, $cases, true);
    }
}

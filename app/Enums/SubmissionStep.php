<?php

namespace App\Enums;

/**
 * Yangi maqola yuborish formasining bosqichlari (muallif kabineti).
 * 1–5 — ma'lumot kiritish, 6 — tekshirish, 7 — rozilik va yuborish.
 */
enum SubmissionStep: int
{
    case Details = 1;
    case Authors = 2;
    case Abstract = 3;
    case Keywords = 4;
    case Files = 5;
    case Review = 6;
    case Submit = 7;

    public function label(): string
    {
        return match ($this) {
            self::Details => __("Maqola ma'lumotlari"),
            self::Authors => __('Mualliflar'),
            self::Abstract => __('Annotatsiya'),
            self::Keywords => __("Kalit so'zlar"),
            self::Files => __('Fayllar'),
            self::Review => __('Tekshirish'),
            self::Submit => __('Yuborish'),
        };
    }

    public function key(): string
    {
        return strtolower($this->name);
    }

    /** Ma'lumot kiritiladigan bosqichlar (to'liqligi tekshiriladi) */
    public function isDataStep(): bool
    {
        return $this->value <= self::Files->value;
    }

    public function next(): self
    {
        return self::tryFrom($this->value + 1) ?? self::Submit;
    }

    /**
     * Frontend uchun ro'yxat.
     *
     * @return array<int, array{number: int, key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $step): array => [
            'number' => $step->value,
            'key' => $step->key(),
            'label' => $step->label(),
        ], self::cases());
    }
}

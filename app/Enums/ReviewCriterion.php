<?php

namespace App\Enums;

/**
 * Taqriz baholash mezonlari (reviews.criteria_scores kalitlari), har biri 1–5 (0.5 qadam).
 */
enum ReviewCriterion: string
{
    case Relevance = 'relevance';
    case Novelty = 'novelty';
    case Methodology = 'methodology';
    case Results = 'results';
    case Conclusions = 'conclusions';
    case References = 'references';

    public function label(): string
    {
        return match ($this) {
            self::Relevance => __('Mavzuning dolzarbligi'),
            self::Novelty => __('Ilmiy yangilik'),
            self::Methodology => __('Tadqiqot metodologiyasi'),
            self::Results => __('Natijalar va tahlil'),
            self::Conclusions => __('Xulosa va tavsiyalar'),
            self::References => __('Adabiyotlar sifati'),
        };
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $c): array => ['key' => $c->value, 'label' => $c->label()], self::cases());
    }
}

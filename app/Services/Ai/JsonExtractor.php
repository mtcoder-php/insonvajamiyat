<?php

namespace App\Services\Ai;

/**
 * Model javobidan JSON obyektni ajratib olish (```json bloklari va atrofidagi matnga chidamli).
 */
final class JsonExtractor
{
    /**
     * @return array<string, mixed>|null
     */
    public static function decode(string $text): ?array
    {
        $text = trim($text);
        $text = (string) preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $text);

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        if (! is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}

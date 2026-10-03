<?php

namespace App\Services\Ai;

/**
 * Katta matnni xatboshi chegarasida bo'laklarga bo'lish (har biri ≤ $max belgi).
 * Har bo'lakning asl matndagi o'rni (offset, belgilar soni bo'yicha) saqlanadi —
 * imlo takliflarini asl matnga aniq joylashtirish uchun.
 */
final class TextChunker
{
    /**
     * @return array<int, array{offset: int, text: string}>
     */
    public static function split(string $text, int $max): array
    {
        $length = mb_strlen($text);

        if ($length <= $max) {
            return [['offset' => 0, 'text' => $text]];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($length, $start + $max);

            if ($end < $length) {
                $window = mb_substr($text, $start, $end - $start);
                $cut = self::boundary($window);
                $end = $start + ($cut > 0 ? $cut : mb_strlen($window));
            }

            $chunks[] = ['offset' => $start, 'text' => mb_substr($text, $start, $end - $start)];
            $start = $end;
        }

        return $chunks;
    }

    /** Oynadagi eng oxirgi qulay chegara: xatboshi → qator → gap oxiri → bo'shliq */
    private static function boundary(string $window): int
    {
        $min = (int) floor(mb_strlen($window) * 0.4);

        foreach (["\n\n", "\n", '. ', '! ', '? ', ' '] as $separator) {
            $position = mb_strrpos($window, $separator);

            if ($position !== false && $position >= $min) {
                return $position + mb_strlen($separator);
            }
        }

        return 0;
    }
}

<?php

namespace App\Support;

/**
 * PDF fayldagi betlar sonini aniqlash (tashqi kutubxonasiz).
 *
 * 1) Ildiz /Type /Pages lug'atidagi /Count — eng ishonchli (eng katta qiymat = jami betlar).
 *    PDF 1.5+ da bu lug'at siqilgan obyekt oqimida (ObjStm, FlateDecode) bo'lishi mumkin —
 *    shuning uchun FlateDecode oqimlar ham ochib ko'riladi.
 * 2) Topilmasa — alohida /Type /Page obyektlari sanaladi.
 *
 * Shifrlangan yoki buzilgan faylda null qaytadi (betlar soni qo'lda kiritiladi).
 */
final class PdfPageCounter
{
    /** Juda katta fayllarda xotirani asrash uchun ochiladigan oqimlar chegarasi */
    private const MAX_STREAMS = 2000;

    public static function count(string $path): ?int
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false ? null : self::countContent($content);
    }

    public static function countContent(string $content): ?int
    {
        if (! str_starts_with(ltrim($content), '%PDF')) {
            return null;
        }

        $chunks = [$content, ...self::inflatedStreams($content)];

        $count = 0;

        foreach ($chunks as $chunk) {
            $count = max($count, self::pagesCount($chunk));
        }

        if ($count > 0) {
            return $count;
        }

        $pages = 0;

        foreach ($chunks as $chunk) {
            $pages += preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', $chunk);
        }

        return $pages > 0 ? $pages : null;
    }

    /** /Type /Pages lug'atlaridagi eng katta /Count */
    private static function pagesCount(string $chunk): int
    {
        if (preg_match_all('#<<((?:(?!<<|>>).|<<(?:(?!<<|>>).)*>>)*)>>#s', $chunk, $dicts) === false) {
            return 0;
        }

        $max = 0;

        foreach ($dicts[1] as $dict) {
            if (preg_match('#/Type\s*/Pages(?![a-zA-Z])#', $dict) === 1
                && preg_match('#/Count\s+(\d+)#', $dict, $m) === 1) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }

    /**
     * FlateDecode bilan siqilgan oqimlarni ochish (obyekt oqimlari shu yerda bo'ladi).
     *
     * @return array<int, string>
     */
    private static function inflatedStreams(string $content): array
    {
        if (! function_exists('gzuncompress')) {
            return [];
        }

        $result = [];

        if (preg_match_all('#/FlateDecode[^>]*>>\s*stream\r?\n#', $content, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        foreach (array_slice($matches[0], 0, self::MAX_STREAMS) as [$marker, $offset]) {
            $start = $offset + strlen($marker);
            $end = strpos($content, 'endstream', $start);

            if ($end === false) {
                continue;
            }

            $raw = rtrim(substr($content, $start, $end - $start), "\r\n");
            $data = @gzuncompress($raw);

            if ($data === false) {
                $data = @gzinflate(substr($raw, 2));
            }

            // Faqat betlar daraxti bo'lishi mumkin bo'lgan oqimlar saqlanadi
            if (is_string($data) && str_contains($data, '/Type')) {
                $result[] = $data;
            }
        }

        return $result;
    }
}

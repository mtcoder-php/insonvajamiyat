<?php

namespace App\Support\Pdf;

/**
 * Kutubxonasiz oddiy PDF yozuvchi: sahifalar, matn (PDF ning standart 14 shriftidan —
 * Helvetica, Times), chiziq va to'rtburchak. Son PDF iga bet raqami / kolontitul bosish
 * (qpdf --overlay) va vaqtinchalik "jurnal ma'lumotlari" sahifasi uchun ishlatiladi.
 *
 * Matn WinAnsi (Windows-1252) kodlashida: lotin yozuvi, « » “ ” ‘ ’ — ha; kirill — yo'q
 * (bunday belgilar o'xshash lotin belgisiga yoki "?" ga almashadi).
 */
final class SimplePdf
{
    public const A4_WIDTH = 595.28;

    public const A4_HEIGHT = 841.89;

    /** Shriftlar: F1 Helvetica, F2 Helvetica-Bold, F3 Times-Italic, F4 Times-Bold */
    private const FONTS = [
        'F1' => 'Helvetica',
        'F2' => 'Helvetica-Bold',
        'F3' => 'Times-Italic',
        'F4' => 'Times-Bold',
    ];

    /** Helvetica belgi kengliklari (1/1000 em), ASCII 32–126 — AFM bo'yicha */
    private const HELVETICA = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    /** @var array<int, array{width: float, height: float, ops: array<int, string>}> */
    private array $pages = [];

    public function addPage(float $width = self::A4_WIDTH, float $height = self::A4_HEIGHT): int
    {
        $this->pages[] = ['width' => $width, 'height' => $height, 'ops' => []];

        return count($this->pages) - 1;
    }

    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * @param  'left'|'center'|'right'  $align  x — chap chet, markaz yoki o'ng chet
     * @param  array{0: float, 1: float, 2: float}  $color  RGB 0..1
     */
    public function text(int $page, float $x, float $y, string $text, float $size = 10, string $font = 'F1', string $align = 'left', array $color = [0, 0, 0]): void
    {
        $encoded = self::encode($text);

        if ($encoded === '') {
            return;
        }

        if ($align !== 'left') {
            $width = self::width($encoded, $size, $font);
            $x -= $align === 'center' ? $width / 2 : $width;
        }

        $this->pages[$page]['ops'][] = sprintf(
            'BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET',
            $color[0], $color[1], $color[2], $font, $size, $x, $y, self::escape($encoded),
        );
    }

    /**
     * Matnni berilgan kenglikka sig'diradi (so'zlar bo'yicha qatorlarga bo'ladi).
     *
     * @param  array{0: float, 1: float, 2: float}  $color
     * @param  'left'|'center'|'right'  $align
     * @return float oxirgi qatordan keyingi y
     */
    public function paragraph(int $page, float $x, float $y, float $maxWidth, string $text, float $size = 10, string $font = 'F1', float $leading = 1.4, array $color = [0, 0, 0], string $align = 'left'): float
    {
        foreach (self::wrap($text, $maxWidth, $size, $font) as $line) {
            $lineX = match ($align) {
                'center' => $x + $maxWidth / 2,
                'right' => $x + $maxWidth,
                default => $x,
            };
            $this->text($page, $lineX, $y, $line, $size, $font, $align, $color);
            $y -= $size * $leading;
        }

        return $y;
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    public function line(int $page, float $x1, float $y1, float $x2, float $y2, float $width = 0.5, array $color = [0, 0, 0]): void
    {
        $this->pages[$page]['ops'][] = sprintf(
            '%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S',
            $color[0], $color[1], $color[2], $width, $x1, $y1, $x2, $y2,
        );
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    public function rect(int $page, float $x, float $y, float $width, float $height, array $color): void
    {
        $this->pages[$page]['ops'][] = sprintf(
            '%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f',
            $color[0], $color[1], $color[2], $x, $y, $width, $height,
        );
    }

    public function output(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $fontIds = [];
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => ''];
        $next = 3;

        foreach (self::FONTS as $key => $base) {
            $fontIds[$key] = $next;
            $objects[$next++] = '<< /Type /Font /Subtype /Type1 /BaseFont /'.$base.' /Encoding /WinAnsiEncoding >>';
        }

        $fontRefs = implode(' ', array_map(fn (string $k, int $id): string => '/'.$k.' '.$id.' 0 R', array_keys($fontIds), $fontIds));
        $kids = [];

        foreach ($this->pages as $page) {
            $content = implode("\n", $page['ops']);
            $contentId = $next++;
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";

            $pageId = $next++;
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << %s >> >> /Contents %d 0 R >>',
                $page['width'], $page['height'], $fontRefs, $contentId,
            );
            $kids[] = $pageId.' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    /**
     * Kodlangan matn kengligi (pt). Times uchun Helvetica jadvali taxminan 0.93 koeffitsiyent bilan.
     */
    public static function width(string $encoded, float $size, string $font = 'F1'): float
    {
        $units = 0;

        foreach (str_split($encoded) as $char) {
            $code = ord($char);
            $units += $code >= 32 && $code <= 126 ? self::HELVETICA[$code - 32] : 556;
        }

        $factor = match ($font) {
            'F2' => 1.06,
            'F3', 'F4' => 0.93,
            default => 1.0,
        };

        return $units * $size * $factor / 1000;
    }

    /**
     * @return array<int, string> qatorlar (UTF-8)
     */
    public static function wrap(string $text, float $maxWidth, float $size, string $font = 'F1'): array
    {
        $lines = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $raw) {
            $current = '';

            foreach (preg_split('/\s+/u', trim($raw)) ?: [] as $word) {
                if ($word === '') {
                    continue;
                }

                $candidate = $current === '' ? $word : $current.' '.$word;

                if ($current !== '' && self::width(self::encode($candidate), $size, $font) > $maxWidth) {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $candidate;
                }
            }

            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * UTF-8 → Windows-1252. O'zbek lotin yozuvidagi ʻ ʼ → ‘ ’, № → No.
     */
    public static function encode(string $text): string
    {
        $text = strtr($text, ['ʻ' => '‘', 'ʼ' => '’', '№' => 'No. ', "\u{00A0}" => ' ']);
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        return $converted === false ? (string) preg_replace('/[^\x20-\x7E]/', '?', $text) : $converted;
    }

    private static function escape(string $encoded): string
    {
        return strtr($encoded, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }
}

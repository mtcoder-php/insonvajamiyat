<?php

namespace App\Support\Pdf;

use RuntimeException;

/**
 * Oddiy matnli hujjat PDF ko'rinishida (AI tarjima va tekshiruv natijasini yuklab olish).
 *
 * A4, Times (Liberation Serif) 12 pt, 1.5 interval, ikki tomonga tekislangan xatboshilar,
 * avtomatik sahifa ko'chishi va pastda bet raqami. Matn — Unicode (lotin va kirill).
 * DocxWriter bilan bir xil tuzilish: sarlavha, izoh qatori, xatboshilar.
 */
final class DocumentPdf
{
    private const LEFT = 85.0;   // 3 sm

    private const RIGHT = 42.5;  // 1.5 sm

    private const TOP = 57.0;    // 2 sm

    private const BOTTOM = 57.0;

    private const SIZE = 12.0;

    private const LEADING = 1.5;

    private const MUTED = [0.36, 0.42, 0.5];

    /**
     * @return string Vaqtinchalik fayl yo'li (javobdan keyin o'chiriladi)
     */
    public static function write(string $title, string $body, ?string $subtitle = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');

        if ($path === false || file_put_contents($path, self::render($title, $body, $subtitle)) === false) {
            throw new RuntimeException('Temporary PDF file could not be created.');
        }

        return $path;
    }

    public static function render(string $title, string $body, ?string $subtitle = null): string
    {
        $pdf = new SimplePdf;
        $width = SimplePdf::A4_WIDTH - self::LEFT - self::RIGHT;
        $center = self::LEFT + $width / 2;
        $page = $pdf->addPage();
        $y = SimplePdf::A4_HEIGHT - self::TOP - 16;

        foreach (SimplePdf::wrap($title, $width, 16, 'F4') as $line) {
            $pdf->text($page, $center, $y, $line, 16, 'F4', 'center');
            $y -= 21;
        }

        if ($subtitle !== null && $subtitle !== '') {
            $y -= 2;
            $pdf->text($page, $center, $y, $subtitle, 10.5, 'F3', 'center', self::MUTED);
            $y -= 14;
        }

        $y -= 14;
        $lineHeight = self::SIZE * self::LEADING;

        foreach (preg_split('/\n/u', str_replace("\r", '', $body)) ?: [] as $raw) {
            $raw = trim($raw);

            if ($raw === '') {
                $y -= $lineHeight / 2;

                continue;
            }

            $lines = SimplePdf::wrap($raw, $width, self::SIZE, 'F5');
            $last = count($lines) - 1;

            foreach ($lines as $i => $line) {
                if ($y < self::BOTTOM + 20) {
                    $page = $pdf->addPage();
                    $y = SimplePdf::A4_HEIGHT - self::TOP - self::SIZE;
                }

                // Ikki tomonga tekislash — xatboshining oxirgi qatoridan tashqari
                $spaces = substr_count($line, ' ');
                $extra = $width - SimplePdf::width($line, self::SIZE, 'F5');
                $spacing = $i < $last && $spaces > 0 && $extra > 0 && $extra < $width * 0.35 ? $extra / $spaces : 0.0;

                $pdf->text($page, self::LEFT, $y, $line, self::SIZE, 'F5', 'left', [0, 0, 0], 0.0, $spacing);
                $y -= $lineHeight;
            }

            $y -= 4;
        }

        for ($i = 0, $total = $pdf->count(); $i < $total; $i++) {
            $pdf->text($i, $center, self::BOTTOM - 24, $total > 1 ? ($i + 1).' / '.$total : '1', 9, 'F5', 'center', self::MUTED);
        }

        return $pdf->output();
    }
}

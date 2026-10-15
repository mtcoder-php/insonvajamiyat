<?php

namespace App\Support\Pdf;

/**
 * Kutubxonasiz oddiy PDF yozuvchi: sahifalar, matn, chiziq, to'rtburchak.
 * Son PDF iga ramka / bet raqami bosish (qpdf --overlay), "Jurnal haqida" va mundarija
 * sahifalari, AI tarjima/tekshiruv natijasini PDF qilib berish uchun ishlatiladi.
 *
 * Matn — Unicode (lotin, kirill, ʻ ʼ « » №): Liberation TrueType shriftlari PDF ga joylanadi
 * (resources/fonts/liberation), faqat ishlatilgan glyph'lar bilan (subset). Kodlash — Identity-H,
 * nusxa olish va qidiruv uchun ToUnicode jadvali qo'shiladi.
 */
final class SimplePdf
{
    public const A4_WIDTH = 595.28;

    public const A4_HEIGHT = 841.89;

    /** Shriftlar: F1 Sans, F2 Sans Bold, F3 Serif Italic, F4 Serif Bold, F5 Serif, F6 Sans Italic */
    private const FONTS = [
        'F1' => 'LiberationSans-Regular',
        'F2' => 'LiberationSans-Bold',
        'F3' => 'LiberationSerif-Italic',
        'F4' => 'LiberationSerif-Bold',
        'F5' => 'LiberationSerif-Regular',
        'F6' => 'LiberationSans-Italic',
    ];

    /** @var array<string, array<int, int>> shrift → ishlatilgan glyph → Unicode kod */
    private array $used = [];

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
     * @param  float  $wordSpacing  bo'shliqlardan keyingi qo'shimcha masofa (ikki tomonga tekislash uchun)
     */
    public function text(int $page, float $x, float $y, string $text, float $size = 10, string $font = 'F1', string $align = 'left', array $color = [0, 0, 0], float $tracking = 0.0, float $wordSpacing = 0.0): void
    {
        $text = self::clean($text);

        if ($text === '') {
            return;
        }

        $chars = mb_str_split($text);

        if ($align !== 'left') {
            $width = self::width($text, $size, $font) + $tracking * max(0, count($chars) - 1);
            $x -= $align === 'center' ? $width / 2 : $width;
        }

        $ttf = self::font($font);
        $parts = [];
        $run = '';

        foreach ($chars as $char) {
            $code = mb_ord($char);
            $gid = $ttf->glyph($code);
            $this->used[$font][$gid] ??= $code;
            $run .= sprintf('%04X', $gid);

            // Identity-H da Tw ishlamaydi — bo'shliqdan keyin TJ siljishi qo'yiladi
            if ($char === ' ' && $wordSpacing > 0) {
                $parts[] = '<'.$run.'> '.sprintf('%.2F', -$wordSpacing * 1000 / $size);
                $run = '';
            }
        }

        if ($run !== '') {
            $parts[] = '<'.$run.'>';
        }

        $this->pages[$page]['ops'][] = sprintf(
            'BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F Tc %.2F %.2F Td [%s] TJ ET',
            $color[0], $color[1], $color[2], $font, $size, $tracking, $x, $y, implode(' ', $parts),
        );
    }

    /**
     * Yumaloq burchakli to'rtburchak (to'ldirilgan va/yoki chiziqli).
     *
     * @param  array{0: float, 1: float, 2: float}|null  $fill
     * @param  array{0: float, 1: float, 2: float}|null  $stroke
     */
    public function roundRect(int $page, float $x, float $y, float $width, float $height, float $radius, ?array $fill, ?array $stroke = null, float $lineWidth = 0.6): void
    {
        $r = min($radius, $width / 2, $height / 2);
        $k = 0.5523 * $r;
        $path = sprintf(
            '%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c h',
            $x + $r, $y,
            $x + $width - $r, $y,
            $x + $width - $r + $k, $y, $x + $width, $y + $r - $k, $x + $width, $y + $r,
            $x + $width, $y + $height - $r,
            $x + $width, $y + $height - $r + $k, $x + $width - $r + $k, $y + $height, $x + $width - $r, $y + $height,
            $x + $r, $y + $height,
            $x + $r - $k, $y + $height, $x, $y + $height - $r + $k, $x, $y + $height - $r,
            $x, $y + $r,
            $x, $y + $r - $k, $x + $r - $k, $y, $x + $r, $y,
        );
        $ops = '';

        if ($fill !== null) {
            $ops .= sprintf('%.3F %.3F %.3F rg ', $fill[0], $fill[1], $fill[2]);
        }

        if ($stroke !== null) {
            $ops .= sprintf('%.3F %.3F %.3F RG %.2F w ', $stroke[0], $stroke[1], $stroke[2], $lineWidth);
        }

        $operator = $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S');
        $this->pages[$page]['ops'][] = $ops.$path.' '.$operator;
    }

    /**
     * To'rtburchak chegarasi (faqat chiziq).
     *
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    public function strokeRect(int $page, float $x, float $y, float $width, float $height, float $lineWidth, array $color): void
    {
        $this->pages[$page]['ops'][] = sprintf(
            '%.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re S',
            $color[0], $color[1], $color[2], $lineWidth, $x, $y, $width, $height,
        );
    }

    /**
     * Matnni berilgan kenglikka sig'diradi (so'zlar bo'yicha qatorlarga bo'ladi).
     *
     * @param  array{0: float, 1: float, 2: float}  $color
     * @param  'left'|'center'|'right'|'justify'  $align
     * @return float oxirgi qatordan keyingi y
     */
    public function paragraph(int $page, float $x, float $y, float $maxWidth, string $text, float $size = 10, string $font = 'F1', float $leading = 1.4, array $color = [0, 0, 0], string $align = 'left'): float
    {
        $lines = self::wrap($text, $maxWidth, $size, $font);
        $last = count($lines) - 1;

        foreach ($lines as $i => $line) {
            if ($align === 'justify') {
                // Ikki tomonga tekislash: oxirgi qatordan boshqalarida so'z oralig'i kengaytiriladi (Tw)
                $spaces = substr_count($line, ' ');
                $extra = $maxWidth - self::width($line, $size, $font);
                $spacing = $i < $last && $spaces > 0 && $extra > 0 ? $extra / $spaces : 0.0;
                $this->text($page, $x, $y, $line, $size, $font, 'left', $color, 0.0, $spacing);
            } else {
                $lineX = match ($align) {
                    'center' => $x + $maxWidth / 2,
                    'right' => $x + $maxWidth,
                    default => $x,
                };
                $this->text($page, $lineX, $y, $line, $size, $font, $align, $color);
            }

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

        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => ''];
        $next = 3;
        $fontRefs = '';

        foreach ($this->used as $key => $glyphs) {
            $fontId = $next;
            $next = $this->embedFont($objects, $next, $key, $glyphs);
            $fontRefs .= '/'.$key.' '.$fontId.' 0 R ';
        }

        $kids = [];

        foreach ($this->pages as $page) {
            $contentId = $next++;
            $objects[$contentId] = self::stream(implode("\n", $page['ops']));

            $pageId = $next++;
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << %s>> >> /Contents %d 0 R >>',
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
     * Type0 (Identity-H) shrift: CIDFontType2 + FontDescriptor + subset FontFile2 + ToUnicode.
     *
     * @param  array<int, string>  $objects
     * @param  array<int, int>  $glyphs  glyph → Unicode kod
     * @return int keyingi bo'sh obyekt raqami
     */
    private function embedFont(array &$objects, int $next, string $key, array $glyphs): int
    {
        $ttf = self::font($key);
        ksort($glyphs);

        $scale = 1000 / $ttf->unitsPerEm;
        $tag = substr(strtoupper(md5($key.implode(',', array_keys($glyphs)))), 0, 6);
        $tag = strtr($tag, '0123456789', 'GHIJKLMNOP');
        $name = $tag.'+'.str_replace('-Regular', '', $ttf->postScriptName);

        [$type0, $cid, $descriptor, $file, $unicode] = [$next, $next + 1, $next + 2, $next + 3, $next + 4];

        $widths = '';

        foreach (array_keys($glyphs) as $gid) {
            $widths .= $gid.' ['.round($ttf->width($gid)).'] ';
        }

        $flags = 32 | (in_array($key, ['F3', 'F6'], true) ? 64 : 0) | (in_array($key, ['F3', 'F4', 'F5'], true) ? 2 : 0);

        $objects[$type0] = sprintf('<< /Type /Font /Subtype /Type0 /BaseFont /%s /Encoding /Identity-H /DescendantFonts [%d 0 R] /ToUnicode %d 0 R >>', $name, $cid, $unicode);
        $objects[$cid] = sprintf(
            '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /%s /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor %d 0 R /CIDToGIDMap /Identity /DW 1000 /W [%s] >>',
            $name, $descriptor, trim($widths),
        );
        $objects[$descriptor] = sprintf(
            '<< /Type /FontDescriptor /FontName /%s /Flags %d /FontBBox [%d %d %d %d] /ItalicAngle %.2F /Ascent %d /Descent %d /CapHeight %d /StemV 80 /FontFile2 %d 0 R >>',
            $name, $flags,
            $ttf->bbox[0] * $scale, $ttf->bbox[1] * $scale, $ttf->bbox[2] * $scale, $ttf->bbox[3] * $scale,
            $ttf->italicAngle, $ttf->ascent * $scale, $ttf->descent * $scale, $ttf->capHeight * $scale, $file,
        );

        $program = $ttf->subset(array_keys($glyphs));
        $objects[$file] = self::stream($program, '/Length1 '.strlen($program));
        $objects[$unicode] = self::stream(self::toUnicode($glyphs));

        return $next + 5;
    }

    /**
     * @param  array<int, int>  $glyphs  glyph → Unicode kod
     */
    private static function toUnicode(array $glyphs): string
    {
        $entries = [];

        foreach ($glyphs as $gid => $code) {
            $utf16 = strtoupper(bin2hex((string) mb_convert_encoding(mb_chr($code), 'UTF-16BE', 'UTF-8')));
            $entries[] = sprintf('<%04X> <%s>', $gid, $utf16);
        }

        $body = '';

        foreach (array_chunk($entries, 100) as $chunk) {
            $body .= count($chunk)." beginbfchar\n".implode("\n", $chunk)."\nendbfchar\n";
        }

        return "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n"
            ."/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
            ."/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n"
            ."1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n"
            .$body
            ."endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
    }

    /** Siqilgan (FlateDecode) oqim obyekti */
    private static function stream(string $data, string $extra = ''): string
    {
        $compressed = gzcompress($data, 6);

        if ($compressed === false) {
            return '<< /Length '.strlen($data).($extra !== '' ? ' '.$extra : '')." >>\nstream\n".$data."\nendstream";
        }

        return '<< /Length '.strlen($compressed).' /Filter /FlateDecode'.($extra !== '' ? ' '.$extra : '')." >>\nstream\n".$compressed."\nendstream";
    }

    private static function font(string $key): TrueTypeFont
    {
        return TrueTypeFont::load(resource_path('fonts/liberation/'.(self::FONTS[$key] ?? self::FONTS['F1']).'.ttf'));
    }

    /**
     * Matn kengligi (pt) — shriftning haqiqiy glyph kengliklari bo'yicha.
     */
    public static function width(string $text, float $size, string $font = 'F1'): float
    {
        $ttf = self::font($font);
        $units = 0.0;

        foreach (mb_str_split(self::clean($text)) as $char) {
            $units += $ttf->width($ttf->glyph(mb_ord($char)));
        }

        return $units * $size / 1000;
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

                if ($current !== '' && self::width($candidate, $size, $font) > $maxWidth) {
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
     * Boshqaruv belgilari olib tashlanadi, maxsus bo'shliqlar oddiy bo'shliqqa; shriftda yo'q
     * tipografik belgilar o'xshashiga almashtiriladi (masalan, ʻ — shriftda bor, saqlanadi).
     */
    public static function clean(string $text): string
    {
        $text = strtr($text, ["\u{00A0}" => ' ', "\u{202F}" => ' ', "\t" => ' ', "\r" => '', "\n" => ' ']);

        return (string) preg_replace('/[\x{0000}-\x{001F}\x{007F}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
    }
}

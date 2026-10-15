<?php

namespace App\Support\Pdf;

use RuntimeException;

/**
 * TrueType (.ttf) shriftini o'qish va PDF ga joylash uchun kichraytirish (subset).
 *
 * - cmap (Unicode → glyph), hmtx (kengliklar), head/hhea/OS/2/post (o'lchamlar) o'qiladi;
 * - subset(): faqat ishlatilgan glyph'lar ma'lumoti qoladi, qolganlari bo'sh (glyph raqamlari
 *   o'zgarmaydi — PDF da CIDToGIDMap /Identity ishlatiladi). Murakkab (composite) glyph'larning
 *   tarkibiy qismlari ham qo'shiladi. Natija bir necha o'n KB bo'ladi (to'liq shrift ~400 KB).
 */
final class TrueTypeFont
{
    /** PDF FontFile2 uchun kerakli jadvallar */
    private const SUBSET_TABLES = ['cvt ', 'fpgm', 'glyf', 'head', 'hhea', 'hmtx', 'loca', 'maxp', 'prep'];

    /** @var array<string, self> */
    private static array $cache = [];

    /** @var array<string, array{offset: int, length: int}> */
    private array $tables = [];

    /** @var array<int, int> Unicode kod → glyph */
    private array $cmap = [];

    /** @var array<int, int> glyph → kenglik (shrift birligida) */
    private array $advances = [];

    /** @var array<int, int> glyph → glyf jadvalidagi boshlanish (oxirgisi — tugash) */
    private array $loca = [];

    public readonly int $unitsPerEm;

    public readonly int $ascent;

    public readonly int $descent;

    public readonly int $capHeight;

    public readonly float $italicAngle;

    /** @var array{0: int, 1: int, 2: int, 3: int} */
    public readonly array $bbox;

    private readonly int $numGlyphs;

    private readonly int $locaFormat;

    private function __construct(private readonly string $data, public readonly string $postScriptName)
    {
        $this->readDirectory();

        $head = $this->table('head');
        $this->unitsPerEm = $this->u16($head + 18);
        $this->bbox = [$this->i16($head + 36), $this->i16($head + 38), $this->i16($head + 40), $this->i16($head + 42)];
        $this->locaFormat = $this->i16($head + 50);

        $hhea = $this->table('hhea');
        $this->ascent = $this->i16($hhea + 4);
        $this->descent = $this->i16($hhea + 6);
        $metrics = $this->u16($hhea + 34);

        $this->numGlyphs = $this->u16($this->table('maxp') + 4);

        $os2 = $this->tables['OS/2']['offset'] ?? null;
        $this->capHeight = $os2 !== null && $this->u16($os2) >= 2 ? $this->i16($os2 + 88) : (int) round($this->ascent * 0.7);

        $post = $this->table('post');
        $this->italicAngle = $this->i16($post + 4) + $this->u16($post + 6) / 65536;

        $hmtx = $this->table('hmtx');
        $last = 0;

        for ($gid = 0; $gid < $this->numGlyphs; $gid++) {
            $last = $gid < $metrics ? $this->u16($hmtx + $gid * 4) : $last;
            $this->advances[$gid] = $last;
        }

        $locaOffset = $this->table('loca');

        for ($gid = 0; $gid <= $this->numGlyphs; $gid++) {
            $this->loca[$gid] = $this->locaFormat === 0
                ? $this->u16($locaOffset + $gid * 2) * 2
                : $this->u32($locaOffset + $gid * 4);
        }

        $this->readCmap();
    }

    public static function load(string $path): self
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $data = @file_get_contents($path);

        if ($data === false || strlen($data) < 12) {
            throw new RuntimeException("Font not found: {$path}");
        }

        return self::$cache[$path] = new self($data, pathinfo($path, PATHINFO_FILENAME));
    }

    /** Belgi glyph'i (shriftda bo'lmasa — 0, ".notdef") */
    public function glyph(int $codepoint): int
    {
        return $this->cmap[$codepoint] ?? 0;
    }

    public function hasGlyph(int $codepoint): bool
    {
        return isset($this->cmap[$codepoint]);
    }

    /** Glyph kengligi 1/1000 em da (PDF /W uchun) */
    public function width(int $gid): float
    {
        return ($this->advances[$gid] ?? 0) * 1000 / $this->unitsPerEm;
    }

    /**
     * Tanlangan glyph'lar qoldirilgan shrift fayli (FontFile2).
     *
     * @param  iterable<int>  $gids
     */
    public function subset(iterable $gids): string
    {
        $keep = [0 => true];

        foreach ($gids as $gid) {
            $this->collect((int) $gid, $keep);
        }

        $glyfOffset = $this->table('glyf');
        $glyf = '';
        $offsets = [];

        for ($gid = 0; $gid < $this->numGlyphs; $gid++) {
            $offsets[$gid] = strlen($glyf);

            if (isset($keep[$gid])) {
                $length = $this->loca[$gid + 1] - $this->loca[$gid];
                $glyf .= substr($this->data, $glyfOffset + $this->loca[$gid], $length);
                $glyf .= str_repeat("\0", (4 - strlen($glyf) % 4) % 4);
            }
        }

        $offsets[$this->numGlyphs] = strlen($glyf);

        // Qisqa loca formati 131 070 baytgacha — subset odatda ancha kichik
        $short = $this->locaFormat === 0 && strlen($glyf) <= 0x1FFFE;
        $loca = '';

        foreach ($offsets as $offset) {
            $loca .= $short ? pack('n', intdiv($offset, 2)) : pack('N', $offset);
        }

        $tables = [];

        foreach (self::SUBSET_TABLES as $tag) {
            if (! isset($this->tables[$tag])) {
                continue;
            }

            $tables[$tag] = match ($tag) {
                'glyf' => $glyf,
                'loca' => $loca,
                default => substr($this->data, $this->tables[$tag]['offset'], $this->tables[$tag]['length']),
            };
        }

        // head: checkSumAdjustment = 0, indexToLocFormat yangi loca'ga mos
        $head = substr($this->data, $this->table('head'), $this->tables['head']['length']);
        $head = substr_replace($head, "\0\0\0\0", 8, 4);
        $tables['head'] = substr_replace($head, pack('n', $short ? 0 : 1), 50, 2);

        [$font, $headAt] = $this->assemble($tables);
        $adjustment = (0xB1B0AFBA - self::checksum($font)) & 0xFFFFFFFF;

        return substr_replace($font, pack('N', $adjustment), $headAt + 8, 4);
    }

    /**
     * @param  array<int, true>  $keep
     */
    private function collect(int $gid, array &$keep): void
    {
        if ($gid < 0 || $gid >= $this->numGlyphs || isset($keep[$gid])) {
            return;
        }

        $keep[$gid] = true;
        $start = $this->table('glyf') + $this->loca[$gid];

        if ($this->loca[$gid + 1] === $this->loca[$gid] || $this->i16($start) >= 0) {
            return;
        }

        // Composite glyph: tarkibiy qismlar
        $at = $start + 10;

        do {
            $flags = $this->u16($at);
            $this->collect($this->u16($at + 2), $keep);
            $at += 4 + (($flags & 0x0001) !== 0 ? 4 : 2);
            $at += match (true) {
                ($flags & 0x0008) !== 0 => 2,
                ($flags & 0x0040) !== 0 => 4,
                ($flags & 0x0080) !== 0 => 8,
                default => 0,
            };
        } while (($flags & 0x0020) !== 0);
    }

    /**
     * @param  array<string, string>  $tables
     * @return array{0: string, 1: int} shrift fayli va head jadvalining boshlanishi
     */
    private function assemble(array $tables): array
    {
        ksort($tables, SORT_STRING);
        $count = count($tables);
        $power = 1;

        while ($power * 2 <= $count) {
            $power *= 2;
        }

        $font = pack('Nnnnn', 0x00010000, $count, $power * 16, (int) log($power, 2), $count * 16 - $power * 16);
        $offset = 12 + 16 * $count;
        $body = '';
        $headAt = 0;

        foreach ($tables as $tag => $content) {
            $padded = $content.str_repeat("\0", (4 - strlen($content) % 4) % 4);
            $headAt = $tag === 'head' ? $offset + strlen($body) : $headAt;
            $font .= $tag.pack('NNN', self::checksum($padded), $offset + strlen($body), strlen($content));
            $body .= $padded;
        }

        return [$font.$body, $headAt];
    }

    private static function checksum(string $data): int
    {
        $data .= str_repeat("\0", (4 - strlen($data) % 4) % 4);
        $sum = 0;

        foreach (unpack('N*', $data) ?: [] as $word) {
            $sum = ($sum + $word) & 0xFFFFFFFF;
        }

        return $sum;
    }

    private function readDirectory(): void
    {
        $count = $this->u16(4);

        for ($i = 0; $i < $count; $i++) {
            $record = 12 + $i * 16;
            $this->tables[substr($this->data, $record, 4)] = [
                'offset' => $this->u32($record + 8),
                'length' => $this->u32($record + 12),
            ];
        }
    }

    private function readCmap(): void
    {
        $cmap = $this->table('cmap');
        $best = null;

        for ($i = 0, $n = $this->u16($cmap + 2); $i < $n; $i++) {
            $record = $cmap + 4 + $i * 8;
            $platform = $this->u16($record);
            $encoding = $this->u16($record + 2);
            $sub = $cmap + $this->u32($record + 4);
            $format = $this->u16($sub);

            if ($platform === 3 && $encoding === 10 && $format === 12) {
                $best = [$sub, 12];

                break;
            }

            if ($platform === 3 && $encoding === 1 && $format === 4) {
                $best = [$sub, 4];
            }
        }

        if ($best === null) {
            throw new RuntimeException("Unicode cmap not found in {$this->postScriptName}");
        }

        [$sub, $format] = $best;

        if ($format === 12) {
            for ($i = 0, $groups = $this->u32($sub + 12); $i < $groups; $i++) {
                $at = $sub + 16 + $i * 12;
                [$start, $end, $glyph] = [$this->u32($at), $this->u32($at + 4), $this->u32($at + 8)];

                for ($code = $start; $code <= $end; $code++) {
                    $this->cmap[$code] = $glyph + $code - $start;
                }
            }

            return;
        }

        $segments = intdiv($this->u16($sub + 6), 2);
        $ends = $sub + 14;
        $starts = $ends + $segments * 2 + 2;
        $deltas = $starts + $segments * 2;
        $ranges = $deltas + $segments * 2;

        for ($s = 0; $s < $segments; $s++) {
            $start = $this->u16($starts + $s * 2);
            $end = $this->u16($ends + $s * 2);
            $delta = $this->i16($deltas + $s * 2);
            $rangeAt = $ranges + $s * 2;
            $range = $this->u16($rangeAt);

            for ($code = $start; $code <= $end && $code !== 0xFFFF; $code++) {
                $glyph = $range === 0
                    ? ($code + $delta) & 0xFFFF
                    : $this->u16($rangeAt + $range + ($code - $start) * 2);

                if ($range !== 0 && $glyph !== 0) {
                    $glyph = ($glyph + $delta) & 0xFFFF;
                }

                if ($glyph !== 0) {
                    $this->cmap[$code] = $glyph;
                }
            }
        }
    }

    private function table(string $tag): int
    {
        return $this->tables[$tag]['offset'] ?? throw new RuntimeException("Table {$tag} not found in {$this->postScriptName}");
    }

    private function u16(int $at): int
    {
        return (ord($this->data[$at]) << 8) | ord($this->data[$at + 1]);
    }

    private function i16(int $at): int
    {
        $value = $this->u16($at);

        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    private function u32(int $at): int
    {
        return (ord($this->data[$at]) << 24) | (ord($this->data[$at + 1]) << 16) | (ord($this->data[$at + 2]) << 8) | ord($this->data[$at + 3]);
    }
}

<?php

namespace App\Support\Pdf;

/**
 * SVG path ("d" atributi) → PDF yo'l operatorlari (m, l, c, h).
 *
 * Barcha buyruqlar: M L H V C S Q T A Z (katta — absolyut, kichik — nisbiy).
 * Kvadratik egri chiziqlar va yoylar kubik Bezier'ga aylantiriladi. Koordinatalar
 * SVG tizimidan (y pastga) PDF ga (y yuqoriga) o'tkaziladi: (originX, originY) — ikonka
 * qutisining chap-yuqori burchagi, scale — 1 SVG birligi necha pt.
 */
final class SvgPath
{
    private float $originX = 0.0;

    private float $originY = 0.0;

    private float $scale = 1.0;

    private string $ops = '';

    public static function toPdf(string $d, float $originX, float $originY, float $scale): string
    {
        $path = new self;
        $path->originX = $originX;
        $path->originY = $originY;
        $path->scale = $scale;
        $path->parse($d);

        return trim($path->ops);
    }

    private function parse(string $d): void
    {
        preg_match_all('/[MmLlHhVvCcSsQqTtAaZz]|[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?/', $d, $matches);
        $tokens = $matches[0];
        $i = 0;
        $count = count($tokens);
        $command = '';
        [$x, $y, $startX, $startY] = [0.0, 0.0, 0.0, 0.0];
        $lastControl = null;
        $lastQuad = null;
        $previous = '';

        $number = function () use (&$tokens, &$i): float {
            return (float) ($tokens[$i++] ?? 0);
        };

        // Yoy bayroqlari (0/1) ajratgichsiz yozilishi mumkin: "a2 2 0 0022 17" → 0, 0, 22
        $flag = function () use (&$tokens, &$i): int {
            $token = $tokens[$i] ?? '0';

            if (strlen($token) > 1 && ($token[0] === '0' || $token[0] === '1') && ctype_digit($token[1] ?? '')) {
                $tokens[$i] = substr($token, 1);

                return (int) $token[0];
            }

            $i++;

            return (int) $token;
        };

        while ($i < $count) {
            if (preg_match('/^[A-Za-z]$/', $tokens[$i]) === 1) {
                $command = $tokens[$i++];
            } elseif ($command === '') {
                break;
            }

            $relative = ctype_lower($command);
            $upper = strtoupper($command);

            switch ($upper) {
                case 'M':
                    $x = ($relative ? $x : 0) + $number();
                    $y = ($relative ? $y : 0) + $number();
                    [$startX, $startY] = [$x, $y];
                    $this->op('m', [$x, $y]);
                    // Keyingi juftliklar — L
                    $command = $relative ? 'l' : 'L';
                    $lastControl = $lastQuad = null;

                    break;

                case 'L':
                    $x = ($relative ? $x : 0) + $number();
                    $y = ($relative ? $y : 0) + $number();
                    $this->op('l', [$x, $y]);
                    $lastControl = $lastQuad = null;

                    break;

                case 'H':
                    $x = ($relative ? $x : 0) + $number();
                    $this->op('l', [$x, $y]);
                    $lastControl = $lastQuad = null;

                    break;

                case 'V':
                    $y = ($relative ? $y : 0) + $number();
                    $this->op('l', [$x, $y]);
                    $lastControl = $lastQuad = null;

                    break;

                case 'C':
                case 'S':
                    if ($upper === 'C') {
                        $x1 = ($relative ? $x : 0) + $number();
                        $y1 = ($relative ? $y : 0) + $number();
                    } else {
                        [$x1, $y1] = $lastControl !== null && in_array(strtoupper($previous), ['C', 'S'], true)
                            ? [2 * $x - $lastControl[0], 2 * $y - $lastControl[1]]
                            : [$x, $y];
                    }

                    $x2 = ($relative ? $x : 0) + $number();
                    $y2 = ($relative ? $y : 0) + $number();
                    $nx = ($relative ? $x : 0) + $number();
                    $ny = ($relative ? $y : 0) + $number();
                    $this->op('c', [$x1, $y1, $x2, $y2, $nx, $ny]);
                    $lastControl = [$x2, $y2];
                    $lastQuad = null;
                    [$x, $y] = [$nx, $ny];

                    break;

                case 'Q':
                case 'T':
                    if ($upper === 'Q') {
                        $qx = ($relative ? $x : 0) + $number();
                        $qy = ($relative ? $y : 0) + $number();
                    } else {
                        [$qx, $qy] = $lastQuad !== null && in_array(strtoupper($previous), ['Q', 'T'], true)
                            ? [2 * $x - $lastQuad[0], 2 * $y - $lastQuad[1]]
                            : [$x, $y];
                    }

                    $nx = ($relative ? $x : 0) + $number();
                    $ny = ($relative ? $y : 0) + $number();
                    $this->op('c', [
                        $x + 2 / 3 * ($qx - $x), $y + 2 / 3 * ($qy - $y),
                        $nx + 2 / 3 * ($qx - $nx), $ny + 2 / 3 * ($qy - $ny),
                        $nx, $ny,
                    ]);
                    $lastQuad = [$qx, $qy];
                    $lastControl = null;
                    [$x, $y] = [$nx, $ny];

                    break;

                case 'A':
                    $rx = abs($number());
                    $ry = abs($number());
                    $angle = $number();
                    $large = $flag();
                    $sweep = $flag();
                    $nx = ($relative ? $x : 0) + $number();
                    $ny = ($relative ? $y : 0) + $number();
                    $this->arc($x, $y, $rx, $ry, $angle, $large === 1, $sweep === 1, $nx, $ny);
                    $lastControl = $lastQuad = null;
                    [$x, $y] = [$nx, $ny];

                    break;

                case 'Z':
                    $this->ops .= 'h ';
                    [$x, $y] = [$startX, $startY];
                    $lastControl = $lastQuad = null;
                    $command = '';

                    break;

                default:
                    $i++;
            }

            $previous = $upper;
        }
    }

    /**
     * Elliptik yoy → kubik Bezier bo'laklari (SVG spetsifikatsiyasi, F.6 ilova).
     */
    private function arc(float $x1, float $y1, float $rx, float $ry, float $angle, bool $large, bool $sweep, float $x2, float $y2): void
    {
        if ($rx == 0.0 || $ry == 0.0 || ($x1 === $x2 && $y1 === $y2)) {
            $this->op('l', [$x2, $y2]);

            return;
        }

        $phi = deg2rad($angle);
        [$cos, $sin] = [cos($phi), sin($phi)];
        $dx = ($x1 - $x2) / 2;
        $dy = ($y1 - $y2) / 2;
        $xp = $cos * $dx + $sin * $dy;
        $yp = -$sin * $dx + $cos * $dy;

        $lambda = ($xp * $xp) / ($rx * $rx) + ($yp * $yp) / ($ry * $ry);

        if ($lambda > 1) {
            $rx *= sqrt($lambda);
            $ry *= sqrt($lambda);
        }

        $numerator = $rx * $rx * $ry * $ry - $rx * $rx * $yp * $yp - $ry * $ry * $xp * $xp;
        $denominator = $rx * $rx * $yp * $yp + $ry * $ry * $xp * $xp;
        $factor = ($large === $sweep ? -1 : 1) * sqrt(max(0, $numerator / $denominator));
        $cxp = $factor * $rx * $yp / $ry;
        $cyp = -$factor * $ry * $xp / $rx;
        $cx = $cos * $cxp - $sin * $cyp + ($x1 + $x2) / 2;
        $cy = $sin * $cxp + $cos * $cyp + ($y1 + $y2) / 2;

        $theta1 = atan2(($yp - $cyp) / $ry, ($xp - $cxp) / $rx);
        $delta = atan2((-$yp - $cyp) / $ry, (-$xp - $cxp) / $rx) - $theta1;

        if (! $sweep && $delta > 0) {
            $delta -= 2 * M_PI;
        } elseif ($sweep && $delta < 0) {
            $delta += 2 * M_PI;
        }

        $segments = (int) ceil(abs($delta) / (M_PI / 2));
        $step = $delta / $segments;
        $k = 4 / 3 * tan($step / 4);

        for ($s = 0; $s < $segments; $s++) {
            $a1 = $theta1 + $s * $step;
            $a2 = $a1 + $step;
            $p1 = [cos($a1) - $k * sin($a1), sin($a1) + $k * cos($a1)];
            $p2 = [cos($a2) + $k * sin($a2), sin($a2) - $k * cos($a2)];
            $p3 = [cos($a2), sin($a2)];
            $points = [];

            foreach ([$p1, $p2, $p3] as [$ux, $uy]) {
                $points[] = $cx + $rx * $ux * $cos - $ry * $uy * $sin;
                $points[] = $cy + $rx * $ux * $sin + $ry * $uy * $cos;
            }

            $this->op('c', $points);
        }
    }

    /**
     * @param  array<int, float>  $points  SVG koordinatalari (x, y juftliklari)
     */
    private function op(string $operator, array $points): void
    {
        $out = [];

        for ($p = 0; $p < count($points); $p += 2) {
            $out[] = sprintf('%.2F %.2F', $this->originX + $points[$p] * $this->scale, $this->originY - $points[$p + 1] * $this->scale);
        }

        $this->ops .= implode(' ', $out).' '.$operator.' ';
    }
}

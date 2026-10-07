<?php

namespace App\Support\Pdf;

use RuntimeException;

/**
 * Rasmdan bitta A4 sahifali PDF yasash (son muqovasi uchun). Tashqi kutubxonasiz:
 * rasm GD orqali JPEG ga o'tkaziladi va PDF ga DCTDecode bilan joylanadi.
 * Rasm sahifani to'liq qoplaydi (nisbati saqlanadi, ortiqcha qismi chetdan kesiladi).
 */
final class ImagePdf
{
    /** A4 o'lchami (pt) */
    private const WIDTH = 595.28;

    private const HEIGHT = 841.89;

    public static function fromImage(string $binary): string
    {
        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            throw new RuntimeException('Cover image could not be read.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Shaffof PNG/WEBP — oq fonga
        $canvas = imagecreatetruecolor($width, $height);
        $white = (int) imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpeg = (string) ob_get_clean();

        $scale = max(self::WIDTH / $width, self::HEIGHT / $height);
        $drawW = $width * $scale;
        $drawH = $height * $scale;
        $x = (self::WIDTH - $drawW) / 2;
        $y = (self::HEIGHT - $drawH) / 2;

        $content = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q', $drawW, $drawH, $x, $y);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /XObject << /Im1 5 0 R >> >> /Contents 4 0 R >>', self::WIDTH, self::HEIGHT),
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
            sprintf('<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>', $width, $height, strlen($jpeg))
                ."\nstream\n".$jpeg."\nendstream",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer'."\n".'<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF\n";
    }
}

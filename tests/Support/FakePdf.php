<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Testlar uchun haqiqiy (minimal) N betlik PDF: PdfPageCounter uni o'qiy oladi.
 */
final class FakePdf
{
    public static function content(int $pages): string
    {
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>'];
        $kids = [];

        for ($i = 0; $i < $pages; $i++) {
            $kids[] = (3 + $i).' 0 R';
        }

        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pages.' >>';

        for ($i = 0; $i < $pages; $i++) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>';
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer
<< /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    public static function upload(int $pages, string $name = 'final.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::content($pages))->mimeType('application/pdf');
    }
}

<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * GD kengaytmasiz haqiqiy PNG yaratadi (UploadedFile::fake()->image() GD talab qiladi).
 * getimagesize() va fileinfo uni to'g'ri PNG deb taniydi.
 */
trait CreatesFakeImages
{
    protected function fakePng(string $name, int $width, int $height): UploadedFile
    {
        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data
            .pack('N', crc32($type.$data));

        // Har bir qator: filtr bayti (0) + RGB piksellar
        $row = "\0".str_repeat("\x1a\x82\xf7", $width);
        $pixels = (string) gzcompress(str_repeat($row, $height));

        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', $pixels)
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}

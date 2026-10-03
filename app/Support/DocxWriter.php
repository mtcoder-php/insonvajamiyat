<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Oddiy Word (.docx) hujjat: sarlavha + xatboshilar (Times New Roman 14, 1.5 interval).
 * Tashqi kutubxonasiz — ZipArchive (php-zip kengaytmasi) orqali.
 */
final class DocxWriter
{
    public static function available(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @return string Vaqtinchalik fayl yo'li (javobdan keyin o'chiriladi)
     */
    public static function write(string $title, string $body, ?string $subtitle = null): string
    {
        if (! self::available()) {
            throw new RuntimeException('PHP zip extension is required to build .docx files.');
        }

        $path = tempnam(sys_get_temp_dir(), 'docx');

        if ($path === false) {
            throw new RuntimeException('Temporary file could not be created.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Docx archive could not be opened.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');

        $paragraphs = self::paragraph($title, bold: true, size: 32, center: true);

        if ($subtitle !== null && $subtitle !== '') {
            $paragraphs .= self::paragraph($subtitle, size: 22, center: true, color: '5B6B80');
        }

        foreach (preg_split('/\n/u', str_replace("\r", '', $body)) ?: [] as $line) {
            $paragraphs .= self::paragraph($line);
        }

        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$paragraphs
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            .'<w:pgMar w:top="1134" w:right="850" w:bottom="1134" w:left="1701" w:header="709" w:footer="709" w:gutter="0"/>'
            .'</w:sectPr></w:body></w:document>');

        $zip->close();

        return $path;
    }

    private static function paragraph(string $text, bool $bold = false, int $size = 28, bool $center = false, ?string $color = null): string
    {
        $runProps = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            .($bold ? '<w:b/>' : '')
            .($color !== null ? '<w:color w:val="'.$color.'"/>' : '')
            .'<w:sz w:val="'.$size.'"/>';

        $paraProps = '<w:pPr><w:spacing w:after="120" w:line="360" w:lineRule="auto"/>'
            .($center ? '<w:jc w:val="center"/>' : '<w:jc w:val="both"/><w:ind w:firstLine="709"/>')
            .'</w:pPr>';

        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:p>'.$paraProps.'<w:r><w:rPr>'.$runProps.'</w:rPr><w:t xml:space="preserve">'.$escaped.'</w:t></w:r></w:p>';
    }
}

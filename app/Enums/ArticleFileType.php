<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** article_files.type */
enum ArticleFileType: string
{
    use EnumHelpers;

    case Manuscript = 'manuscript';         // Asosiy fayl (.docx/.pdf)
    case Supplementary = 'supplementary';   // Ilova: rasm, jadval va h.k.
    case Revision = 'revision';             // Tuzatilgan versiya fayli
    case Translation = 'translation';       // Tarjima eksporti
    case FinalPdf = 'final_pdf';            // Maketlangan yakuniy PDF (nashr uchun)

    public function label(): string
    {
        return match ($this) {
            self::Manuscript => __('Asosiy fayl'),
            self::Supplementary => __("Qo'shimcha fayl"),
            self::Revision => __('Tuzatilgan fayl'),
            self::Translation => __('Tarjima'),
            self::FinalPdf => __('Yakuniy PDF'),
        };
    }

    /**
     * Ruxsat etilgan kengaytmalar (validatsiya uchun)
     *
     * @return array<int, string>
     */
    public function allowedMimes(): array
    {
        return match ($this) {
            self::Manuscript, self::Revision, self::Translation => ['docx', 'pdf'],
            self::Supplementary => ['docx', 'pdf', 'xlsx', 'png', 'jpg', 'jpeg', 'zip'],
            self::FinalPdf => ['pdf'],
        };
    }

    /** Maksimal hajm, KB */
    public function maxSizeKb(): int
    {
        return match ($this) {
            self::Supplementary => 20480,
            default => 10240,
        };
    }
}

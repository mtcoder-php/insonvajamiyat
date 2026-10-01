<?php

namespace App\Console\Commands;

use App\Services\Web\BookCoverImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * public/web/books/ dagi rasmlarni nomi mos "Tavsiya etilgan kitoblar"ga biriktirish.
 *
 *   php artisan app:import-book-covers          # faqat muqovasi yo'qlarga
 *   php artisan app:import-book-covers --force  # hammasini qayta yozish
 */
#[Signature('app:import-book-covers {--force : Muqovasi bor kitoblarni ham yangilash}')]
#[Description('public/web/books/ dagi rasmlarni tavsiya etilgan kitoblarga biriktirish (fayl nomi = kitob nomi)')]
class ImportBookCovers extends Command
{
    public function handle(BookCoverImporter $importer): int
    {
        $result = $importer->import((bool) $this->option('force'));

        $this->info("Biriktirildi: {$result['attached']} ta kitob.");

        if ($result['missing'] !== []) {
            $this->warn('Muqovasi topilmagan kitoblar (public/web/books/<kitob nomi>.png):');

            foreach ($result['missing'] as $title) {
                $this->line("  — {$title}");
            }
        }

        return self::SUCCESS;
    }
}

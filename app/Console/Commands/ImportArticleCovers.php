<?php

namespace App\Console\Commands;

use App\Services\Web\ArticleCoverImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * public/web/article/ dagi rasmlarni sarlavhasi mos maqolalarga biriktirish.
 *
 *   php artisan app:import-article-covers          # faqat rasmi yo'qlarga
 *   php artisan app:import-article-covers --force  # hammasini qayta yozish
 */
#[Signature('app:import-article-covers {--force : Rasmi bor maqolalarni ham yangilash}')]
#[Description('public/web/article/ dagi rasmlarni maqolalarga biriktirish (fayl nomi = sarlavha)')]
class ImportArticleCovers extends Command
{
    public function handle(ArticleCoverImporter $importer): int
    {
        $result = $importer->import((bool) $this->option('force'));

        $this->info("Biriktirildi: {$result['attached']} ta maqola.");

        if ($result['missing'] !== []) {
            $this->warn('Rasmi topilmagan maqolalar (public/web/article/<sarlavha>.png):');

            foreach ($result['missing'] as $title) {
                $this->line("  — {$title}");
            }
        }

        return self::SUCCESS;
    }
}

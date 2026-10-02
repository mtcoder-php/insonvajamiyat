<?php

namespace App\Console\Commands;

use App\Enums\ArticleFileType;
use App\Models\ArticleFile;
use App\Support\PdfPageCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Avval yuklangan PDF fayllarning betlar sonini aniqlash (article_files.page_count)
 * va yakuniy PDF bo'yicha maqolalar hajmini (articles.pages_count) to'ldirish.
 * Bir marta ishga tushiriladi; yangi yuklanganlar avtomatik hisoblanadi.
 *
 *   php artisan app:count-pdf-pages
 */
class CountPdfPages extends Command
{
    protected $signature = 'app:count-pdf-pages {--force : Hisoblanganlarini ham qayta hisoblash}';

    protected $description = 'PDF fayllar betlar sonini aniqlash va maqolalar hajmini to\'ldirish';

    public function handle(): int
    {
        $counted = 0;
        $failed = 0;
        $articles = 0;

        ArticleFile::query()
            ->with('article')
            ->where(fn ($q) => $q->where('mime_type', 'application/pdf')->orWhere('original_name', 'like', '%.pdf'))
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('page_count'))
            ->orderBy('id')
            ->chunkById(100, function ($files) use (&$counted, &$failed, &$articles): void {
                foreach ($files as $file) {
                    /** @var ArticleFile $file */
                    $disk = Storage::disk($file->disk);
                    $pages = $disk->exists($file->path) ? PdfPageCounter::count($disk->path($file->path)) : null;

                    if ($pages === null) {
                        $failed++;

                        continue;
                    }

                    $file->forceFill(['page_count' => min($pages, 65535)])->saveQuietly();
                    $counted++;

                    if ($file->type === ArticleFileType::FinalPdf && $file->article->pages_count !== $pages) {
                        $file->article->forceFill(['pages_count' => $pages])->saveQuietly();
                        $articles++;
                    }
                }
            });

        $this->info("Hisoblandi: {$counted} ta PDF, maqola hajmi yangilandi: {$articles} ta.");

        if ($failed > 0) {
            $this->warn("{$failed} ta faylni o'qib bo'lmadi (topilmadi, shifrlangan yoki buzilgan) — hajmi qo'lda kiritiladi.");
        }

        return self::SUCCESS;
    }
}

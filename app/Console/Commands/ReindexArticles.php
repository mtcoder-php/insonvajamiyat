<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

/**
 * Katalog qidiruvi indeksini (articles.search_text) qayta yaratish.
 * Odatda avtomatik yangilanadi; eski / import qilingan ma'lumotlar uchun bir marta ishga tushiriladi.
 */
class ReindexArticles extends Command
{
    protected $signature = 'app:reindex-articles';

    protected $description = 'Maqolalar qidiruv indeksini (search_text) qayta yaratish';

    public function handle(): int
    {
        $count = 0;

        Article::query()->withTrashed()->chunkById(200, function ($articles) use (&$count): void {
            foreach ($articles as $article) {
                /** @var Article $article */
                $article->search_text = $article->buildSearchText();
                $article->saveQuietly();
                $count++;
            }
        });

        $this->info("{$count} ta maqola qayta indekslandi.");

        return self::SUCCESS;
    }
}

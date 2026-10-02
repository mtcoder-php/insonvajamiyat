<?php

namespace App\Services\Web;

use App\Models\Article;
use Illuminate\Support\Facades\DB;

/**
 * Maqola bo'yicha kunlik ko'rish / yuklab olish agregati (article_daily_stats).
 * Statistika sahifasida tanlangan davrdagi ko'rishlar shu jadvaldan hisoblanadi;
 * articles.views_count / downloads_count — umumiy hisoblagich (alohida oshiriladi).
 *
 * MySQL va SQLite'da bir xil ishlaydi: avval mavjud qator oshiriladi,
 * bo'lmasa insertOrIgnore (parallel so'rovda unique indeks himoya qiladi) va qayta oshiriladi.
 */
class ArticleDailyStats
{
    public const VIEWS = 'views';

    public const DOWNLOADS = 'downloads';

    public function record(Article $article, string $column): void
    {
        if (! in_array($column, [self::VIEWS, self::DOWNLOADS], true)) {
            return;
        }

        $today = now()->toDateString();
        $query = fn () => DB::table('article_daily_stats')
            ->where('article_id', $article->id)
            ->where('date', $today);

        if ($query()->increment($column) > 0) {
            return;
        }

        DB::table('article_daily_stats')->insertOrIgnore([
            'article_id' => $article->id,
            'date' => $today,
            'views' => 0,
            'downloads' => 0,
        ]);

        $query()->increment($column);
    }
}

<?php

namespace App\Services\Web;

use App\Models\Article;
use App\Support\TitleImageFiles;

/**
 * Maqola rasmlarini public/web/article/ papkasidan biriktiradi
 * (fayl nomi = maqola sarlavhasi, moslash qoidalari — TitleImageFiles).
 * Rasm storage/app/public/articles/covers/{slug}.{ext} ga nusxalanadi
 * va articles.cover_image_path ga yoziladi.
 */
class ArticleCoverImporter
{
    public const SOURCE_DIR = 'web/article';

    /**
     * missing — rasmi topilmagan nashr etilgan maqolalar (saytda ko'rinadiganlar),
     * missingDrafts — rasmi topilmagan, hali nashr etilmagan maqolalar soni.
     *
     * @return array{attached: int, missing: array<int, string>, missingDrafts: int}
     */
    public function import(bool $force = false): array
    {
        $files = new TitleImageFiles(self::SOURCE_DIR);
        $attached = 0;
        $missing = [];
        $missingDrafts = 0;

        $articles = Article::query()
            ->when(! $force, fn ($query) => $query->whereNull('cover_image_path'))
            ->get();

        foreach ($articles as $article) {
            $title = (string) $article->getTranslation('title', 'uz', true);
            $source = $files->find($title);

            if ($source === null) {
                if ($article->isPublished()) {
                    $missing[] = $title;
                } else {
                    $missingDrafts++;
                }

                continue;
            }

            $path = $files->store($source, 'articles/covers', (string) ($article->slug ?? $article->id));

            if ($path !== null) {
                $article->forceFill(['cover_image_path' => $path])->save();
                $attached++;
            }
        }

        return ['attached' => $attached, 'missing' => $missing, 'missingDrafts' => $missingDrafts];
    }

    public static function normalize(string $value): string
    {
        return TitleImageFiles::normalize($value);
    }
}

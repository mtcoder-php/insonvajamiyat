<?php

namespace App\Services\Web;

use App\Models\RecommendedBook;
use App\Support\TitleImageFiles;
use Illuminate\Support\Str;

/**
 * "Tavsiya etilgan kitoblar" muqovalarini public/web/books/ papkasidan biriktiradi
 * (fayl nomi = kitob nomi, masalan "O'rta Osiyo xalqlari etnologiyasi.png").
 * Rasm storage/app/public/books/covers/{id}-{slug}.{ext} ga nusxalanadi.
 */
class BookCoverImporter
{
    public const SOURCE_DIR = 'web/books';

    /**
     * @return array{attached: int, missing: array<int, string>}
     */
    public function import(bool $force = false): array
    {
        $files = new TitleImageFiles(self::SOURCE_DIR);
        $attached = 0;
        $missing = [];

        $books = RecommendedBook::query()
            ->when(! $force, fn ($query) => $query->whereNull('cover_image_path'))
            ->get();

        foreach ($books as $book) {
            $title = (string) $book->getTranslation('title', 'uz', true);
            $source = $files->find($title);

            if ($source === null) {
                $missing[] = $title;

                continue;
            }

            $path = $files->store($source, 'books/covers', $book->id.'-'.Str::slug($title));

            if ($path !== null) {
                $book->forceFill(['cover_image_path' => $path])->save();
                $attached++;
            }
        }

        return ['attached' => $attached, 'missing' => $missing];
    }
}

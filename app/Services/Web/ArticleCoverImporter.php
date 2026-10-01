<?php

namespace App\Services\Web;

use App\Models\Article;
use App\Support\MediaUrl;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Maqola rasmlarini public/web/article/ papkasidan biriktiradi.
 *
 * Fayl nomi = maqola sarlavhasi (masalan, "O'rta asrlar davrida ... tizimi.png").
 * Apostrof turlari (' ʻ ’ ‘ `), katta-kichik harf va ortiqcha bo'shliqlar
 * farqi hisobga olinmaydi. Rasm storage/app/public/articles/covers/{slug}.{ext}
 * ga nusxalanadi va articles.cover_image_path ga yoziladi.
 */
class ArticleCoverImporter
{
    public const SOURCE_DIR = 'web/article';

    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    /**
     * missing — rasmi topilmagan nashr etilgan maqolalar (saytda ko'rinadiganlar),
     * missingDrafts — rasmi topilmagan, hali nashr etilmagan maqolalar soni.
     *
     * @return array{attached: int, missing: array<int, string>, missingDrafts: int}
     */
    public function import(bool $force = false): array
    {
        $files = $this->sourceFiles();
        $attached = 0;
        $missing = [];
        $missingDrafts = 0;

        $articles = Article::query()
            ->when(! $force, fn ($query) => $query->whereNull('cover_image_path'))
            ->get();

        foreach ($articles as $article) {
            $title = (string) $article->getTranslation('title', 'uz', true);
            $source = $files[self::normalize($title)] ?? null;

            if ($source === null) {
                if ($article->isPublished()) {
                    $missing[] = $title;
                } else {
                    $missingDrafts++;
                }

                continue;
            }

            $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            $path = Storage::disk(MediaUrl::DISK)->putFileAs(
                'articles/covers',
                new File($source),
                "{$article->slug}.{$extension}",
            );

            if ($path === false) {
                continue;
            }

            $article->forceFill(['cover_image_path' => $path])->save();
            $attached++;
        }

        return ['attached' => $attached, 'missing' => $missing, 'missingDrafts' => $missingDrafts];
    }

    /**
     * Normallashtirilgan nom => to'liq yo'l.
     *
     * @return array<string, string>
     */
    private function sourceFiles(): array
    {
        $directory = public_path(self::SOURCE_DIR);

        if (! is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (scandir($directory) ?: [] as $name) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($extension, self::EXTENSIONS, true)) {
                $files[self::normalize(pathinfo($name, PATHINFO_FILENAME))] = $directory.DIRECTORY_SEPARATOR.$name;
            }
        }

        return $files;
    }

    public static function normalize(string $value): string
    {
        $value = str_replace(['ʻ', 'ʼ', '‘', '’', '`', '´'], "'", $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return rtrim(mb_strtolower(trim($value)), '.');
    }
}

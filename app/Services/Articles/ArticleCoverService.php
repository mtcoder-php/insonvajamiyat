<?php

namespace App\Services\Articles;

use App\Models\Article;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Maqola rasmi (muqova): saytdagi katalog, maqola sahifasi va bosh sahifa kartalarida ko'rinadi.
 * Fayl public diskda: articles/covers/{uuid}-{tasodifiy}.{ext} — har yuklashda yangi nom
 * (brauzer keshidagi eski rasm ko'rinib qolmasligi uchun), eskisi o'chiriladi.
 */
class ArticleCoverService
{
    public const DIR = 'articles/covers';

    public function store(Article $article, UploadedFile $image): string
    {
        $extension = strtolower($image->getClientOriginalExtension()) ?: 'jpg';
        $path = $image->storeAs(self::DIR, $article->uuid.'-'.Str::lower(Str::random(6)).'.'.$extension, MediaUrl::DISK);

        if ($path === false) {
            throw new RuntimeException('Article cover could not be stored.');
        }

        $old = $article->cover_image_path;
        $article->forceFill(['cover_image_path' => $path])->save();
        $this->deleteFile($old);

        return $path;
    }

    public function remove(Article $article): void
    {
        $old = $article->cover_image_path;
        $article->forceFill(['cover_image_path' => null])->save();
        $this->deleteFile($old);
    }

    private function deleteFile(?string $path): void
    {
        if ($path !== null && $path !== '' && Storage::disk(MediaUrl::DISK)->exists($path)) {
            Storage::disk(MediaUrl::DISK)->delete($path);
        }
    }
}

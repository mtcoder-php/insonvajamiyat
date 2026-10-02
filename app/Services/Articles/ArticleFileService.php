<?php

namespace App\Services\Articles;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\ArticleVersion;
use App\Models\User;
use App\Support\PdfPageCounter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Maqola fayllari maxfiy diskda (storage/app/private/articles/{uuid}/...).
 * To'g'ridan-to'g'ri URL yo'q — yuklab olish faqat controller + ArticlePolicy orqali.
 */
class ArticleFileService
{
    public const DISK = 'local';

    public function store(
        Article $article,
        UploadedFile $file,
        ArticleFileType $type,
        ?User $uploader = null,
        ?ArticleVersion $version = null,
    ): ArticleFile {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $path = $file->storeAs("articles/{$article->uuid}", Str::uuid()->toString().'.'.$extension, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Article file could not be stored.');
        }

        $realPath = $file->getRealPath();
        $checksum = $realPath !== false ? hash_file('sha256', $realPath) : false;
        $pageCount = $realPath !== false && $extension === 'pdf' ? PdfPageCounter::count($realPath) : null;

        return $article->files()->create([
            'article_version_id' => $version?->id,
            'type' => $type,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'page_count' => $pageCount !== null ? min($pageCount, 65535) : null,
            'checksum' => $checksum !== false ? $checksum : null,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    public function download(ArticleFile $file): StreamedResponse
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /** PDF — brauzerda ko'rsatish (iframe uchun), boshqa turlar — yuklab olish */
    public function inline(ArticleFile $file): StreamedResponse
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return $file->extension() === 'pdf'
            ? Storage::disk($file->disk)->response($file->path, $file->original_name)
            : Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function delete(ArticleFile $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }
}

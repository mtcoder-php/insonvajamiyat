<?php

namespace App\Models;

use App\Enums\ArticleFileType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maqola fayli (asosiy qo'lyozma, ilova, tuzatilgan versiya, yakuniy PDF).
 * Fayllar maxfiy diskda saqlanadi va faqat ruxsat bilan yuklab olinadi
 * (App\Services\Articles\ArticleFileService, ArticlePolicy::downloadFiles).
 *
 * @property int $id
 * @property string $uuid
 * @property int $article_id
 * @property int|null $article_version_id
 * @property ArticleFileType $type
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $page_count
 * @property string|null $checksum
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 */
#[Fillable([
    'article_id', 'article_version_id', 'type', 'disk', 'path', 'original_name',
    'mime_type', 'size', 'page_count', 'checksum', 'uploaded_by',
])]
class ArticleFile extends Model
{
    use HasUuids;

    /**
     * Faqat `uuid` ustuni UUID; asosiy kalit auto-increment.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ArticleFileType::class,
            'size' => 'integer',
            'page_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }
}

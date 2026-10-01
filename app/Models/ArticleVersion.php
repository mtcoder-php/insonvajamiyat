<?php

namespace App\Models;

use App\Enums\ArticleVersionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Maqola versiyasi: dastlabki yuborish, tuzatish, AI tuzatgan matn, tarjima, maket.
 *
 * @property int $id
 * @property int $article_id
 * @property int $version_number
 * @property ArticleVersionType $type
 * @property int $review_round
 * @property string $language
 * @property string|null $content
 * @property string|null $change_note
 * @property int|null $ai_request_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'article_id', 'version_number', 'type', 'review_round', 'language', 'content',
    'change_note', 'ai_request_id', 'created_by',
])]
class ArticleVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ArticleVersionType::class,
            'version_number' => 'integer',
            'review_round' => 'integer',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return HasMany<ArticleFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class);
    }
}

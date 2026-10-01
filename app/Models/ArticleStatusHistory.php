<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maqola holatlari tarixi (kim, qachon, qaysi holatga o'tkazdi).
 *
 * @property int $id
 * @property int $article_id
 * @property ArticleStatus|null $from_status
 * @property ArticleStatus $to_status
 * @property int|null $changed_by
 * @property string|null $comment
 * @property bool $is_visible_to_author
 * @property Carbon $created_at
 * @property-read User|null $changedBy
 */
#[Fillable(['article_id', 'from_status', 'to_status', 'changed_by', 'comment', 'is_visible_to_author', 'created_at'])]
class ArticleStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ArticleStatus::class,
            'to_status' => ArticleStatus::class,
            'is_visible_to_author' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}

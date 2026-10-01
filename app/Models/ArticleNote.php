<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Muharrir izohi (ichki, muallifga ko'rinmaydi).
 *
 * @property int $id
 * @property int $article_id
 * @property int $user_id
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $user
 */
#[Fillable(['article_id', 'user_id', 'body'])]
class ArticleNote extends Model
{
    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

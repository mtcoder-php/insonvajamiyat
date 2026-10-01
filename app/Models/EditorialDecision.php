<?php

namespace App\Models;

use App\Enums\EditorialDecisionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Muharrir qarori: taqrizga yuborish / tuzatish / qabul / rad.
 *
 * @property int $id
 * @property int $article_id
 * @property int $editor_id
 * @property int $round
 * @property EditorialDecisionType $decision
 * @property string|null $comment_to_author
 * @property string|null $internal_note
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $editor
 */
#[Fillable(['article_id', 'editor_id', 'round', 'decision', 'comment_to_author', 'internal_note'])]
class EditorialDecision extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => EditorialDecisionType::class,
            'round' => 'integer',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}

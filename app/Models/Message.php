<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maqola bo'yicha yozishma xabari (muallif ↔ tahririyat yoki tahririyat ↔ taqrizchi).
 *
 * @property int $id
 * @property int $article_id
 * @property int|null $review_id
 * @property MessageChannel $channel
 * @property int $sender_id
 * @property string $body
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $sender
 */
#[Fillable(['article_id', 'review_id', 'channel', 'sender_id', 'body', 'attachment_path', 'attachment_name'])]
class Message extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

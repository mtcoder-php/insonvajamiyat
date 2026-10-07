<?php

namespace App\Models;

use App\Enums\BroadcastAudience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ommaviy xabar (Admin → Xabarlar).
 *
 * @property int $id
 * @property string $uuid
 * @property string $subject
 * @property string $body
 * @property BroadcastAudience $audience
 * @property bool $send_email
 * @property string $status
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $sender_id
 * @property Carbon|null $sent_at
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $sender
 */
#[Fillable(['subject', 'body', 'audience', 'send_email', 'sender_id'])]
class Broadcast extends Model
{
    use HasUuids;

    public const QUEUED = 'queued';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    /**
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
            'audience' => BroadcastAudience::class,
            'send_email' => 'boolean',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

<?php

namespace App\Models;

use App\Enums\AuditEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit log yozuvi (append-only). Yozish — faqat App\Services\Audit\AuditLogger orqali.
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuditEvent $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_label
 * @property string|null $description
 * @property array<string, mixed>|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read User|null $user
 */
#[Fillable([
    'user_id', 'event', 'subject_type', 'subject_id', 'subject_label',
    'description', 'properties', 'ip_address', 'user_agent', 'created_at',
])]
class AuditLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'event' => AuditEvent::class,
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Saqlash muddati o'tgan yozuvlar (`php artisan model:prune`).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = max(30, (int) config('journal.audit.retention_days', 365));

        return static::query()->where('created_at', '<', now()->subDays($days));
    }
}

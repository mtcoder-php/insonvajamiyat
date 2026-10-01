<?php

namespace App\Models;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use Database\Factories\AiRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * AI xizmatiga so'rov (imlo tekshiruvi, tarjima, tahlil).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $article_id
 * @property AiRequestType $type
 * @property AiRequestStatus $status
 * @property string|null $model
 * @property string $source_language
 * @property string|null $target_language
 * @property string $input_text
 * @property int $input_tokens
 * @property int $output_tokens
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'article_id', 'type', 'model', 'source_language', 'target_language', 'input_text'])]
class AiRequest extends Model
{
    /** @use HasFactory<AiRequestFactory> */
    use HasFactory, HasUuids;

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
            'type' => AiRequestType::class,
            'status' => AiRequestStatus::class,
            'result' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_usd' => 'decimal:6',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

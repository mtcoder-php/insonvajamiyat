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
 * @property int|null $prompt_template_id
 * @property string $provider
 * @property string $input_text
 * @property string|null $output_text
 * @property array<string, mixed>|null $result
 * @property int $chunks_total
 * @property int $chunks_completed
 * @property int $input_tokens
 * @property int $output_tokens
 * @property string $cost_usd
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Article|null $article
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
            'chunks_total' => 'integer',
            'chunks_completed' => 'integer',
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

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** Jami sarflangan token (kiruvchi + chiquvchi) */
    public function totalTokens(): int
    {
        return $this->input_tokens + $this->output_tokens;
    }
}

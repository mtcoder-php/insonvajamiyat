<?php

namespace App\Models;

use App\Enums\AiRequestType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * AI ko'rsatmasi (system prompt) shabloni — Super Admin kodni o'zgartirmasdan tahrirlaydi.
 *
 * Kalitlar: proofreader, translator, analytics (umumiy, til o'rinbosarlari bilan).
 * O'rinbosarlar: {source_language}, {target_language}, {text}.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property AiRequestType $type
 * @property string $source_language
 * @property string|null $target_language
 * @property string $system_prompt
 * @property string|null $user_prompt_template
 * @property string|null $model
 * @property string $temperature
 * @property int $max_tokens
 * @property bool $is_active
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $editor
 */
#[Fillable(['key', 'name', 'type', 'source_language', 'target_language', 'system_prompt', 'user_prompt_template', 'model', 'temperature', 'max_tokens', 'is_active', 'updated_by'])]
class PromptTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiRequestType::class,
            'temperature' => 'decimal:2',
            'max_tokens' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

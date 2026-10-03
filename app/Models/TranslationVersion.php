<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $translation_id
 * @property int $version
 * @property string $content
 * @property bool $is_ai_generated
 * @property int|null $ai_request_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Translation $translation
 * @property-read User|null $author
 */
#[Fillable(['translation_id', 'version', 'content', 'is_ai_generated', 'ai_request_id', 'created_by'])]
class TranslationVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_ai_generated' => 'boolean',
        ];
    }

    /** @return BelongsTo<Translation, $this> */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

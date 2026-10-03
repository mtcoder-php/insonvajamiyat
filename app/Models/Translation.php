<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tarjima hujjati (TZ 4.1.6): AI tarjimasi (v1) va foydalanuvchining tahrirlari (v2, v3 …).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $article_id
 * @property string $title
 * @property string $source_language
 * @property string $target_language
 * @property string $source_text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Collection<int, TranslationVersion> $versions
 * @property-read TranslationVersion|null $latestVersion
 */
#[Fillable(['user_id', 'article_id', 'title', 'source_language', 'target_language', 'source_text'])]
class Translation extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TranslationVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(TranslationVersion::class)->orderByDesc('version');
    }

    /** @return HasOne<TranslationVersion, $this> */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(TranslationVersion::class)->ofMany('version', 'max');
    }
}

<?php

namespace App\Models;

use App\Enums\JournalDocumentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Mualliflar uchun yuklab olinadigan fayl: maqola shabloni, yo'riqnoma, shakllar (TZ 4.2.5).
 * Fayl public diskda (documents/), yuklab olish esa route orqali — asl nom bilan va hisoblagich bilan.
 *
 * @property int $id
 * @property JournalDocumentKind $kind
 * @property string $title
 * @property string|null $description
 * @property string $path
 * @property string $original_name
 * @property string $extension
 * @property int $size
 * @property bool $is_active
 * @property int $sort_order
 * @property int $downloads_count
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $editor
 */
#[Fillable(['kind', 'title', 'description', 'path', 'original_name', 'extension', 'size', 'is_active', 'sort_order'])]
class JournalDocument extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => JournalDocumentKind::class,
            'size' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'downloads_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Tartib: tur (shablon → yo'riqnoma → shakllar → boshqa), keyin sort_order.
     *
     * @param  Builder<JournalDocument>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByRaw(
            "case kind when 'template' then 0 when 'guide' then 1 when 'form' then 2 else 3 end",
        )->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<JournalDocument>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

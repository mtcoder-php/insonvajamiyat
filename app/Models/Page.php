<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Statik sahifa ("Jurnal haqida", "Yo'riqnoma", "Aloqa") — App\Services\Content\PageService.
 *
 * content — bo'limlar ro'yxati (tildan mustaqil tuzilma, matnlar tarjimali):
 *   [{ "heading": {"uz": "...", "ru": "...", "en": "..."}, "body": {"uz": "...", ...} }, ...]
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property array<int, mixed>|null $content
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property bool $is_published
 * @property bool $show_in_menu
 * @property int $menu_order
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $editor
 */
#[Fillable(['slug', 'title', 'content', 'meta_title', 'meta_description', 'is_published'])]
class Page extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title', 'meta_title', 'meta_description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_published' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

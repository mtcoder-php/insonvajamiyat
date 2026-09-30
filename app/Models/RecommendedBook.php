<?php

namespace App\Models;

use Database\Factories\RecommendedBookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Tavsiya etilgan kitob (bosh sahifa o'ng ustuni).
 *
 * @property int $id
 * @property string $title
 * @property string $author
 * @property int|null $year
 * @property string|null $cover_image_path
 * @property string|null $url
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'author', 'year', 'cover_image_path', 'url', 'sort_order', 'is_active'])]
class RecommendedBook extends Model
{
    /** @use HasFactory<RecommendedBookFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<RecommendedBook>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderByDesc('id');
    }
}

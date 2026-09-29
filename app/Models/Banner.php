<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Bosh sahifa slayderi (hero) bannerlari.
 *
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string $image_path
 * @property string|null $link_url
 * @property string|null $button_text
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'subtitle', 'image_path', 'link_url', 'button_text', 'sort_order', 'is_active', 'starts_at', 'ends_at'])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['title', 'subtitle', 'button_text'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Faol va ko'rsatish muddati ichidagi bannerlar.
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

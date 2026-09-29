<?php

namespace App\Models;

use App\Enums\PartnerType;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Hamkor tashkilot yoki indekslash bazasi (Google Scholar, CrossRef ...).
 *
 * @property int $id
 * @property PartnerType $type
 * @property string $name
 * @property string|null $subtitle
 * @property string|null $logo_path
 * @property string|null $url
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'subtitle', 'logo_path', 'url', 'sort_order', 'is_active'])]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name', 'subtitle'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Partner>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<Partner>  $query
     */
    public function scopeOfType(Builder $query, PartnerType $type): void
    {
        $query->where('type', $type->value);
    }
}

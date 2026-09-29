<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Ilmiy yo'nalish (Tarix, Etnologiya, Antropologiya, Falsafa ...).
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $code
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $published_articles_count
 * @property-read Subject|null $parent
 */
#[Fillable(['parent_id', 'name', 'slug', 'code', 'is_active', 'sort_order'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'parent_id');
    }

    /** @return HasMany<Subject, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Subject::class, 'parent_id');
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Faol yo'nalishlar, admin belgilagan tartibda.
     *
     * @param  Builder<Subject>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Nashr etilgan maqolalar soni: published_articles_count.
     *
     * @param  Builder<Subject>  $query
     */
    public function scopeWithPublishedArticlesCount(Builder $query): void
    {
        $query->withCount([
            'articles as published_articles_count' => fn ($articles) => $articles
                ->where('status', ArticleStatus::Published->value),
        ]);
    }
}

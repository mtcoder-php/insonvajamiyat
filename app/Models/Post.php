<?php

namespace App\Models;

use App\Enums\PostType;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Yangilik yoki e'lon (Content Manager boshqaradi).
 *
 * @property int $id
 * @property PostType $type
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $image_path
 * @property bool $is_published
 * @property bool $is_pinned
 * @property Carbon|null $published_at
 * @property int|null $author_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['type', 'slug', 'title', 'excerpt', 'body', 'image_path', 'is_published', 'is_pinned', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Chop etilgan (vaqti kelgan) postlar: avval qadalganlar, keyin eng yangilari.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeOfType(Builder $query, PostType $type): void
    {
        $query->where('type', $type->value);
    }
}

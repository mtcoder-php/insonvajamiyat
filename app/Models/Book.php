<?php

namespace App\Models;

use App\Support\ContentMedia;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Kutubxonadagi kitob (sayt: /library). "Tavsiya etilgan" (is_featured) kitoblar
 * bosh sahifaning o'ng ustunida ham ko'rinadi.
 *
 * PDF fayl maxfiy (local) diskda — o'qish/yuklab olish faqat route orqali (hisoblagich bilan).
 *
 * @property int $id
 * @property string|null $slug
 * @property string $title
 * @property string|null $description
 * @property int|null $subject_id
 * @property string $author
 * @property string|null $publisher
 * @property int|null $year
 * @property int|null $pages
 * @property string|null $isbn
 * @property string $language
 * @property string|null $cover_image_path
 * @property string|null $file_path
 * @property string|null $file_name
 * @property int|null $file_size
 * @property string|null $url
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $views_count
 * @property int $downloads_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subject|null $subject
 */
#[Fillable([
    'slug', 'title', 'description', 'subject_id', 'author', 'publisher', 'year', 'pages', 'isbn', 'language',
    'cover_image_path', 'file_path', 'file_name', 'file_size', 'url', 'sort_order', 'is_featured', 'is_active',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasTranslations;

    /** PDF fayllar papkasi (local disk) */
    public const FILE_DIR = 'library';

    /** @var array<int, string> */
    public array $translatable = ['title', 'description'];

    protected static function booted(): void
    {
        static::saving(function (Book $book): void {
            if (! is_string($book->slug) || $book->slug === '') {
                $title = (string) ($book->getTranslation('title', 'uz', false) ?: $book->getTranslation('title', app()->getLocale(), true));
                $book->slug = ContentMedia::uniqueSlug(self::query()->whereKeyNot($book->id ?? 0), $title, 'kitob');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'pages' => 'integer',
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function hasFile(): bool
    {
        return is_string($this->file_path) && $this->file_path !== '';
    }

    /**
     * Saytda ko'rinadigan kitoblar (tartib bo'yicha).
     *
     * @param  Builder<Book>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderByDesc('id');
    }

    /**
     * @param  Builder<Book>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}

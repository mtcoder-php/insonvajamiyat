<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Tadbir: konferensiya, seminar, forum.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $registration_url
 * @property string|null $image_path
 * @property bool $is_published
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['slug', 'title', 'description', 'location', 'starts_at', 'ends_at', 'registration_url', 'image_path', 'is_published'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'description', 'location'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Event>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Hali tugamagan tadbirlar, eng yaqini birinchi.
     *
     * @param  Builder<Event>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $today = now()->startOfDay();

        $query->where(fn ($q) => $q
            ->where('starts_at', '>=', $today)
            ->orWhere('ends_at', '>=', $today))
            ->orderBy('starts_at');
    }
}

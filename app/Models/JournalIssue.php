<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Database\Factories\JournalIssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * Jurnal soni (masalan, 2026-yil №3).
 *
 * @property int $id
 * @property int $year
 * @property int|null $volume
 * @property int $number
 * @property string $slug
 * @property string|null $title
 * @property string|null $description
 * @property string|null $cover_image_path
 * @property string|null $toc_file_path
 * @property string|null $full_pdf_path
 * @property IssueStatus $status
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $articles_count
 * @property-read string $label
 * @property-read Collection<int, Article> $articles
 */
#[Fillable([
    'year', 'volume', 'number', 'slug', 'title', 'description',
    'cover_image_path', 'toc_file_path', 'full_pdf_path',
])]
class JournalIssue extends Model
{
    /** @use HasFactory<JournalIssueFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'volume' => 'integer',
            'number' => 'integer',
            'status' => IssueStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** /issues/{slug} — masalan /issues/2026-3 */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'issue_articles')
            ->withPivot(['section', 'position', 'page_from', 'page_to'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * @param  Builder<JournalIssue>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', IssueStatus::Published->value)->whereNotNull('published_at');
    }

    /**
     * "№3 (2026)"
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatLabel());
    }

    private function formatLabel(): string
    {
        return "№{$this->number} ({$this->year})";
    }
}

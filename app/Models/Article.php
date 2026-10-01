<?php

namespace App\Models;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * Maqola — tizimning markaziy obyekti (yuborishdan nashrgacha).
 *
 * @property int $id
 * @property string $uuid
 * @property int $submitter_id
 * @property int $article_type_id
 * @property int|null $subject_id
 * @property int|null $handling_editor_id
 * @property string $language
 * @property string $title
 * @property string|null $abstract
 * @property mixed $keywords
 * @property string|null $udc
 * @property string|null $references
 * @property string|null $body_html
 * @property string|null $cover_image_path
 * @property int|null $pages_count
 * @property bool $is_fast_track
 * @property string|null $search_text
 * @property ArticleStatus $status
 * @property ArticlePaymentStatus $payment_status
 * @property int $review_round
 * @property bool $is_blind_review
 * @property string|null $slug
 * @property string|null $doi
 * @property int $views_count
 * @property int $downloads_count
 * @property string $rating_avg
 * @property int $ratings_count
 * @property Carbon|null $submitted_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $published_at
 * @property Carbon|null $withdrawn_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $submitter
 * @property-read ArticleType $articleType
 * @property-read Subject|null $subject
 * @property-read Collection<int, ArticleAuthor> $authors
 * @property-read Collection<int, JournalIssue> $issues
 * @property-read Collection<int, ArticleFile> $files
 * @property-read Collection<int, ArticleVersion> $versions
 * @property-read Collection<int, Payment> $payments
 * @property-read User|null $handlingEditor
 * @property-read Collection<int, ArticleNote> $notes
 * @property-read Collection<int, EditorialDecision> $decisions
 * @property-read Collection<int, ArticleStatusHistory> $statusHistories
 */
#[Fillable([
    'submitter_id', 'article_type_id', 'subject_id', 'language', 'title', 'abstract',
    'keywords', 'udc', 'references', 'body_html', 'cover_image_path', 'pages_count',
    'is_fast_track', 'slug', 'doi',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'abstract', 'keywords'];

    /**
     * Faqat `uuid` ustuni UUID; asosiy kalit (id) auto-increment bo'lib qoladi.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'payment_status' => ArticlePaymentStatus::class,
            'is_fast_track' => 'boolean',
            'is_blind_review' => 'boolean',
            'pages_count' => 'integer',
            'review_round' => 'integer',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'rating_avg' => 'decimal:2',
            'ratings_count' => 'integer',
            'production_checklist' => 'array',
            'plagiarism_percent' => 'decimal:2',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'chief_editor_approved_at' => 'datetime',
        ];
    }

    /** Ommaviy sahifada slug orqali ochiladi: /articles/{slug} */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    /** @return BelongsTo<ArticleType, $this> */
    public function articleType(): BelongsTo
    {
        return $this->belongsTo(ArticleType::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<ArticleFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class)->latest('id');
    }

    /** @return BelongsTo<User, $this> */
    public function handlingEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handling_editor_id');
    }

    /** @return HasMany<ArticleNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ArticleNote::class)->latest()->latest('id');
    }

    /** @return HasMany<EditorialDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(EditorialDecision::class)->latest()->latest('id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ArticleVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ArticleVersion::class)->orderBy('version_number');
    }

    /**
     * Holatlar tarixi (eng eskisi birinchi).
     *
     * @return HasMany<ArticleStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ArticleStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<ArticleAuthor, $this> */
    public function authors(): HasMany
    {
        return $this->hasMany(ArticleAuthor::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Maqola chop etilgan son (issue_articles.article_id unique — amalda bitta).
     *
     * @return BelongsToMany<JournalIssue, $this>
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(JournalIssue::class, 'issue_articles')
            ->withPivot(['section', 'position', 'page_from', 'page_to'])
            ->withTimestamps();
    }

    /**
     * Web qismda ko'rinadigan maqolalar.
     *
     * @param  Builder<Article>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->whereNotNull('slug');
    }

    /**
     * Foydalanuvchi muallif bo'lgan maqolalar: yuboruvchi yoki hammuallif.
     *
     * @param  Builder<Article>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('submitter_id', $user->id)
            ->orWhereHas('authors', fn (Builder $a) => $a->where('user_id', $user->id)));
    }

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published && $this->slug !== null;
    }
}

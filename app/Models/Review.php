<?php

namespace App\Models;

use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Taqriz: taqrizchi biriktiruvi va uning xulosasi (bitta taqriz raundi uchun).
 * Blind review: muallifga reviewer_id hech qachon ko'rsatilmaydi, taqrizchiga — mualliflar.
 *
 * @property int $id
 * @property int $article_id
 * @property int $reviewer_id
 * @property int|null $assigned_by
 * @property int $round
 * @property ReviewStatus $status
 * @property ReviewRecommendation|null $recommendation
 * @property string|null $score
 * @property array<string, float>|null $criteria_scores
 * @property string|null $comments_to_author
 * @property string|null $comments_to_editor
 * @property string|null $attachment_path
 * @property Carbon|null $due_at
 * @property Carbon|null $responded_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $reviewer
 * @property-read User|null $assigner
 */
#[Fillable(['article_id', 'reviewer_id', 'assigned_by', 'round', 'due_at'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'recommendation' => ReviewRecommendation::class,
            'score' => 'decimal:1',
            'criteria_scores' => 'array',
            'round' => 'integer',
            'due_at' => 'datetime',
            'responded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** Muddatgacha qolgan kunlar (manfiy — muddati o'tgan), muddat yo'q bo'lsa null */
    public function daysLeft(): ?int
    {
        return $this->due_at !== null
            ? (int) floor(now()->startOfDay()->diffInDays($this->due_at->copy()->startOfDay(), false))
            : null;
    }
}

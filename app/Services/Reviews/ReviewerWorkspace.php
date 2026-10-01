<?php

namespace App\Services\Reviews;

use App\Enums\ArticleFileType;
use App\Enums\EditorialDecisionType;
use App\Enums\Language;
use App\Enums\ReviewCriterion;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Models\ArticleFile;
use App\Models\EditorialDecision;
use App\Models\Review;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Taqrizchi ish joyi ("Taqrizlarim", admin reviewer.png). Blind review: taqrizchiga
 * mualliflar va ularning tashkiloti ko'rsatilmaydi.
 */
class ReviewerWorkspace
{
    public const PER_PAGE = 15;

    /** Tab → holatlar */
    public const TABS = [
        'invited' => [ReviewStatus::Invited],
        'active' => [ReviewStatus::Accepted],
        'completed' => [ReviewStatus::Completed],
        'closed' => [ReviewStatus::Declined, ReviewStatus::Cancelled],
        'all' => [],
    ];

    /**
     * @return array<string, int>
     */
    public function counts(User $reviewer): array
    {
        $byStatus = Review::query()
            ->where('reviewer_id', $reviewer->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];

        foreach (self::TABS as $key => $statuses) {
            $counts[$key] = (int) collect($statuses)->sum(fn (ReviewStatus $s): int => (int) ($byStatus[$s->value] ?? 0));
        }

        $counts['all'] = (int) $byStatus->sum();

        return $counts;
    }

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function list(User $reviewer, string $tab): LengthAwarePaginator
    {
        $statuses = self::TABS[$tab] ?? [];

        return Review::query()
            ->where('reviewer_id', $reviewer->id)
            ->when($statuses !== [], fn (Builder $q) => $q->whereIn('status', array_map(fn (ReviewStatus $s): string => $s->value, $statuses)))
            ->with(['article.subject', 'article.articleType'])
            ->orderByRaw("case when status in ('invited', 'accepted') then 0 else 1 end")
            ->orderBy('due_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function listItem(Review $review): array
    {
        return [
            'id' => $review->id,
            'code' => EditorialWorkspace::code($review->article),
            'title' => $review->article->title,
            'subject' => $review->article->subject?->name,
            'type' => $review->article->articleType->name,
            'round' => $review->round,
            'status' => $review->status->value,
            'statusLabel' => $review->status->label(),
            'recommendation' => $review->recommendation?->label(),
            'invitedAt' => $review->created_at?->toIso8601String(),
            'dueAt' => $review->due_at?->toIso8601String(),
            'daysLeft' => $review->status->isActive() ? $review->daysLeft() : null,
            'url' => route('admin.reviews.show', $review->id),
        ];
    }

    /**
     * Taqriz sahifasi: anonim maqola, fayllar, taqriz formasi va jarayon.
     *
     * @return array<string, mixed>
     */
    public function detail(Review $review): array
    {
        $review->load(['article.subject', 'article.articleType', 'article.files', 'article.decisions']);
        $article = $review->article;
        $canSeeFiles = in_array($review->status, [ReviewStatus::Accepted, ReviewStatus::Completed], true);
        // Shu raund bo'yicha muharrir yakuniy qarori (taqrizga yuborishdan tashqari)
        $decided = $article->decisions->first(
            fn (EditorialDecision $decision): bool => $decision->round === $review->round
                && $decision->decision !== EditorialDecisionType::SendToReview,
        );

        return [
            'id' => $review->id,
            'status' => $review->status->value,
            'statusLabel' => $review->status->label(),
            'round' => $review->round,
            'dueAt' => $review->due_at?->toIso8601String(),
            'daysLeft' => $review->status->isActive() ? $review->daysLeft() : null,
            'invitedAt' => $review->created_at?->toIso8601String(),
            'respondedAt' => $review->responded_at?->toIso8601String(),
            'completedAt' => $review->completed_at?->toIso8601String(),
            'article' => [
                'code' => EditorialWorkspace::code($article),
                'title' => $article->title,
                'subject' => $article->subject?->name,
                'type' => $article->articleType->name,
                'language' => $article->language,
                'submittedAt' => $article->submitted_at?->toIso8601String(),
                'abstracts' => $this->localized($review, 'abstract'),
                'titles' => $this->localized($review, 'title'),
                'keywords' => $this->keywords($review),
                'files' => $canSeeFiles
                    ? $article->files
                        ->filter(fn (ArticleFile $file): bool => in_array($file->type, [ArticleFileType::Manuscript, ArticleFileType::Revision, ArticleFileType::Supplementary], true))
                        ->values()
                        ->map(fn (ArticleFile $file): array => [
                            'uuid' => $file->uuid,
                            'name' => $file->original_name,
                            'typeLabel' => $file->type->label(),
                            'extension' => $file->extension(),
                            'size' => $file->size,
                            'viewUrl' => route('admin.reviews.files', [$review->id, $file->uuid]),
                            'downloadUrl' => route('admin.reviews.files', [$review->id, $file->uuid, 'download' => 1]),
                        ])->all()
                    : [],
            ],
            'form' => [
                'score' => $review->score !== null ? (float) $review->score : null,
                'criteria' => $review->criteria_scores ?? (object) [],
                'recommendation' => $review->recommendation?->value,
                'comments_to_author' => $review->comments_to_author,
                'comments_to_editor' => $review->comments_to_editor,
                'attachmentUrl' => $review->attachment_path !== null ? route('admin.reviews.attachment', $review->id) : null,
            ],
            'decisionMade' => $decided !== null,
            'can' => [
                'respond' => $review->status === ReviewStatus::Invited,
                'edit' => $review->status === ReviewStatus::Accepted,
            ],
            'urls' => [
                'accept' => route('admin.reviews.accept', $review->id),
                'decline' => route('admin.reviews.decline', $review->id),
                'save' => route('admin.reviews.update', $review->id),
                'index' => route('admin.reviews.index'),
            ],
        ];
    }

    /**
     * @return array{criteria: array<int, array{key: string, label: string}>, recommendations: array<int, array{value: string, label: string}>}
     */
    public static function options(): array
    {
        return [
            'criteria' => ReviewCriterion::options(),
            'recommendations' => array_map(fn (ReviewRecommendation $r): array => [
                'value' => $r->value,
                'label' => $r->label(),
            ], ReviewRecommendation::cases()),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function localized(Review $review, string $key): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $value = $review->article->getTranslation($key, $language->value, false);
            $values[$language->value] = is_string($value) && $value !== '' ? $value : null;
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function keywords(Review $review): array
    {
        $words = [];

        foreach (Language::cases() as $language) {
            $value = $review->article->getTranslation('keywords', $language->value, false);

            if (is_array($value)) {
                array_push($words, ...array_values(array_filter($value, 'is_string')));
            }
        }

        return $words;
    }
}

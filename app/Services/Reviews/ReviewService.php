<?php

namespace App\Services\Reviews;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\EditorialDecisionType;
use App\Enums\ReviewCriterion;
use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Review;
use App\Models\User;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\EditorialNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Taqriz jarayoni (blind review):
 *   muharrir taqrizchilarni taklif qiladi → taqrizchi qabul qiladi / rad etadi →
 *   baholash mezonlari, tavsiya va izohlar bilan taqriz topshiradi → muharrir qaror qiladi.
 *
 * Taklif qilinganda maqola "Taqrizda" (InReview) holatiga o'tadi; yangi raund
 * (birinchi marta yoki qayta yuborilgandan keyin) review_round ni oshiradi.
 */
class ReviewService
{
    public const DISK = 'local';

    public const DEFAULT_DUE_DAYS = 14;

    public const MAX_REVIEWERS = 5;

    /** Taqrizchi taklif qilish mumkin bo'lgan holatlar */
    public const INVITABLE = [ArticleStatus::UnderReview, ArticleStatus::InReview, ArticleStatus::Resubmitted];

    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly AuditLogger $audit,
        private readonly EditorialNotifier $notifier,
    ) {}

    /**
     * @param  array<int, int>  $reviewerIds
     * @return Collection<int, Review>
     */
    public function invite(Article $article, array $reviewerIds, int $dueDays, User $editor): Collection
    {
        if (! in_array($article->status, self::INVITABLE, true)) {
            throw ValidationException::withMessages([
                'reviewer_ids' => __("Maqolaning hozirgi holatida («:status») taqrizchi tayinlab bo'lmaydi.", [
                    'status' => $article->status->label(),
                ]),
            ]);
        }

        $reviewers = User::query()->whereIn('id', $reviewerIds)->get();

        foreach ($reviewers as $reviewer) {
            if (! self::canReview($reviewer) || $reviewer->id === $article->submitter_id || $this->isAuthor($article, $reviewer)) {
                throw ValidationException::withMessages([
                    'reviewer_ids' => __(':name bu maqolaga taqrizchi bo\'la olmaydi.', ['name' => $reviewer->name]),
                ]);
            }
        }

        $newRound = $article->status !== ArticleStatus::InReview;
        $round = $newRound ? $article->review_round + 1 : max(1, $article->review_round);

        $existing = $article->reviews()->where('round', $round)->pluck('reviewer_id')->all();
        $fresh = $reviewers->reject(fn (User $user): bool => in_array($user->id, $existing, true));

        if ($fresh->isEmpty()) {
            throw ValidationException::withMessages([
                'reviewer_ids' => __('Tanlangan taqrizchilar bu raundda allaqachon tayinlangan.'),
            ]);
        }

        $reviews = DB::transaction(function () use ($article, $fresh, $dueDays, $editor, $round, $newRound): Collection {
            $reviews = $fresh->values()->map(fn (User $reviewer): Review => $article->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'assigned_by' => $editor->id,
                'round' => $round,
                'due_at' => now()->addDays($dueDays)->endOfDay(),
            ]));

            if ($newRound) {
                $article->review_round = $round;

                if ($article->handling_editor_id === null) {
                    $article->handling_editor_id = $editor->id;
                }

                $article->decisions()->create([
                    'editor_id' => $editor->id,
                    'round' => $round,
                    'decision' => EditorialDecisionType::SendToReview,
                ]);

                $this->workflow->transition(
                    $article,
                    ArticleStatus::InReview,
                    $editor,
                    __('Maqolangiz mustaqil taqrizchilarga yuborildi. Taqriz natijalari haqida xabar beramiz.'),
                );
            }

            $this->audit->log(AuditEvent::ReviewInvited, $article, [
                'reviewers' => $fresh->map(fn (User $u): string => $u->name)->values()->all(),
                'round' => $round,
                'due_days' => $dueDays,
            ], actor: $editor);

            return $reviews;
        });

        $this->notifier->reviewersInvited($article, $reviews);

        return $reviews;
    }

    public function accept(Review $review, User $reviewer): void
    {
        $this->ensureOwner($review, $reviewer);
        $this->ensureStatus($review, [ReviewStatus::Invited]);

        $review->forceFill(['status' => ReviewStatus::Accepted, 'responded_at' => now()])->save();

        $this->notifier->reviewerResponded($review, 'accepted');
    }

    public function decline(Review $review, User $reviewer, ?string $reason): void
    {
        $this->ensureOwner($review, $reviewer);
        $this->ensureStatus($review, [ReviewStatus::Invited]);

        $review->forceFill([
            'status' => ReviewStatus::Declined,
            'responded_at' => now(),
            'comments_to_editor' => $reason,
        ])->save();

        $this->notifier->reviewerResponded($review, 'declined', $reason);
    }

    /**
     * Taqrizni saqlash ($submit=false — qoralama) yoki topshirish.
     *
     * @param  array{score: float|null, criteria: array<string, float>, recommendation: ReviewRecommendation|null, comments_to_author: string|null, comments_to_editor: string|null}  $data
     */
    public function save(Review $review, User $reviewer, array $data, bool $submit, ?UploadedFile $attachment = null): void
    {
        $this->ensureOwner($review, $reviewer);
        $this->ensureStatus($review, [ReviewStatus::Accepted]);

        if ($submit) {
            $missing = array_diff(
                array_map(fn (ReviewCriterion $c): string => $c->value, ReviewCriterion::cases()),
                array_keys($data['criteria']),
            );

            if ($missing !== [] || $data['recommendation'] === null || blank($data['comments_to_author'])) {
                throw ValidationException::withMessages([
                    'review' => __('Taqrizni topshirish uchun barcha mezonlarni baholang, tavsiyani tanlang va izoh yozing.'),
                ]);
            }
        }

        $criteria = $data['criteria'];
        $score = $data['score'] ?? ($criteria !== [] ? round(array_sum($criteria) / count($criteria) * 2) / 2 : null);

        DB::transaction(function () use ($review, $data, $submit, $attachment, $criteria, $score): void {
            if ($attachment !== null) {
                $this->deleteAttachment($review);
                $path = $attachment->storeAs(
                    "reviews/{$review->id}",
                    'taqriz.'.(strtolower($attachment->getClientOriginalExtension()) ?: 'bin'),
                    self::DISK,
                );

                if ($path === false) {
                    throw new RuntimeException('Review attachment could not be stored.');
                }

                $review->attachment_path = $path;
            }

            $review->forceFill([
                'score' => $score,
                'criteria_scores' => $criteria !== [] ? $criteria : null,
                'recommendation' => $data['recommendation'],
                'comments_to_author' => $data['comments_to_author'],
                'comments_to_editor' => $data['comments_to_editor'],
            ]);

            if ($submit) {
                $review->forceFill(['status' => ReviewStatus::Completed, 'completed_at' => now()]);
            }

            $review->save();
        });

        if ($submit) {
            $this->notifier->reviewerResponded($review, 'completed');
        }
    }

    /** Muharrir taklifni bekor qiladi (faqat hali yakunlanmagan taqriz) */
    public function cancel(Review $review): void
    {
        $this->ensureStatus($review, [ReviewStatus::Invited, ReviewStatus::Accepted]);

        $review->forceFill(['status' => ReviewStatus::Cancelled])->save();

        $this->audit->log(AuditEvent::ReviewCancelled, $review->article, [
            'review' => $review->id,
            'reviewer' => $review->reviewer->name,
        ]);
    }

    /**
     * Taqrizchi uchun maqola fayli: PDF — brauzerda ko'rish, boshqalari — yuklab olish.
     */
    public function articleFile(ArticleFile $file, bool $inline): StreamedResponse
    {
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $inline && $file->extension() === 'pdf'
            ? $disk->response($file->path, $file->original_name)
            : $disk->download($file->path, $file->original_name);
    }

    public function attachment(Review $review): StreamedResponse
    {
        abort_unless($review->attachment_path !== null && Storage::disk(self::DISK)->exists($review->attachment_path), 404);

        return Storage::disk(self::DISK)->download($review->attachment_path);
    }

    /** Taqrizchi bo'la oladigan foydalanuvchi: "Taqrizchi" roli, bloklanmagan */
    public static function canReview(User $user): bool
    {
        return ! $user->is_blocked && $user->hasRole(RoleName::Reviewer);
    }

    private function isAuthor(Article $article, User $user): bool
    {
        return $article->authors()->where('user_id', $user->id)->exists();
    }

    private function deleteAttachment(Review $review): void
    {
        if ($review->attachment_path !== null) {
            Storage::disk(self::DISK)->delete($review->attachment_path);
        }
    }

    private function ensureOwner(Review $review, User $user): void
    {
        if ($review->reviewer_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * @param  array<int, ReviewStatus>  $allowed
     */
    private function ensureStatus(Review $review, array $allowed): void
    {
        if (! in_array($review->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'review' => __('Taqrizning hozirgi holatida («:status») bu amalni bajarib bo\'lmaydi.', [
                    'status' => $review->status->label(),
                ]),
            ]);
        }
    }
}

<?php

namespace App\Services\Articles;

use App\Enums\ArticleStatus;
use App\Events\ArticleStatusChanged;
use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maqola holatini o'zgartirishning YAGONA yo'li.
 *
 *  - O'tish ArticleStatus::transitions() bo'yicha tekshiriladi (state machine)
 *  - Hayotiy sikl vaqt belgilari (submitted_at, accepted_at, ...) qo'yiladi
 *  - Har bir o'tish article_status_histories ga yoziladi (kim, qachon, izoh)
 *  - ArticleStatusChanged event'i yuboriladi (bildirishnomalar uchun)
 */
class ArticleWorkflow
{
    public function canTransition(Article $article, ArticleStatus $to): bool
    {
        return $article->status->canTransitionTo($to);
    }

    /**
     * @param  bool  $visibleToAuthor  Muallif tarixda ko'radimi (ichki qadamlar uchun false)
     */
    public function transition(
        Article $article,
        ArticleStatus $to,
        ?User $actor,
        ?string $comment = null,
        bool $visibleToAuthor = true,
    ): ArticleStatusHistory {
        $from = $article->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => __("Maqolani «:from» holatidan «:to» holatiga o'tkazib bo'lmaydi.", [
                    'from' => $from->label(),
                    'to' => $to->label(),
                ]),
            ]);
        }

        $history = DB::transaction(function () use ($article, $from, $to, $actor, $comment, $visibleToAuthor): ArticleStatusHistory {
            $article->status = $to;
            $this->stampLifecycle($article, $to);
            $article->save();

            return $article->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'comment' => $comment,
                'is_visible_to_author' => $visibleToAuthor,
                'created_at' => now(),
            ]);
        });

        ArticleStatusChanged::dispatch($article, $from, $to, $actor, $comment);

        return $history;
    }

    /**
     * Yangi yaratilgan maqolaning birinchi tarix yozuvi (from = null).
     */
    public function recordInitial(Article $article, ?User $actor): ArticleStatusHistory
    {
        return $article->statusHistories()->create([
            'from_status' => null,
            'to_status' => $article->status,
            'changed_by' => $actor?->id,
            'is_visible_to_author' => true,
            'created_at' => now(),
        ]);
    }

    private function stampLifecycle(Article $article, ArticleStatus $to): void
    {
        $column = match ($to) {
            ArticleStatus::Submitted => 'submitted_at',
            ArticleStatus::Accepted => 'accepted_at',
            ArticleStatus::Rejected => 'rejected_at',
            ArticleStatus::Published => 'published_at',
            ArticleStatus::Withdrawn => 'withdrawn_at',
            default => null,
        };

        // Yuborilgan / nashr etilgan vaqt birinchi marta qo'yiladi (qayta yuborishda o'zgarmaydi)
        $keepFirst = in_array($to, [ArticleStatus::Submitted, ArticleStatus::Published], true);

        if ($column !== null && (! $keepFirst || $article->getAttribute($column) === null)) {
            $article->forceFill([$column => now()]);
        }
    }
}

<?php

namespace App\Services\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleStatusHistory;

/**
 * Muallifga ko'rsatiladigan soddalashtirilgan jarayon (timeline):
 * Yuborildi → Muharrir ko'rigi → Taqriz → Tuzatish → Qabul qilindi → Nashr.
 *
 * Har bir qadam: done (o'tilgan), current (hozir), pending (kutilmoqda), skipped (bo'lmagan),
 * sana — tarixdagi shu qadamga birinchi o'tish vaqti. Rad etilgan / qaytarib olingan
 * maqolada erishilgan qadamdan keyin yakuniy holat ko'rsatiladi.
 */
class ArticleTimeline
{
    /** @var array<string, array{label: string, statuses: array<int, ArticleStatus>}> */
    private const STEPS = [
        'submitted' => ['label' => 'Yuborildi', 'statuses' => [ArticleStatus::Submitted, ArticleStatus::AwaitingPayment]],
        'editor' => ['label' => "Muharrir ko'rigi", 'statuses' => [ArticleStatus::UnderReview]],
        'review' => ['label' => 'Taqriz', 'statuses' => [ArticleStatus::InReview]],
        'revision' => ['label' => 'Tuzatish', 'statuses' => [ArticleStatus::RevisionRequired, ArticleStatus::Resubmitted]],
        'accepted' => ['label' => 'Qabul qilindi', 'statuses' => [ArticleStatus::Accepted, ArticleStatus::InProduction]],
        'published' => ['label' => 'Nashr', 'statuses' => [ArticleStatus::Published]],
    ];

    /**
     * @return array<int, array{key: string, label: string, state: string, date: string|null}>
     */
    public function for(Article $article): array
    {
        $histories = $article->relationLoaded('statusHistories')
            ? $article->statusHistories
            : $article->statusHistories()->get();

        $keys = array_keys(self::STEPS);
        $currentIndex = $this->stepIndex($article->status, $histories->all());
        $closed = in_array($article->status, [ArticleStatus::Rejected, ArticleStatus::Withdrawn], true);
        $steps = [];

        foreach ($keys as $index => $key) {
            $statuses = self::STEPS[$key]['statuses'];
            $first = $histories->first(fn (ArticleStatusHistory $h): bool => in_array($h->to_status, $statuses, true));

            $state = match (true) {
                $index < $currentIndex => $first !== null || $key === 'submitted' ? 'done' : 'skipped',
                $index === $currentIndex => $closed || $article->status === ArticleStatus::Published ? 'done' : 'current',
                default => 'pending',
            };

            $steps[] = [
                'key' => $key,
                'label' => self::STEPS[$key]['label'],
                'state' => $state,
                'date' => $first?->created_at->toIso8601String()
                    ?? ($key === 'submitted' ? $article->submitted_at?->toIso8601String() : null),
            ];
        }

        // Yakuniy salbiy holat (rad etilgan / qaytarib olingan): erishilgan qadamdan keyin
        // shu holat ko'rsatiladi, qolgan qadamlar olib tashlanadi
        if ($closed) {
            $steps = array_slice($steps, 0, $currentIndex + 1);
            $steps[] = [
                'key' => $article->status->value,
                'label' => $article->status->label(),
                'state' => 'failed',
                'date' => ($article->rejected_at ?? $article->withdrawn_at)?->toIso8601String(),
            ];
        }

        return $steps;
    }

    /**
     * @param  array<int, ArticleStatusHistory>  $histories
     */
    private function stepIndex(ArticleStatus $status, array $histories): int
    {
        $keys = array_keys(self::STEPS);

        foreach ($keys as $index => $key) {
            if (in_array($status, self::STEPS[$key]['statuses'], true)) {
                return $index;
            }
        }

        // Qoralama — hali yuborilmagan; rad / qaytarib olingan — erishilgan oxirgi qadam
        if ($status === ArticleStatus::Draft) {
            return -1;
        }

        $reached = 0;

        foreach ($histories as $history) {
            foreach ($keys as $index => $key) {
                if (in_array($history->to_status, self::STEPS[$key]['statuses'], true)) {
                    $reached = max($reached, $index);
                }
            }
        }

        return $reached;
    }
}

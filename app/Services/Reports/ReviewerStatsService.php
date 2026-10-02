<?php

namespace App\Services\Reports;

use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Review;
use App\Models\User;
use App\Support\MediaUrl;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Taqrizchilar" tabi: har bir taqrizchining davrdagi yuklamasi va tezligi.
 *
 *  invited   — davrda yuborilgan takliflar
 *  accepted  — qabul qilganlari (rad etilmagan va javob berilgan)
 *  declined  — rad etganlari
 *  completed — davrda topshirilgan xulosalar
 *  pending   — hozir ishlayotgan / javob kutilayotgan (davrdan qat'i nazar)
 *  overdue   — pending ichidan muddati o'tganlari
 *  avgDays   — taklifdan xulosagacha o'rtacha kun
 *  onTime    — muddatida topshirilgan xulosalar ulushi (%)
 */
class ReviewerStatsService
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, int|float|null>}
     */
    public function table(ReportPeriod $period, ?int $subjectId = null): array
    {
        $reviews = fn (): Builder => Review::query()->when(
            $subjectId !== null,
            fn (Builder $q) => $q->whereHas('article', fn ($a) => $a->where('subject_id', $subjectId)),
        );

        /** @var Collection<int, Review> $invited */
        $invited = $reviews()->whereBetween('created_at', $period->range())
            ->get(['id', 'reviewer_id', 'status', 'responded_at']);

        /** @var Collection<int, Review> $completed */
        $completed = $reviews()->where('status', ReviewStatus::Completed->value)
            ->whereBetween('completed_at', $period->range())
            ->get(['id', 'reviewer_id', 'created_at', 'completed_at', 'due_at']);

        /** @var Collection<int, Review> $pending */
        $pending = $reviews()->whereIn('status', [ReviewStatus::Invited->value, ReviewStatus::Accepted->value])
            ->get(['id', 'reviewer_id', 'due_at']);

        $reviewerIds = $invited->pluck('reviewer_id')
            ->merge($completed->pluck('reviewer_id'))
            ->merge($pending->pluck('reviewer_id'));

        $reviewers = User::query()
            ->withTrashed()
            ->where(fn (Builder $q) => $q->whereIn('id', $reviewerIds->unique()->all())
                ->orWhereHas('roles', fn (Builder $r) => $r->where('name', RoleName::Reviewer->value)))
            ->with('authorProfile')
            ->orderBy('name')
            ->get();

        $rows = $reviewers->map(function (User $user) use ($invited, $completed, $pending): array {
            $mine = $invited->where('reviewer_id', $user->id);
            $done = $completed->where('reviewer_id', $user->id);
            $open = $pending->where('reviewer_id', $user->id);

            $onTime = $done->filter(fn (Review $r): bool => $r->due_at === null || ($r->completed_at !== null && $r->completed_at->lessThanOrEqualTo($r->due_at)));

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatarUrl' => MediaUrl::from($user->authorProfile?->avatar_path),
                'degree' => $user->authorProfile?->academic_degree,
                'invited' => $mine->count(),
                'accepted' => $mine->filter(fn (Review $r): bool => $r->status !== ReviewStatus::Declined && $r->responded_at !== null)->count(),
                'declined' => $mine->where('status', ReviewStatus::Declined)->count(),
                'completed' => $done->count(),
                'pending' => $open->count(),
                'overdue' => $open->filter(fn (Review $r): bool => $r->due_at !== null && $r->due_at->isPast())->count(),
                'avgDays' => $this->averageDays($done),
                'onTime' => $done->isEmpty() ? null : (int) round($onTime->count() / $done->count() * 100),
            ];
        })
            ->sortByDesc(fn (array $row): int => $row['completed'] * 1000 + $row['pending'] * 10 + $row['invited'])
            ->values();

        return [
            'rows' => $rows->all(),
            'totals' => [
                'reviewers' => $rows->count(),
                'invited' => (int) $rows->sum('invited'),
                'completed' => (int) $rows->sum('completed'),
                'declined' => (int) $rows->sum('declined'),
                'pending' => (int) $rows->sum('pending'),
                'overdue' => (int) $rows->sum('overdue'),
                'avgDays' => $this->averageDays($completed),
            ],
        ];
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    private function averageDays(Collection $reviews): ?float
    {
        $hours = $reviews
            ->filter(fn (Review $r): bool => $r->created_at !== null && $r->completed_at !== null)
            ->map(fn (Review $r): float => max(0.0, (float) $r->created_at?->diffInHours($r->completed_at)));

        return $hours->isEmpty() ? null : round((float) $hours->avg() / 24, 1);
    }
}

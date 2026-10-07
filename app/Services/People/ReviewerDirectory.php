<?php

namespace App\Services\People;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Enums\RoleName;
use App\Models\Review;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Admin → Taqrizchilar: taqrizchilar bazasi, yuklama, tezlik va taqrizlar tarixi.
 *
 *  active    — taklif qilingan yoki ishlayotgan (invited + accepted)
 *  overdue   — active ichidan muddati o'tganlari
 *  avgDays   — taklifdan xulosagacha o'rtacha kun (barcha davr)
 *  onTime    — muddatida topshirilgan xulosalar ulushi (%)
 *  acceptRate — javob berilgan takliflardan qabul qilinganlari (%)
 */
class ReviewerDirectory
{
    public const PER_PAGE = 15;

    /** Shuncha faol taqrizi bo'lgan taqrizchi "band" hisoblanadi */
    public const BUSY_FROM = 3;

    public const STATUSES = ['available', 'busy', 'overdue', 'paused'];

    public const SORTS = ['name', 'load', 'completed', 'latest'];

    /**
     * @param  array<string, mixed>  $input
     * @return array{search: string, subject: int|null, status: string, sort: string}
     */
    public static function filters(array $input): array
    {
        $search = $input['search'] ?? '';
        $subject = $input['subject'] ?? null;
        $status = $input['status'] ?? '';
        $sort = $input['sort'] ?? 'name';

        return [
            'search' => is_string($search) ? mb_substr(trim($search), 0, 100) : '',
            'subject' => is_numeric($subject) && (int) $subject > 0 ? (int) $subject : null,
            'status' => is_string($status) && in_array($status, self::STATUSES, true) ? $status : '',
            'sort' => is_string($sort) && in_array($sort, self::SORTS, true) ? $sort : 'name',
        ];
    }

    /**
     * @param  array{search: string, subject: int|null, status: string, sort: string}  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array{currentPage: int, lastPage: int, total: int, from: int|null, to: int|null}}
     */
    public function list(array $filters, int $page = 1): array
    {
        $active = [ReviewStatus::Invited->value, ReviewStatus::Accepted->value];

        $query = User::query()
            ->role(RoleName::Reviewer->value)
            ->with(['authorProfile', 'subjects'])
            ->withCount([
                'reviews as active_count' => fn ($q) => $q->whereIn('status', $active),
                'reviews as completed_count' => fn ($q) => $q->where('status', ReviewStatus::Completed->value),
                'reviews as declined_count' => fn ($q) => $q->where('status', ReviewStatus::Declined->value),
                'reviews as overdue_count' => fn ($q) => $q->whereIn('status', $active)->where('due_at', '<', now()),
            ]);

        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';

            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('authorProfile', fn (Builder $p) => $p->where('organization', 'like', $like)));
        }

        if ($filters['subject'] !== null) {
            $subject = $filters['subject'];
            $query->whereHas('subjects', fn (Builder $s) => $s->whereKey($subject));
        }

        $activeReviews = fn ($q) => $q->whereIn('status', $active);

        $query = match ($filters['status']) {
            'available' => $query->whereNull('reviews_paused_at')->where('is_blocked', false)
                ->whereHas('reviews', $activeReviews, '<', self::BUSY_FROM),
            'busy' => $query->whereHas('reviews', $activeReviews, '>=', self::BUSY_FROM),
            'overdue' => $query->whereHas('reviews', fn ($q) => $q->whereIn('status', $active)->where('due_at', '<', now())),
            'paused' => $query->whereNotNull('reviews_paused_at'),
            default => $query,
        };

        $query = match ($filters['sort']) {
            'load' => $query->orderByDesc('active_count')->orderBy('name'),
            'completed' => $query->orderByDesc('completed_count')->orderBy('name'),
            'latest' => $query->latest()->latest('id'),
            default => $query->orderBy('name'),
        };

        $paginator = $query->paginate(self::PER_PAGE, page: $page);

        /** @var array<int, User> $users */
        $users = $paginator->items();
        $completed = $this->completedReviews(array_map(fn (User $u): int => $u->id, $users));

        return [
            'data' => array_map(function (User $user) use ($completed): array {
                $done = $completed->where('reviewer_id', $user->id);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatarUrl' => $user->avatarUrl(),
                    'organization' => $user->authorProfile?->organization,
                    'degree' => $user->authorProfile?->academic_degree,
                    'subjects' => array_column(PersonPresenter::subjects($user), 'name'),
                    'active' => (int) $user->getAttribute('active_count'),
                    'completed' => (int) $user->getAttribute('completed_count'),
                    'declined' => (int) $user->getAttribute('declined_count'),
                    'overdue' => (int) $user->getAttribute('overdue_count'),
                    'avgDays' => self::averageDays($done),
                    'onTime' => self::onTime($done),
                    'isPaused' => $user->reviews_paused_at !== null,
                    'isBlocked' => $user->is_blocked,
                    'url' => route('admin.reviewers.show', $user->id),
                ];
            }, $users),
            'meta' => AuthorDirectory::meta($paginator),
        ];
    }

    /**
     * @return array{total: int, available: int, paused: int, active: int, overdue: int, avgDays: float|null}
     */
    public function counts(): array
    {
        $active = [ReviewStatus::Invited->value, ReviewStatus::Accepted->value];
        $reviewers = fn (): Builder => User::query()->role(RoleName::Reviewer->value);

        $completed = Review::query()
            ->where('status', ReviewStatus::Completed->value)
            ->whereIn('reviewer_id', $reviewers()->select('id'))
            ->get(['id', 'reviewer_id', 'created_at', 'completed_at', 'due_at']);

        return [
            'total' => $reviewers()->count(),
            'available' => $reviewers()->whereNull('reviews_paused_at')->where('is_blocked', false)
                ->whereHas('reviews', fn ($q) => $q->whereIn('status', $active), '<', self::BUSY_FROM)
                ->count(),
            'paused' => $reviewers()->whereNotNull('reviews_paused_at')->count(),
            'active' => Review::query()->whereIn('status', $active)->count(),
            'overdue' => Review::query()->whereIn('status', $active)->where('due_at', '<', now())->count(),
            'avgDays' => self::averageDays($completed),
        ];
    }

    /**
     * Taqrizchi sahifasi: profil, yo'nalishlar, ko'rsatkichlar va taqrizlar tarixi.
     *
     * @return array<string, mixed>
     */
    public function detail(User $user): array
    {
        $user->loadMissing(['authorProfile', 'subjects']);

        /** @var Collection<int, Review> $reviews */
        $reviews = $user->reviews()
            ->with(['article' => fn ($q) => $q->withTrashed()->with('subject')])
            ->latest()
            ->latest('id')
            ->get();

        $done = $reviews->where('status', ReviewStatus::Completed);
        $active = $reviews->filter(fn (Review $r): bool => $r->status->isActive());
        $answered = $reviews->filter(fn (Review $r): bool => $r->responded_at !== null || $r->status === ReviewStatus::Completed);
        $declined = $reviews->where('status', ReviewStatus::Declined);
        $scores = $done->map(fn (Review $r): ?float => $r->score !== null ? (float) $r->score : null)->filter(fn (?float $v): bool => $v !== null);

        $recommendations = [];

        foreach ($done as $review) {
            if ($review->recommendation !== null) {
                $key = $review->recommendation->value;
                $recommendations[$key] = ($recommendations[$key] ?? 0) + 1;
            }
        }

        return [
            'profile' => PersonPresenter::profile($user),
            'subjects' => PersonPresenter::subjects($user),
            'isReviewer' => $user->hasRole(RoleName::Reviewer),
            'isPaused' => $user->reviews_paused_at !== null,
            'pausedAt' => $user->reviews_paused_at?->toIso8601String(),
            'stats' => [
                'invited' => $reviews->count(),
                'active' => $active->count(),
                'completed' => $done->count(),
                'declined' => $declined->count(),
                'overdue' => $active->filter(fn (Review $r): bool => $r->due_at !== null && $r->due_at->isPast())->count(),
                'avgDays' => self::averageDays($done),
                'onTime' => self::onTime($done),
                'acceptRate' => $answered->isEmpty() ? null : (int) round(($answered->count() - $declined->count()) / $answered->count() * 100),
                'avgScore' => $scores->isEmpty() ? null : round((float) $scores->avg(), 1),
                'recommendations' => $recommendations,
            ],
            'reviews' => $reviews->take(100)->map(fn (Review $r): array => [
                'id' => $r->id,
                'round' => $r->round,
                'status' => $r->status->value,
                'statusLabel' => $r->status->label(),
                'recommendation' => $r->recommendation?->value,
                'recommendationLabel' => $r->recommendation?->label(),
                'score' => $r->score !== null ? (float) $r->score : null,
                'invitedAt' => $r->created_at?->toIso8601String(),
                'dueAt' => $r->due_at?->toIso8601String(),
                'completedAt' => $r->completed_at?->toIso8601String(),
                'isOverdue' => $r->status->isActive() && $r->due_at !== null && $r->due_at->isPast(),
                'article' => [
                    'code' => EditorialWorkspace::code($r->article),
                    'title' => $r->article->title,
                    'subject' => $r->article->subject?->name,
                    'url' => $r->article->trashed() || $r->article->status === ArticleStatus::Draft
                        ? null
                        : route('admin.articles.index', ['queue' => 'all', 'article' => $r->article->uuid]),
                ],
            ])->values()->all(),
        ];
    }

    /**
     * Taqrizchi sahifasini ochish mumkinmi: roli bor yoki avval taqriz qilgan.
     */
    public function isReviewer(User $user): bool
    {
        return $user->hasRole(RoleName::Reviewer) || $user->reviews()->exists();
    }

    /**
     * "Taqrizchi qo'shish" uchun nomzodlar: hali taqrizchi bo'lmagan faol foydalanuvchilar.
     *
     * @return array<int, array{id: int, name: string, email: string, organization: string|null, avatarUrl: string|null}>
     */
    public function candidates(string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return User::query()
            ->active()
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', RoleName::Reviewer->value))
            ->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('authorProfile', fn (Builder $p) => $p->where('organization', 'like', $like)))
            ->with('authorProfile')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'organization' => $u->authorProfile?->organization,
                'avatarUrl' => $u->avatarUrl(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Review>
     */
    private function completedReviews(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Review::query()
            ->whereIn('reviewer_id', $ids)
            ->where('status', ReviewStatus::Completed->value)
            ->get(['id', 'reviewer_id', 'created_at', 'completed_at', 'due_at'])
            ->toBase();
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    public static function averageDays(Collection $reviews): ?float
    {
        $hours = $reviews
            ->filter(fn (Review $r): bool => $r->created_at !== null && $r->completed_at !== null)
            ->map(fn (Review $r): float => max(0.0, (float) $r->created_at?->diffInHours($r->completed_at)));

        return $hours->isEmpty() ? null : round((float) $hours->avg() / 24, 1);
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    public static function onTime(Collection $reviews): ?int
    {
        if ($reviews->isEmpty()) {
            return null;
        }

        $onTime = $reviews->filter(fn (Review $r): bool => $r->due_at === null
            || ($r->completed_at !== null && $r->completed_at->lessThanOrEqualTo($r->due_at)));

        return (int) round($onTime->count() / $reviews->count() * 100);
    }
}

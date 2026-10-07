<?php

namespace App\Services\People;

use App\Enums\ArticleStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin → Mualliflar: muallif rolidagi foydalanuvchilar, ularning maqolalari va to'lovlari.
 * Muallif maqolalari — o'zi yuborganlari va hammuallif sifatida qo'shilganlari (article_authors.user_id).
 * Qoralamalar tahririyatga ko'rinmaydi, shuning uchun hisoblanmaydi.
 */
class AuthorDirectory
{
    public const PER_PAGE = 15;

    public const SORTS = ['latest', 'name', 'articles', 'published'];

    /**
     * @param  array<string, mixed>  $input
     * @return array{search: string, subject: int|null, sort: string}
     */
    public static function filters(array $input): array
    {
        $search = $input['search'] ?? '';
        $subject = $input['subject'] ?? null;
        $sort = $input['sort'] ?? 'latest';

        return [
            'search' => is_string($search) ? mb_substr(trim($search), 0, 100) : '',
            'subject' => is_numeric($subject) && (int) $subject > 0 ? (int) $subject : null,
            'sort' => is_string($sort) && in_array($sort, self::SORTS, true) ? $sort : 'latest',
        ];
    }

    /**
     * @param  array{search: string, subject: int|null, sort: string}  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array{currentPage: int, lastPage: int, total: int, from: int|null, to: int|null}}
     */
    public function list(array $filters, int $page = 1, bool $withPayments = false): array
    {
        $query = User::query()
            ->role(RoleName::Author->value)
            ->with(['authorProfile', 'subjects'])
            ->withCount([
                'submittedArticles as articles_count' => fn ($q) => $q->where('status', '!=', ArticleStatus::Draft->value),
                'submittedArticles as published_count' => fn ($q) => $q->where('status', ArticleStatus::Published->value),
            ]);

        if ($withPayments) {
            $query->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', PaymentStatus::Paid->value)], 'amount');
        }

        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';

            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('authorProfile', fn (Builder $p) => $p
                    ->where('organization', 'like', $like)
                    ->orWhere('orcid', 'like', $like)
                    ->orWhere('last_name', 'like', $like)));
        }

        if ($filters['subject'] !== null) {
            $subject = $filters['subject'];

            $query->where(fn (Builder $q) => $q
                ->whereHas('subjects', fn (Builder $s) => $s->whereKey($subject))
                ->orWhereHas('submittedArticles', fn (Builder $a) => $a
                    ->where('subject_id', $subject)
                    ->where('status', '!=', ArticleStatus::Draft->value)));
        }

        $query = match ($filters['sort']) {
            'name' => $query->orderBy('name'),
            'articles' => $query->orderByDesc('articles_count')->orderBy('name'),
            'published' => $query->orderByDesc('published_count')->orderBy('name'),
            default => $query->latest()->latest('id'),
        };

        $paginator = $query->paginate(self::PER_PAGE, page: $page);

        return [
            'data' => array_map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatarUrl' => $user->avatarUrl(),
                'organization' => $user->authorProfile?->organization,
                'degree' => $user->authorProfile?->academic_degree,
                'orcid' => $user->authorProfile?->orcid,
                'subjects' => array_column(PersonPresenter::subjects($user), 'name'),
                'articles' => (int) $user->getAttribute('articles_count'),
                'published' => (int) $user->getAttribute('published_count'),
                'paid' => $withPayments ? (float) $user->getAttribute('paid_sum') : null,
                'isBlocked' => $user->is_blocked,
                'createdAt' => $user->created_at?->toIso8601String(),
                'url' => route('admin.authors.show', $user->id),
            ], $paginator->items()),
            'meta' => self::meta($paginator),
        ];
    }

    /**
     * Yuqoridagi kartalar.
     *
     * @return array{total: int, active: int, published: int, newThisMonth: int, orcid: int}
     */
    public function counts(): array
    {
        $authors = fn (): Builder => User::query()->role(RoleName::Author->value);

        return [
            'total' => $authors()->count(),
            'active' => $authors()->whereHas('submittedArticles', fn (Builder $q) => $q->where('status', '!=', ArticleStatus::Draft->value))->count(),
            'published' => $authors()->whereHas('submittedArticles', fn (Builder $q) => $q->where('status', ArticleStatus::Published->value))->count(),
            'newThisMonth' => $authors()->where('created_at', '>=', now()->startOfMonth())->count(),
            'orcid' => $authors()->whereHas('authorProfile', fn (Builder $q) => $q->whereNotNull('orcid'))->count(),
        ];
    }

    /**
     * Muallif sahifasi: profil, statistika, maqolalar va (ruxsat bo'lsa) to'lovlar.
     *
     * @return array<string, mixed>
     */
    public function detail(User $user, bool $withPayments): array
    {
        $user->loadMissing(['authorProfile', 'subjects']);

        $articles = Article::query()
            ->ownedBy($user)
            ->where('status', '!=', ArticleStatus::Draft->value)
            ->with('subject')
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        $rows = $articles->map(fn (Article $a): array => [
            ...PersonPresenter::article($a),
            'isSubmitter' => $a->submitter_id === $user->id,
        ]);

        $groups = ['new' => 0, 'reviewing' => 0, 'revision' => 0, 'accepted' => 0, 'published' => 0, 'closed' => 0];

        foreach ($articles as $article) {
            $group = $article->status->group();
            $key = in_array($group, ['rejected', 'withdrawn'], true) ? 'closed' : $group;
            $groups[$key] = ($groups[$key] ?? 0) + 1;
        }

        $payments = null;

        if ($withPayments) {
            $payments = $user->payments()
                ->with('article:id,uuid,title')
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (Payment $p): array => [
                    'uuid' => $p->uuid,
                    'amount' => (float) $p->amount,
                    'currency' => $p->currency,
                    'status' => $p->status->value,
                    'statusLabel' => $p->status->label(),
                    'purpose' => $p->purpose->label(),
                    'provider' => $p->provider->label(),
                    'article' => $p->article?->title,
                    'receipt' => $p->receipt_number,
                    'date' => ($p->paid_at ?? $p->created_at)?->toIso8601String(),
                ])
                ->all();
        }

        return [
            'profile' => PersonPresenter::profile($user),
            'subjects' => PersonPresenter::subjects($user),
            'stats' => [
                'articles' => $rows->count(),
                'submitted' => $rows->where('isSubmitter', true)->count(),
                'coauthored' => $rows->where('isSubmitter', false)->count(),
                'groups' => $groups,
                'views' => (int) $articles->sum('views_count'),
                'downloads' => (int) $articles->sum('downloads_count'),
                'drafts' => $user->submittedArticles()->where('status', ArticleStatus::Draft->value)->count(),
                'paid' => $withPayments
                    ? (float) $user->payments()->where('status', PaymentStatus::Paid->value)->sum('amount')
                    : null,
            ],
            'articles' => $rows->values()->all(),
            'payments' => $payments,
        ];
    }

    /**
     * Muallif sahifasini ochish mumkinmi: muallif roli yoki yuborilgan maqolasi bor.
     */
    public function isAuthor(User $user): bool
    {
        return $user->hasRole(RoleName::Author) || $user->submittedArticles()->exists();
    }

    /**
     * @param  LengthAwarePaginator<int, *>  $paginator
     * @return array{currentPage: int, lastPage: int, total: int, from: int|null, to: int|null}
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}

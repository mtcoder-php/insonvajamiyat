<?php

namespace App\Services\Cabinet;

use App\Enums\ArticleStatus;
use App\Http\Resources\Cabinet\AuthorArticleResource;
use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\User;
use App\Services\Articles\ArticleTimeline;
use Illuminate\Database\Eloquent\Builder;

/**
 * Muallif kabineti bosh sahifasi (dizayn: "Muallif kabineti"):
 * statistika kartalari, so'nggi maqolalar, jarayon (timeline), xabarlar, 6 oylik grafik.
 */
class AuthorDashboardService
{
    /** Ko'rib chiqilayotgan (tahririyat qo'lidagi) holatlar */
    public const REVIEWING = [
        ArticleStatus::Submitted, ArticleStatus::AwaitingPayment, ArticleStatus::UnderReview,
        ArticleStatus::InReview, ArticleStatus::Resubmitted,
    ];

    public function __construct(private readonly ArticleTimeline $timeline) {}

    /**
     * @return Builder<Article>
     */
    public function articles(User $user): Builder
    {
        return Article::query()->ownedBy($user);
    }

    /**
     * @return array<int, array{key: string, value: int, delta: int|null, hint: string}>
     */
    public function cards(User $user): array
    {
        $count = fn (array $statuses): int => $this->articles($user)
            ->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, $statuses))
            ->count();
        $since = fn (string $column, int $days): int => $this->articles($user)
            ->where($column, '>=', now()->subDays($days))
            ->count();

        return [
            [
                'key' => 'total',
                'value' => $this->articles($user)->where('status', '!=', ArticleStatus::Draft->value)->count(),
                'delta' => $since('submitted_at', 90),
                'hint' => "so'nggi 3 oyda",
            ],
            [
                'key' => 'reviewing',
                'value' => $count(self::REVIEWING),
                'delta' => $this->articles($user)
                    ->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, self::REVIEWING))
                    ->where('submitted_at', '>=', now()->subDays(30))
                    ->count(),
                'hint' => 'yangi',
            ],
            [
                'key' => 'revision',
                'value' => $count([ArticleStatus::RevisionRequired]),
                'delta' => null,
                'hint' => 'javobingiz kutilmoqda',
            ],
            [
                'key' => 'accepted',
                'value' => $count([ArticleStatus::Accepted, ArticleStatus::InProduction]),
                'delta' => $this->articles($user)
                    ->whereIn('status', [ArticleStatus::Accepted->value, ArticleStatus::InProduction->value])
                    ->where('accepted_at', '>=', now()->subDays(90))
                    ->count(),
                'hint' => "so'nggi 3 oyda",
            ],
            [
                'key' => 'published',
                'value' => $count([ArticleStatus::Published]),
                'delta' => null,
                'hint' => 'jami nashr',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(User $user, int $limit = 5): array
    {
        $articles = $this->articles($user)
            ->with(['subject', 'issues'])
            ->latest('updated_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        return AuthorArticleResource::collection($articles)->resolve();
    }

    /**
     * Jarayonni kuzatish uchun maqola: hali yakunlanmaganlar ichida avval
     * muallifdan javob kutilayotgani (tuzatish), so'ng oxirgi yangilangani.
     *
     * @return array{article: array<string, mixed>, steps: array<int, array<string, mixed>>}|null
     */
    public function focus(User $user): ?array
    {
        $article = $this->articles($user)
            ->whereNotIn('status', [
                ArticleStatus::Draft->value, ArticleStatus::Published->value,
                ArticleStatus::Rejected->value, ArticleStatus::Withdrawn->value,
            ])
            ->with(['statusHistories', 'subject', 'issues'])
            ->orderByRaw('case when status = ? then 0 else 1 end', [ArticleStatus::RevisionRequired->value])
            ->latest('updated_at')
            ->latest('id')
            ->first();

        if ($article === null) {
            return null;
        }

        return [
            'article' => AuthorArticleResource::make($article)->resolve(),
            'steps' => $this->timeline->for($article),
        ];
    }

    /**
     * Tahririyatdan xabarlar: muallifga ko'rinadigan, izohli holat o'zgarishlari.
     *
     * @return array<int, array{id: int, sender: string, message: string, title: string, url: string, createdAt: string}>
     */
    public function messages(User $user, int $limit = 3): array
    {
        return ArticleStatusHistory::query()
            ->whereIn('article_id', $this->articles($user)->select('id'))
            ->where('is_visible_to_author', true)
            ->whereNotNull('comment')
            ->with('article')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (ArticleStatusHistory $history): array => [
                'id' => $history->id,
                'sender' => $history->to_status === ArticleStatus::InReview ? 'Taqrizchi' : 'Tahririyat',
                'message' => (string) $history->comment,
                'title' => $history->article->title,
                'url' => route('cabinet.articles.show', $history->article->uuid),
                'createdAt' => $history->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Oxirgi 6 oy: yuborilgan, jarayondagi (hali yakunlanmagan) va nashr etilgan maqolalar.
     *
     * @return array{months: array<int, string>, submitted: array<int, int>, inProgress: array<int, int>, published: array<int, int>}
     */
    public function chart(User $user): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $months = [];
        $submitted = $inProgress = $published = array_fill(0, 6, 0);

        for ($i = 0; $i < 6; $i++) {
            $months[] = $start->copy()->addMonths($i)->format('Y-m');
        }

        $rows = $this->articles($user)
            ->where(fn (Builder $q) => $q->where('submitted_at', '>=', $start)->orWhere('published_at', '>=', $start))
            ->get(['id', 'status', 'submitted_at', 'published_at']);

        foreach ($rows as $article) {
            $submittedIndex = $article->submitted_at ? array_search($article->submitted_at->format('Y-m'), $months, true) : false;
            $publishedIndex = $article->published_at ? array_search($article->published_at->format('Y-m'), $months, true) : false;

            if ($submittedIndex !== false) {
                $submitted[(int) $submittedIndex]++;

                if (! $article->status->isFinal()) {
                    $inProgress[(int) $submittedIndex]++;
                }
            }

            if ($publishedIndex !== false && $article->status === ArticleStatus::Published) {
                $published[(int) $publishedIndex]++;
            }
        }

        return [
            'months' => $months,
            'submitted' => $submitted,
            'inProgress' => $inProgress,
            'published' => $published,
        ];
    }
}

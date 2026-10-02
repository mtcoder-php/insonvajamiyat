<?php

namespace App\Services\Reports;

use App\Enums\AiRequestStatus;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\PaymentProvider;
use App\Enums\ReviewStatus;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Subject;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Editorial\EditorialWorkspace;
use App\Support\ReportPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * "Statistika va hisobotlar" sahifasi (super admin analistic page.png).
 *
 * Har bir blok alohida metod — controller'da Inertia closure sifatida beriladi.
 * Hamma ko'rsatkichlar tanlangan davr (ReportPeriod) va ixtiyoriy fan yo'nalishi
 * bo'yicha hisoblanadi; o'zgarish (%) — xuddi shu uzunlikdagi oldingi davrga nisbatan.
 * SQL MySQL va SQLite'da bir xil ishlaydi (sana bo'laklari PHP'da yig'iladi).
 */
class AnalyticsService
{
    /** Donut va ro'yxatlarda ko'rsatiladigan eng katta guruhlar soni, qolgani "Boshqalar" */
    public const TOP = 5;

    /** @var array<string, string> */
    public const COUNTRIES = [
        'UZ' => "O'zbekiston", 'RU' => 'Rossiya', 'KZ' => "Qozog'iston", 'KG' => "Qirg'iziston",
        'TJ' => 'Tojikiston', 'TM' => 'Turkmaniston', 'AZ' => 'Ozarbayjon', 'TR' => 'Turkiya',
        'BY' => 'Belarus', 'UA' => 'Ukraina', 'US' => 'AQSh', 'GB' => 'Buyuk Britaniya',
        'DE' => 'Germaniya', 'FR' => 'Fransiya', 'IT' => 'Italiya', 'PL' => 'Polsha',
        'KR' => 'Janubiy Koreya', 'JP' => 'Yaponiya', 'CN' => 'Xitoy', 'IN' => 'Hindiston',
        'PK' => 'Pokiston', 'AF' => "Afg'oniston", 'IR' => 'Eron', 'MY' => 'Malayziya',
    ];

    /** Tashkilot turi — nomidagi kalit so'zlar bo'yicha (o'zbek, rus, ingliz) */
    private const ORGANIZATION_TYPES = [
        'university' => ['universitet', 'university', 'университет', 'institut', 'institute', 'институт', 'akademiya', 'academy', 'академия', 'oliygoh'],
        'research' => ['ilmiy', 'tadqiqot', 'markaz', 'research', 'center', 'centre', 'научн', 'центр', 'laboratoriya', 'laboratory'],
        'college' => ['kollej', 'college', 'колледж', 'litsey', 'lyceum', 'лицей', 'texnikum', 'техникум', 'maktab', 'school', 'школа'],
    ];

    /** Jadval tablari → maqola holatlari */
    public const TABLE_TABS = [
        'all' => [],
        'new' => [ArticleStatus::Submitted, ArticleStatus::AwaitingPayment],
        'reviewing' => [ArticleStatus::UnderReview, ArticleStatus::InReview, ArticleStatus::Resubmitted, ArticleStatus::RevisionRequired],
        'accepted' => [ArticleStatus::Accepted, ArticleStatus::InProduction],
        'published' => [ArticleStatus::Published],
        'rejected' => [ArticleStatus::Rejected],
    ];

    /**
     * Yuqoridagi 6 ta ko'rsatkich.
     *
     * @return array<int, array{key: string, value: int|float|null, previous: int|float|null, trend: int|null, better: string}>
     */
    public function kpis(ReportPeriod $period, ?int $subjectId = null): array
    {
        $previous = $period->previous();

        $card = function (string $key, callable $metric, string $better = 'up') use ($period, $previous): array {
            /** @var int|float|null $current */
            $current = $metric($period);
            /** @var int|float|null $before */
            $before = $metric($previous);

            return [
                'key' => $key,
                'value' => $current,
                'previous' => $before,
                'trend' => self::percentChange($current, $before),
                'better' => $better,
            ];
        };

        return [
            $card('submitted', fn (ReportPeriod $p): int => $this->countBetween('submitted_at', $p, $subjectId)),
            $card('accepted', fn (ReportPeriod $p): int => $this->countBetween('accepted_at', $p, $subjectId)),
            $card('rejected', fn (ReportPeriod $p): int => $this->countBetween('rejected_at', $p, $subjectId), 'down'),
            $card('review_days', fn (ReportPeriod $p): ?float => $this->averageReviewDays($p, $subjectId), 'down'),
            $card('authors', fn (ReportPeriod $p): int => $this->activeAuthors($p, $subjectId)),
            $card('views', fn (ReportPeriod $p): int => $this->views($p, $subjectId)),
        ];
    }

    /**
     * "Maqolalar dinamikasi": yuborilgan / qabul qilingan / rad etilgan.
     *
     * @return array{labels: array<int, string>, submitted: array<int, int|float>, accepted: array<int, int|float>, rejected: array<int, int|float>}
     */
    public function dynamics(ReportPeriod $period, ?int $subjectId = null): array
    {
        $series = fn (string $column): array => $period->series(
            $this->articles($subjectId)
                ->whereBetween($column, $period->range())
                ->pluck($column)
                ->map(fn (mixed $date): array => [(string) $date, 1]),
        );

        return [
            'labels' => array_column($period->buckets(), 'label'),
            'submitted' => $series('submitted_at'),
            'accepted' => $series('accepted_at'),
            'rejected' => $series('rejected_at'),
        ];
    }

    /**
     * "Maqolalar yo'nalishlari": davrda yuborilgan maqolalar fan yo'nalishlari bo'yicha.
     *
     * @return array{total: int, items: array<int, array{key: string, label: string, value: int}>}
     */
    public function subjects(ReportPeriod $period, ?int $subjectId = null): array
    {
        $counts = $this->articles($subjectId)
            ->whereBetween('submitted_at', $period->range())
            ->selectRaw('subject_id, count(*) as aggregate')
            ->groupBy('subject_id')
            ->pluck('aggregate', 'subject_id');

        $names = Subject::query()->whereIn('id', $counts->keys()->filter()->all())->get()
            ->mapWithKeys(fn (Subject $s): array => [$s->id => ['slug' => $s->slug, 'name' => (string) $s->name]]);

        $items = [];

        foreach ($counts as $id => $value) {
            $subject = $names->get($id);
            $items[] = [
                'key' => $subject['slug'] ?? 'none',
                'label' => $subject['name'] ?? "Yo'nalishsiz",
                'value' => (int) $value,
            ];
        }

        return $this->topWithOthers($items);
    }

    /**
     * "Mamlakatlar bo'yicha mualliflar" — noyob mualliflar (familiya + ism) soni.
     *
     * @return array{total: int, items: array<int, array{key: string, label: string, value: int}>}
     */
    public function countries(ReportPeriod $period, ?int $subjectId = null): array
    {
        $rows = $this->authorsQuery($period, $subjectId)
            ->select(['article_authors.country', 'article_authors.last_name', 'article_authors.first_name'])
            ->distinct()
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $code = is_string($row->country) && $row->country !== '' ? strtoupper($row->country) : '';
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }

        arsort($counts);

        $items = [];

        foreach ($counts as $code => $value) {
            $code = (string) $code;
            $items[] = [
                'key' => $code === '' ? 'none' : $code,
                'label' => $code === '' ? "Ko'rsatilmagan" : (self::COUNTRIES[$code] ?? $code),
                'value' => $value,
            ];
        }

        return $this->topWithOthers($items, 6);
    }

    /**
     * "Tashkilotlar / muassasalar" — noyob tashkilotlar turlari bo'yicha va eng faollari.
     *
     * @return array{total: int, items: array<int, array{key: string, label: string, value: int}>, top: array<int, array{name: string, authors: int}>}
     */
    public function organizations(ReportPeriod $period, ?int $subjectId = null): array
    {
        $rows = $this->authorsQuery($period, $subjectId)
            ->whereNotNull('article_authors.organization')
            ->select(['article_authors.organization', 'article_authors.last_name', 'article_authors.first_name'])
            ->distinct()
            ->get();

        /** @var array<string, array{name: string, authors: int}> $organizations */
        $organizations = [];

        foreach ($rows as $row) {
            $name = trim((string) $row->organization);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            $organizations[$key] ??= ['name' => $name, 'authors' => 0];
            $organizations[$key]['authors']++;
        }

        $labels = ['university' => 'Universitet va institutlar', 'research' => 'Ilmiy markazlar', 'college' => 'Kollej va maktablar', 'other' => 'Boshqalar'];
        $byType = array_fill_keys(array_keys($labels), 0);

        foreach (array_keys($organizations) as $key) {
            $byType[self::organizationType($key)]++;
        }

        uasort($organizations, fn (array $a, array $b): int => $b['authors'] <=> $a['authors']);

        return [
            'total' => count($organizations),
            'items' => array_values(array_filter(array_map(
                fn (string $type): array => ['key' => $type, 'label' => $labels[$type], 'value' => $byType[$type]],
                array_keys($labels),
            ), fn (array $item): bool => $item['value'] > 0)),
            'top' => array_slice(array_values($organizations), 0, self::TOP),
        ];
    }

    public static function organizationType(string $name): string
    {
        $name = mb_strtolower($name);

        foreach (self::ORGANIZATION_TYPES as $type => $needles) {
            if (Str::contains($name, $needles)) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * "Daromadlar": muvaffaqiyatli to'lovlar (so'm) to'lov tizimlari bo'yicha.
     *
     * @return array{labels: array<int, string>, click: array<int, int|float>, payme: array<int, int|float>, manual: array<int, int|float>, total: int, previous: int, trend: int|null, count: int}
     */
    public function revenue(ReportPeriod $period, ?int $subjectId = null): array
    {
        $payments = $this->payments($period, $subjectId)->get(['provider', 'amount', 'paid_at']);

        $series = fn (PaymentProvider $provider): array => $period->series(
            $payments->filter(fn (Payment $p): bool => $p->provider === $provider)
                ->map(fn (Payment $p): array => [$p->paid_at ?? now(), (int) round((float) $p->amount)]),
        );

        $total = (int) round((float) $payments->sum(fn (Payment $p): float => (float) $p->amount));
        $previous = (int) round((float) $this->payments($period->previous(), $subjectId)->sum('amount'));

        return [
            'labels' => array_column($period->buckets(), 'label'),
            'click' => $series(PaymentProvider::Click),
            'payme' => $series(PaymentProvider::Payme),
            'manual' => $series(PaymentProvider::Manual),
            'total' => $total,
            'previous' => $previous,
            'trend' => self::percentChange($total, $previous),
            'count' => $payments->count(),
        ];
    }

    /**
     * "AI statistika": so'rovlar, muvaffaqiyatli, xatolar, xarajat (USD) va tokenlar.
     *
     * @return array{labels: array<int, string>, series: array<int, int|float>, metrics: array<int, array{key: string, value: int|float, trend: int|null}>, tokens: int}
     */
    public function ai(ReportPeriod $period): array
    {
        $current = $this->aiTotals($period);
        $previous = $this->aiTotals($period->previous());

        $dates = AiRequest::query()->whereBetween('created_at', $period->range())->pluck('created_at');

        return [
            'labels' => array_column($period->buckets(), 'label'),
            'series' => $period->series($dates->map(fn (mixed $date): array => [(string) $date, 1])),
            'metrics' => array_map(fn (string $key): array => [
                'key' => $key,
                'value' => $current[$key],
                'trend' => self::percentChange($current[$key], $previous[$key]),
            ], ['total', 'completed', 'failed', 'cost']),
            'tokens' => (int) $current['tokens'],
        ];
    }

    /**
     * "Tezkor ma'lumotlar" — bugun (kechagiga nisbatan) va hozirgi navbatlar.
     *
     * @return array<int, array{key: string, value: int, trend: int|null, hint: string|null}>
     */
    public function quick(): array
    {
        $today = ReportPeriod::today();
        $yesterday = $today->previous();
        $pending = [ReviewStatus::Invited->value, ReviewStatus::Accepted->value];

        $submitted = fn (ReportPeriod $p): int => $this->countBetween('submitted_at', $p, null);
        $paid = fn (ReportPeriod $p): int => (int) round((float) $this->payments($p, null)->sum('amount'));
        $ai = fn (ReportPeriod $p): int => AiRequest::query()->whereBetween('created_at', $p->range())->count();

        $overdue = Review::query()->whereIn('status', $pending)->where('due_at', '<', now())->count();

        return [
            ['key' => 'submitted', 'value' => $submitted($today), 'trend' => self::percentChange($submitted($today), $submitted($yesterday)), 'hint' => null],
            ['key' => 'reviews', 'value' => Review::query()->whereIn('status', $pending)->count(), 'trend' => null, 'hint' => $overdue > 0 ? "{$overdue} tasi muddati o'tgan" : null],
            ['key' => 'payments', 'value' => $paid($today), 'trend' => self::percentChange($paid($today), $paid($yesterday)), 'hint' => null],
            ['key' => 'ai', 'value' => $ai($today), 'trend' => self::percentChange($ai($today), $ai($yesterday)), 'hint' => null],
        ];
    }

    /**
     * "Eng faol mualliflar" — davrda yuborilgan maqolalari soni bo'yicha.
     *
     * @return array<int, array{name: string, organization: string|null, articles: int}>
     */
    public function topAuthors(ReportPeriod $period, ?int $subjectId = null, int $limit = self::TOP): array
    {
        return $this->authorsQuery($period, $subjectId)
            ->selectRaw('article_authors.last_name, article_authors.first_name, max(article_authors.organization) as organization, count(distinct article_authors.article_id) as total')
            ->groupBy('article_authors.last_name', 'article_authors.first_name')
            ->orderByDesc('total')
            ->orderBy('article_authors.last_name')
            ->limit($limit)
            ->get()
            ->map(fn (stdClass $row): array => [
                'name' => trim(((string) ($row->last_name ?? '')).' '.mb_substr((string) ($row->first_name ?? ''), 0, 1).'.'),
                'organization' => isset($row->organization) && is_string($row->organization) ? $row->organization : null,
                'articles' => (int) ($row->total ?? 0),
            ])
            ->all();
    }

    /**
     * "Tizim statistikasi" va ro'yxatdan o'tishlar dinamikasi (sparkline).
     *
     * @return array{items: array<int, array{key: string, value: int, trend: int|null}>, registrations: array<int, int|float>}
     */
    public function system(ReportPeriod $period): array
    {
        $newUsers = fn (ReportPeriod $p): int => User::query()->whereBetween('created_at', $p->range())->count();

        $tokens = (int) AiRequest::query()
            ->whereBetween('created_at', $period->range())
            ->sum(DB::raw('input_tokens + output_tokens'));

        return [
            'items' => [
                ['key' => 'users', 'value' => User::query()->count(), 'trend' => self::percentChange($newUsers($period), $newUsers($period->previous()))],
                ['key' => 'new_users', 'value' => $newUsers($period), 'trend' => null],
                ['key' => 'online', 'value' => User::query()->where('last_login_at', '>=', now()->subDay())->count(), 'trend' => null],
                ['key' => 'issues', 'value' => JournalIssue::query()->where('status', IssueStatus::Published->value)->count(), 'trend' => null],
                ['key' => 'archive', 'value' => Article::query()->where('status', ArticleStatus::Published->value)->count(), 'trend' => null],
                ['key' => 'tokens', 'value' => $tokens, 'trend' => null],
            ],
            'registrations' => $period->series(
                User::query()->whereBetween('created_at', $period->range())->pluck('created_at')
                    ->map(fn (mixed $date): array => [(string) $date, 1]),
            ),
        ];
    }

    /**
     * Pastdagi "Maqolalar" jadvali: davrda yuborilganlar, tab va qidiruv bo'yicha.
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function articlesTable(ReportPeriod $period, ?int $subjectId, string $tab, ?string $search, int $perPage = 8): LengthAwarePaginator
    {
        $statuses = self::TABLE_TABS[$tab] ?? [];

        return $this->articles($subjectId)
            ->whereBetween('submitted_at', $period->range())
            ->when($statuses !== [], fn (Builder $q) => $q->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, $statuses)))
            ->when($search !== null && $search !== '', function (Builder $q) use ($search): void {
                $term = mb_strtolower((string) $search);
                $id = preg_match('/(\d{1,7})\s*$/', $term, $m) === 1 ? (int) $m[1] : null;

                $q->where(function (Builder $w) use ($term, $id): void {
                    $w->where('search_text', 'like', '%'.$term.'%')
                        ->orWhereHas('authors', fn (Builder $a) => $a->where('last_name', 'like', '%'.$term.'%'));

                    if ($id !== null) {
                        $w->orWhere('id', $id);
                    }
                });
            })
            ->with(['authors', 'subject'])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate($perPage, pageName: 'page')
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public static function tableRow(Article $article): array
    {
        $author = $article->authors->sortBy('sort_order')->first();

        return [
            'id' => $article->id,
            'code' => EditorialWorkspace::code($article),
            'title' => $article->title,
            'author' => $author?->short_name,
            'authorsCount' => $article->authors->count(),
            'subject' => $article->subject?->name,
            'status' => $article->status->value,
            'statusGroup' => DashboardService::groupOf($article->status),
            'statusLabel' => $article->status->label(),
            'submittedAt' => $article->submitted_at?->toIso8601String(),
            'url' => route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function subjectOptions(): array
    {
        return Subject::query()->orderBy('sort_order')->get()
            ->map(fn (Subject $s): array => ['value' => $s->slug, 'label' => (string) $s->name])
            ->all();
    }

    public static function percentChange(int|float|null $current, int|float|null $previous): ?int
    {
        if ($current === null || $previous === null || (float) $previous === 0.0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * @return Builder<Article>
     */
    public function articles(?int $subjectId): Builder
    {
        return Article::query()->when($subjectId !== null, fn (Builder $q) => $q->where('subject_id', $subjectId));
    }

    /**
     * Davrda yuborilgan maqolalarning mualliflari (DB query — distinct va guruhlash uchun).
     */
    public function authorsQuery(ReportPeriod $period, ?int $subjectId): QueryBuilder
    {
        return DB::table('article_authors')
            ->join('articles', 'articles.id', '=', 'article_authors.article_id')
            ->whereNull('articles.deleted_at')
            ->whereBetween('articles.submitted_at', $period->range())
            ->when($subjectId !== null, fn (QueryBuilder $q) => $q->where('articles.subject_id', $subjectId));
    }

    /**
     * @return Builder<Payment>
     */
    public function payments(ReportPeriod $period, ?int $subjectId): Builder
    {
        return Payment::query()
            ->paid()
            ->whereBetween('paid_at', $period->range())
            ->when($subjectId !== null, fn (Builder $q) => $q->whereHas('article', fn ($a) => $a->where('subject_id', $subjectId)));
    }

    private function countBetween(string $column, ReportPeriod $period, ?int $subjectId): int
    {
        return $this->articles($subjectId)->whereBetween($column, $period->range())->count();
    }

    /** Taqrizchi taklif qilingandan xulosa topshirguncha o'rtacha kun (1 xona aniqlikda) */
    private function averageReviewDays(ReportPeriod $period, ?int $subjectId): ?float
    {
        $reviews = Review::query()
            ->where('status', ReviewStatus::Completed->value)
            ->whereBetween('completed_at', $period->range())
            ->when($subjectId !== null, fn (Builder $q) => $q->whereHas('article', fn ($a) => $a->where('subject_id', $subjectId)))
            ->get(['created_at', 'completed_at']);

        if ($reviews->isEmpty()) {
            return null;
        }

        $hours = $reviews->map(fn (Review $r): float => $r->created_at !== null && $r->completed_at !== null
            ? max(0.0, (float) $r->created_at->diffInHours($r->completed_at))
            : 0.0);

        return round((float) $hours->avg() / 24, 1);
    }

    private function activeAuthors(ReportPeriod $period, ?int $subjectId): int
    {
        return $this->articles($subjectId)
            ->whereBetween('submitted_at', $period->range())
            ->distinct()
            ->count('submitter_id');
    }

    private function views(ReportPeriod $period, ?int $subjectId): int
    {
        return (int) DB::table('article_daily_stats')
            ->whereBetween('date', [$period->from->toDateString(), $period->to->toDateString()])
            ->when($subjectId !== null, fn (QueryBuilder $q) => $q->whereIn(
                'article_id',
                Article::query()->where('subject_id', $subjectId)->select('id'),
            ))
            ->sum('views');
    }

    /**
     * @return array{total: int, completed: int, failed: int, cost: float, tokens: int}
     */
    private function aiTotals(ReportPeriod $period): array
    {
        $row = AiRequest::query()
            ->whereBetween('created_at', $period->range())
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [AiRequestStatus::Completed->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as failed', [AiRequestStatus::Failed->value])
            ->selectRaw('sum(cost_usd) as cost')
            ->selectRaw('sum(input_tokens + output_tokens) as tokens')
            ->toBase()
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'cost' => round((float) ($row->cost ?? 0), 2),
            'tokens' => (int) ($row->tokens ?? 0),
        ];
    }

    /**
     * Eng kattalari + "Boshqalar".
     *
     * @param  array<int, array{key: string, label: string, value: int}>  $items
     * @return array{total: int, items: array<int, array{key: string, label: string, value: int}>}
     */
    private function topWithOthers(array $items, int $top = self::TOP): array
    {
        usort($items, fn (array $a, array $b): int => $b['value'] <=> $a['value']);

        $head = array_slice($items, 0, $top);
        $rest = array_sum(array_column(array_slice($items, $top), 'value'));

        if ($rest > 0) {
            $head[] = ['key' => 'other', 'label' => 'Boshqalar', 'value' => $rest];
        }

        return ['total' => array_sum(array_column($items, 'value')), 'items' => $head];
    }
}

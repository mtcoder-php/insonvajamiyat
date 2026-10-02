<?php

namespace App\Services\Admin;

use App\Enums\AiRequestType;
use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\RoleName;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notifications\NotificationCenter;
use App\Services\Payments\OnlinePaymentService;
use App\Support\MediaUrl;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Admin panel bosh sahifasi (super admin dashboard.png) ma'lumotlari.
 *
 * Har bir blok alohida metod — Inertia'ga closure sifatida beriladi,
 * partial reload'da faqat kerakli blok hisoblanadi.
 */
class DashboardService
{
    /**
     * Maqolalar holati guruhlari (statistika kartalari va "Maqolalar holati" diagrammasi).
     *
     * @var array<string, array<int, ArticleStatus>>
     */
    private const STATUS_GROUPS = [
        'new' => [ArticleStatus::Submitted, ArticleStatus::AwaitingPayment],
        'reviewing' => [ArticleStatus::UnderReview, ArticleStatus::InReview, ArticleStatus::Resubmitted],
        'revision' => [ArticleStatus::RevisionRequired],
        'accepted' => [ArticleStatus::Accepted, ArticleStatus::InProduction],
        'published' => [ArticleStatus::Published],
    ];

    /**
     * Yuqoridagi 5 ta statistika kartasi va o'tgan davrga nisbatan o'zgarish (%).
     *
     * @return array<int, array{key: string, value: int, trend: int|null, period: string}>
     */
    public function statusCards(): array
    {
        $counts = $this->statusCounts();
        $now = now();

        return [
            [
                'key' => 'total',
                'value' => array_sum($counts),
                'trend' => $this->monthTrend('submitted_at', $now),
                'period' => 'month',
            ],
            [
                'key' => 'reviewing',
                'value' => $counts['reviewing'],
                'trend' => $this->historyTrend(self::STATUS_GROUPS['reviewing'], $now),
                'period' => 'week',
            ],
            [
                'key' => 'revision',
                'value' => $counts['revision'],
                'trend' => $this->historyTrend(self::STATUS_GROUPS['revision'], $now),
                'period' => 'week',
            ],
            [
                'key' => 'accepted',
                'value' => $counts['accepted'],
                'trend' => $this->monthTrend('accepted_at', $now),
                'period' => 'month',
            ],
            [
                'key' => 'published',
                'value' => $counts['published'],
                'trend' => $this->monthTrend('published_at', $now),
                'period' => 'month',
            ],
        ];
    }

    /**
     * "Maqolalar holati" — guruhlar bo'yicha soni.
     *
     * @return array{total: int, groups: array<int, array{key: string, value: int}>}
     */
    public function statusBreakdown(): array
    {
        $counts = $this->statusCounts();

        return [
            'total' => array_sum($counts),
            'groups' => array_map(
                fn (string $key): array => ['key' => $key, 'value' => $counts[$key]],
                array_keys($counts),
            ),
        ];
    }

    /**
     * "Maqolalar dinamikasi" — joriy yil, oyma-oy: yuborilgan / qabul qilingan / nashr etilgan.
     *
     * @return array{year: int, submitted: array<int, int>, accepted: array<int, int>, published: array<int, int>}
     */
    public function monthlyDynamics(?int $year = null): array
    {
        $year ??= (int) now()->year;

        return [
            'year' => $year,
            'submitted' => $this->monthlyCounts('submitted_at', $year),
            'accepted' => $this->monthlyCounts('accepted_at', $year),
            'published' => $this->monthlyCounts('published_at', $year),
        ];
    }

    /**
     * "Yangi kelgan maqolalar" jadvali.
     *
     * @return array<int, array<string, mixed>>
     */
    public function latestSubmissions(int $limit = 5): array
    {
        return Article::query()
            ->whereNotNull('submitted_at')
            ->with(['authors', 'submitter', 'issues'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->map(function (Article $article): array {
                /** @var ArticleAuthor|null $author */
                $author = $article->authors->first();

                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'author' => $author !== null ? $author->short_name : $article->submitter->name,
                    'issue' => $article->issues->first()?->label,
                    'submittedAt' => $article->submitted_at?->toIso8601String(),
                    'status' => $article->status->value,
                    'statusGroup' => self::groupOf($article->status),
                    'statusLabel' => $article->status->label(),
                    'coverUrl' => MediaUrl::from($article->cover_image_path),
                ];
            })
            ->all();
    }

    /**
     * "To'lovlar statistikasi" — joriy yil, oyma-oy muvaffaqiyatli to'lovlar summasi (so'm):
     * Click, Payme va qo'lda tasdiqlangan (bank o'tkazmasi) to'lovlar.
     *
     * @return array{year: int, click: array<int, int>, payme: array<int, int>, manual: array<int, int>, total: int}
     */
    public function paymentsMonthly(?int $year = null): array
    {
        $year ??= (int) now()->year;

        $payments = Payment::query()
            ->paid()
            ->whereYear('paid_at', $year)
            ->get(['provider', 'amount', 'paid_at']);

        $sum = fn (PaymentProvider $provider): array => $this->perMonth(
            $payments->filter(fn (Payment $p): bool => $p->provider === $provider),
            fn (Payment $p): int => (int) $p->paid_at?->month,
            fn (Payment $p): int => (int) round((float) $p->amount),
        );

        return [
            'year' => $year,
            'click' => $sum(PaymentProvider::Click),
            'payme' => $sum(PaymentProvider::Payme),
            'manual' => $sum(PaymentProvider::Manual),
            'total' => (int) round((float) $payments->sum(fn (Payment $p): float => (float) $p->amount)),
        ];
    }

    /**
     * "So'nggi to'lovlar".
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentPayments(int $limit = 3): array
    {
        return Payment::query()
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'provider' => $payment->provider->value,
                'amount' => (int) round((float) $payment->amount),
                'date' => ($payment->paid_at ?? $payment->created_at)?->toIso8601String(),
                'status' => $payment->status->value,
                'statusLabel' => $payment->status->label(),
            ])
            ->all();
    }

    /**
     * "AI xizmatlari ishlatilishi" — joriy oy so'rovlari turlar bo'yicha va o'tgan oyga nisbatan o'sish.
     *
     * @return array{total: int, trend: int|null, types: array<int, array{key: string, label: string, value: int}>}
     */
    public function aiUsage(): array
    {
        $start = now()->startOfMonth();
        $previousStart = $start->copy()->subMonthNoOverflow();

        $current = AiRequest::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $previousTotal = AiRequest::query()
            ->whereBetween('created_at', [$previousStart, $start])
            ->count();

        $total = (int) $current->sum();

        return [
            'total' => $total,
            'trend' => $this->percentChange($total, $previousTotal),
            'types' => array_map(fn (AiRequestType $type): array => [
                'key' => $type->value,
                'label' => $type->label(),
                'value' => (int) ($current[$type->value] ?? 0),
            ], AiRequestType::cases()),
        ];
    }

    /**
     * "So'nggi bildirishnomalar" — joriy foydalanuvchining (database) bildirishnomalari.
     *
     * @return array<int, array<string, mixed>>
     */
    public function notifications(User $user, int $limit = 5): array
    {
        return $user->notifications()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                ...NotificationCenter::item($notification),
                'message' => NotificationCenter::item($notification)['body'],
            ])
            ->all();
    }

    /**
     * "Faol foydalanuvchilar" — oxirgi kirganlar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeUsers(int $limit = 5): array
    {
        return User::query()
            ->whereNotNull('last_login_at')
            ->where('is_blocked', false)
            ->with(['roles', 'authorProfile'])
            ->latest('last_login_at')
            ->limit($limit)
            ->get()
            ->map(function (User $user): array {
                $roleName = $user->roles->pluck('name')->first();
                $role = is_string($roleName) ? RoleName::tryFrom($roleName) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $role?->label(),
                    'avatarUrl' => MediaUrl::from($user->authorProfile?->avatar_path),
                    'lastSeenAt' => $user->last_login_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * "Tizim holati" — asosiy xizmatlar ishlayotgani / sozlangani.
     *
     * @return array<int, array{key: string, label: string, state: string}>
     */
    public function systemHealth(): array
    {
        $databaseUp = true;

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $databaseUp = false;
        }

        $paymentsConfigured = app(OnlinePaymentService::class)->providers() !== [];

        return [
            ['key' => 'web', 'label' => 'Web server', 'state' => 'up'],
            ['key' => 'database', 'label' => 'Database', 'state' => $databaseUp ? 'up' : 'down'],
            ['key' => 'payments', 'label' => "To'lov tizimlari (Click/Payme)", 'state' => $paymentsConfigured ? 'up' : 'not_configured'],
            ['key' => 'ai', 'label' => 'AI server', 'state' => filled(config('services.anthropic.key')) ? 'up' : 'not_configured'],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        $raw = Article::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];

        foreach (self::STATUS_GROUPS as $key => $statuses) {
            $counts[$key] = (int) collect($statuses)->sum(fn (ArticleStatus $s): int => (int) ($raw[$s->value] ?? 0));
        }

        return $counts;
    }

    public static function groupOf(ArticleStatus $status): string
    {
        foreach (self::STATUS_GROUPS as $key => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $key;
            }
        }

        return 'other';
    }

    /** Joriy oy va o'tgan oy bo'yicha sanalgan maqolalar o'zgarishi (%) */
    private function monthTrend(string $column, CarbonInterface $now): ?int
    {
        $start = $now->copy()->startOfMonth();
        $previousStart = $start->copy()->subMonthNoOverflow();

        $current = Article::query()->where($column, '>=', $start)->count();
        $previous = Article::query()->whereBetween($column, [$previousStart, $start])->count();

        return $this->percentChange($current, $previous);
    }

    /**
     * So'nggi 7 kun va undan oldingi 7 kunda shu holatlarga o'tgan maqolalar o'zgarishi (%).
     *
     * @param  array<int, ArticleStatus>  $statuses
     */
    private function historyTrend(array $statuses, CarbonInterface $now): ?int
    {
        $values = array_map(fn (ArticleStatus $s): string => $s->value, $statuses);
        $weekAgo = $now->copy()->subWeek();

        $current = ArticleStatusHistory::query()
            ->whereIn('to_status', $values)
            ->where('created_at', '>=', $weekAgo)
            ->count();
        $previous = ArticleStatusHistory::query()
            ->whereIn('to_status', $values)
            ->whereBetween('created_at', [$weekAgo->copy()->subWeek(), $weekAgo])
            ->count();

        return $this->percentChange($current, $previous);
    }

    private function percentChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * @return array<int, int>
     */
    private function monthlyCounts(string $column, int $year): array
    {
        $dates = Article::query()
            ->whereYear($column, $year)
            ->pluck($column);

        return $this->perMonth(
            $dates,
            fn (mixed $date): int => Carbon::parse($date)->month,
            fn (): int => 1,
        );
    }

    /**
     * 12 oylik massiv: har oy uchun $value yig'indisi.
     *
     * @template T
     *
     * @param  Collection<array-key, T>  $items
     * @param  callable(T): int  $month
     * @param  callable(T): int  $value
     * @return array<int, int>
     */
    private function perMonth(Collection $items, callable $month, callable $value): array
    {
        $totals = array_fill(0, 12, 0);

        foreach ($items as $item) {
            $index = $month($item) - 1;

            if ($index >= 0 && $index < 12) {
                $totals[$index] += $value($item);
            }
        }

        return $totals;
    }
}

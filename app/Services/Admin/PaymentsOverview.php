<?php

namespace App\Services\Admin;

use App\Enums\ArticleStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Article;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin → To'lovlar (supper admin payments.png): statistika, to'lov kutilayotgan
 * maqolalar va to'lovlar ro'yxati (tablar, qidiruv).
 */
class PaymentsOverview
{
    public const PER_PAGE = 15;

    /** Tablar: awaiting — to'lov kutilayotgan maqolalar, qolganlari — payments jadvali */
    public const TABS = ['awaiting', 'all', 'click', 'payme', 'manual', 'failed'];

    /** "Muvaffaqiyatsiz" tabidagi holatlar */
    private const FAILED = [PaymentStatus::Cancelled, PaymentStatus::Failed, PaymentStatus::Refunded];

    /**
     * Statistika kartalari (so'm).
     *
     * @return array<string, array{amount: float, count: int, share?: int|null, trend?: int|null}>
     */
    public function stats(): array
    {
        $paid = Payment::query()->paid();
        $revenue = (float) (clone $paid)->sum('amount');

        $byProvider = (clone $paid)
            ->selectRaw('provider, sum(amount) as total, count(*) as aggregate')
            ->groupBy('provider')
            ->get()
            ->keyBy(fn (Payment $row): string => $row->provider->value);

        $provider = function (PaymentProvider $provider) use ($byProvider, $revenue): array {
            $row = $byProvider->get($provider->value);
            $amount = (float) ($row?->getAttribute('total') ?? 0);

            return [
                'amount' => $amount,
                'count' => (int) ($row?->getAttribute('aggregate') ?? 0),
                'share' => $revenue > 0 ? (int) round($amount / $revenue * 100) : null,
            ];
        };

        $thisMonth = (float) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount');
        $lastMonth = (float) (clone $paid)
            ->whereBetween('paid_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->startOfMonth()])
            ->sum('amount');

        $awaiting = $this->awaitingQuery();

        return [
            'revenue' => [
                'amount' => $revenue,
                'count' => (clone $paid)->count(),
                'month' => $thisMonth,
                'trend' => $lastMonth > 0 ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null,
            ],
            'click' => $provider(PaymentProvider::Click),
            'payme' => $provider(PaymentProvider::Payme),
            'manual' => $provider(PaymentProvider::Manual),
            'awaiting' => [
                'amount' => (float) (clone $awaiting)
                    ->join('article_types', 'article_types.id', '=', 'articles.article_type_id')
                    ->sum('article_types.price'),
                'count' => (clone $awaiting)->count(),
            ],
        ];
    }

    /**
     * Tablar uchun sonlar.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $byProvider = Payment::query()
            ->selectRaw('provider, count(*) as aggregate')
            ->groupBy('provider')
            ->pluck('aggregate', 'provider');

        return [
            'awaiting' => $this->awaitingQuery()->count(),
            'all' => (int) $byProvider->sum(),
            'click' => (int) ($byProvider[PaymentProvider::Click->value] ?? 0),
            'payme' => (int) ($byProvider[PaymentProvider::Payme->value] ?? 0),
            'manual' => (int) ($byProvider[PaymentProvider::Manual->value] ?? 0),
            'failed' => Payment::query()->whereIn('status', $this->values(self::FAILED))->count(),
        ];
    }

    /**
     * To'lov kutilayotgan maqolalar (eng uzoq kutayotgani birinchi).
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function awaiting(?string $search): LengthAwarePaginator
    {
        $query = $this->awaitingQuery()->with(['submitter', 'articleType', 'subject']);

        if ($search !== null) {
            $like = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->where('title->uz', 'like', $like)
                ->orWhere('title->ru', 'like', $like)
                ->orWhere('title->en', 'like', $like)
                ->orWhereHas('submitter', fn (Builder $user) => $user
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)));
        }

        return $query->oldest('submitted_at')->oldest('id')->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function payments(string $tab, ?string $search): LengthAwarePaginator
    {
        $query = Payment::query()->with(['user', 'article', 'confirmedBy', 'items']);

        match ($tab) {
            'click' => $query->where('provider', PaymentProvider::Click->value),
            'payme' => $query->where('provider', PaymentProvider::Payme->value),
            'manual' => $query->where('provider', PaymentProvider::Manual->value),
            'failed' => $query->whereIn('status', $this->values(self::FAILED)),
            default => null,
        };

        if ($search !== null) {
            $like = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->where('receipt_number', 'like', $like)
                ->orWhere('provider_transaction_id', 'like', $like)
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orWhereHas('article', fn (Builder $article) => $article
                    ->where('title->uz', 'like', $like)
                    ->orWhere('title->ru', 'like', $like)
                    ->orWhere('title->en', 'like', $like)));
        }

        return $query->latest()->latest('id')->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * To'lovlar soni provayderlar bo'yicha (muvaffaqiyatli) — donut uchun.
     *
     * @return array{total: int, items: array<int, array{key: string, label: string, value: int}>}
     */
    public function providerBreakdown(): array
    {
        $counts = Payment::query()->paid()
            ->selectRaw('provider, count(*) as aggregate')
            ->groupBy('provider')
            ->pluck('aggregate', 'provider');

        $items = array_map(fn (PaymentProvider $provider): array => [
            'key' => $provider->value,
            'label' => $provider->label(),
            'value' => (int) ($counts[$provider->value] ?? 0),
        ], PaymentProvider::cases());

        return ['total' => (int) $counts->sum(), 'items' => $items];
    }

    /**
     * @return Builder<Article>
     */
    private function awaitingQuery(): Builder
    {
        return Article::query()->where('articles.status', ArticleStatus::AwaitingPayment->value);
    }

    /**
     * @param  array<int, PaymentStatus>  $statuses
     * @return array<int, string>
     */
    private function values(array $statuses): array
    {
        return array_map(fn (PaymentStatus $status): string => $status->value, $statuses);
    }
}

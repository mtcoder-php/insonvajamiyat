<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Hisobot davri: [from, to] (kunlar bo'yicha, ikkalasi ham kiradi).
 *
 * - Standart: oxirgi 6 oy (5 oy oldingi oyning 1-kunidan bugungacha).
 * - Taqqoslash uchun "oldingi davr" — xuddi shu uzunlikdagi undan oldingi oraliq.
 * - Grafik bo'laklari: 62 kungacha — kunlik, undan uzun — oylik.
 * - Eng uzun davr — 3 yil (so'rovlar og'irlashmasligi uchun).
 */
final class ReportPeriod
{
    public const MAX_DAYS = 3 * 366;

    public const DAILY_LIMIT = 62;

    private const MONTHS = ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyun', 'Iyul', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function make(?string $from = null, ?string $to = null): self
    {
        $today = CarbonImmutable::now()->endOfDay();
        $end = self::parse($to)?->endOfDay() ?? $today;
        $end = $end->greaterThan($today) ? $today : $end;

        $start = self::parse($from)?->startOfDay()
            ?? $end->subMonthsNoOverflow(5)->startOfMonth();

        if ($start->greaterThan($end)) {
            $start = $end->startOfDay();
        }

        if ($start->diffInDays($end) > self::MAX_DAYS) {
            $start = $end->subDays(self::MAX_DAYS)->startOfDay();
        }

        return new self($start, $end);
    }

    public static function today(): self
    {
        $now = CarbonImmutable::now();

        return new self($now->startOfDay(), $now->endOfDay());
    }

    /** Xuddi shu uzunlikdagi oldingi davr */
    public function previous(): self
    {
        $days = $this->days();
        $end = $this->from->subDay()->endOfDay();

        return new self($end->subDays($days - 1)->startOfDay(), $end);
    }

    public function days(): int
    {
        return (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;
    }

    public function isDaily(): bool
    {
        return $this->days() <= self::DAILY_LIMIT;
    }

    /**
     * Grafik bo'laklari: kalit (Y-m-d yoki Y-m) va yorliq.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function buckets(): array
    {
        $buckets = [];

        if ($this->isDaily()) {
            for ($day = $this->from->startOfDay(); $day->lessThanOrEqualTo($this->to); $day = $day->addDay()) {
                $buckets[] = ['key' => $day->format('Y-m-d'), 'label' => $day->format('d.m')];
            }

            return $buckets;
        }

        $multiYear = $this->from->year !== $this->to->year;

        for ($month = $this->from->startOfMonth(); $month->lessThanOrEqualTo($this->to); $month = $month->addMonthNoOverflow()) {
            $label = self::MONTHS[$month->month - 1];
            $buckets[] = [
                'key' => $month->format('Y-m'),
                'label' => $multiYear ? $label.' '.$month->format('y') : $label,
            ];
        }

        return $buckets;
    }

    /** Sana qaysi bo'lakka tushadi */
    public function bucketKey(CarbonInterface|string $date): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $date->format($this->isDaily() ? 'Y-m-d' : 'Y-m');
    }

    /**
     * Qiymatlarni bo'laklar bo'yicha yig'ish.
     *
     * @param  iterable<array-key, array{0: CarbonInterface|string, 1: int|float}>  $rows  [sana, qiymat]
     * @return array<int, int|float>
     */
    public function series(iterable $rows): array
    {
        $totals = array_fill_keys(array_column($this->buckets(), 'key'), 0);

        foreach ($rows as [$date, $value]) {
            $key = $this->bucketKey($date);

            if (array_key_exists($key, $totals)) {
                $totals[$key] += $value;
            }
        }

        return array_values($totals);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        return [$this->from, $this->to];
    }

    /**
     * @return array{from: string, to: string, days: int, daily: bool, label: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'days' => $this->days(),
            'daily' => $this->isDaily(),
            'label' => $this->from->format('d.m.Y').' – '.$this->to->format('d.m.Y'),
        ];
    }

    private static function parse(?string $value): ?CarbonImmutable
    {
        if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}

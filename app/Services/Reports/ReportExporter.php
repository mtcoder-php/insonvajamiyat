<?php

namespace App\Services\Reports;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Payment;
use App\Services\Editorial\EditorialWorkspace;
use App\Support\ReportPeriod;
use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hisobotlarni CSV (Excel) ga eksport qilish.
 *
 * Fayl Excel'da to'g'ri ochilishi uchun: UTF-8 BOM va ";" ajratgich
 * (o'zbek/rus mintaqa sozlamalarida Excel standart ajratgichi).
 * Ma'lumot oqim bilan (cursor/chunk) yoziladi — katta hajmda ham xotira oshmaydi.
 */
class ReportExporter
{
    public const TYPES = [
        'articles' => 'Maqolalar',
        'payments' => "To'lovlar",
        'reviewers' => 'Taqrizchilar',
        'authors' => 'Mualliflar',
    ];

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly ReviewerStatsService $reviewers,
    ) {}

    public function download(string $type, ReportPeriod $period, ?int $subjectId): StreamedResponse
    {
        [$headers, $rows] = match ($type) {
            'payments' => [$this->paymentHeaders(), $this->paymentRows($period, $subjectId)],
            'reviewers' => [$this->reviewerHeaders(), $this->reviewerRows($period, $subjectId)],
            'authors' => [$this->authorHeaders(), $this->authorRows($period, $subjectId)],
            default => [$this->articleHeaders(), $this->articleRows($period, $subjectId)],
        };

        $filename = sprintf('%s_%s_%s.csv', $type, $period->from->toDateString(), $period->to->toDateString());

        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ';', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Formula injection'dan himoya: = + - @ bilan boshlangan matn oldiga apostrof qo'yiladi.
     */
    public static function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Ha' : "Yo'q";
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = is_scalar($value) ? (string) $value : '';

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /** @return array<int, string> */
    private function articleHeaders(): array
    {
        return ['ID', 'Kod', 'Sarlavha', 'Mualliflar', "Yo'nalish", 'Holat', 'Yuborilgan', 'Qabul qilingan', 'Rad etilgan', 'Nashr etilgan', 'DOI', "Ko'rishlar", 'Yuklab olishlar'];
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function articleRows(ReportPeriod $period, ?int $subjectId): Generator
    {
        $query = $this->analytics->articles($subjectId)
            ->whereBetween('submitted_at', $period->range())
            ->with(['authors', 'subject'])
            ->orderBy('submitted_at');

        foreach ($query->lazy(200) as $article) {
            /** @var Article $article */
            yield [
                $article->id,
                EditorialWorkspace::code($article),
                $article->title,
                $article->authors->sortBy('sort_order')->map(fn (ArticleAuthor $a): string => $a->full_name)->implode(', '),
                $article->subject?->name,
                $article->status->label(),
                $article->submitted_at?->format('d.m.Y H:i'),
                $article->accepted_at?->format('d.m.Y'),
                $article->rejected_at?->format('d.m.Y'),
                $article->published_at?->format('d.m.Y'),
                $article->doi,
                $article->views_count,
                $article->downloads_count,
            ];
        }
    }

    /** @return array<int, string> */
    private function paymentHeaders(): array
    {
        return ['Kvitansiya', "To'lov tizimi", 'Summa', 'Valyuta', "To'lovchi", 'Email', 'Maqola', "To'langan sana", 'Tranzaksiya'];
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function paymentRows(ReportPeriod $period, ?int $subjectId): Generator
    {
        $query = $this->analytics->payments($period, $subjectId)
            ->with(['user', 'article'])
            ->orderBy('paid_at');

        foreach ($query->lazy(200) as $payment) {
            /** @var Payment $payment */
            yield [
                $payment->receipt_number,
                $payment->provider->label(),
                (float) $payment->amount,
                $payment->currency,
                $payment->user->name,
                $payment->user->email,
                $payment->article !== null ? EditorialWorkspace::code($payment->article) : null,
                $payment->paid_at?->format('d.m.Y H:i'),
                $payment->provider_transaction_id,
            ];
        }
    }

    /** @return array<int, string> */
    private function reviewerHeaders(): array
    {
        return ['Taqrizchi', 'Email', 'Ilmiy daraja', 'Takliflar', 'Qabul qilgan', 'Rad etgan', 'Topshirgan', 'Jarayonda', "Muddati o'tgan", "O'rtacha kun", 'Muddatida (%)'];
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function reviewerRows(ReportPeriod $period, ?int $subjectId): Generator
    {
        foreach ($this->reviewers->table($period, $subjectId)['rows'] as $row) {
            yield [
                $row['name'], $row['email'], $row['degree'], $row['invited'], $row['accepted'], $row['declined'],
                $row['completed'], $row['pending'], $row['overdue'], $row['avgDays'], $row['onTime'],
            ];
        }
    }

    /** @return array<int, string> */
    private function authorHeaders(): array
    {
        return ['Familiya', 'Ism', 'Tashkilot', 'Mamlakat', 'Email', 'ORCID', 'Maqolalar'];
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function authorRows(ReportPeriod $period, ?int $subjectId): Generator
    {
        $rows = $this->analytics->authorsQuery($period, $subjectId)
            ->selectRaw('article_authors.last_name, article_authors.first_name, max(article_authors.organization) as organization, max(article_authors.country) as country, max(article_authors.email) as email, max(article_authors.orcid) as orcid, count(distinct article_authors.article_id) as total')
            ->groupBy('article_authors.last_name', 'article_authors.first_name')
            ->orderByDesc('total')
            ->orderBy('article_authors.last_name')
            ->get();

        foreach ($rows as $row) {
            $country = is_string($row->country ?? null) ? strtoupper($row->country) : null;

            yield [
                $row->last_name ?? null,
                $row->first_name ?? null,
                $row->organization ?? null,
                $country !== null ? (AnalyticsService::COUNTRIES[$country] ?? $country) : null,
                $row->email ?? null,
                $row->orcid ?? null,
                (int) ($row->total ?? 0),
            ];
        }
    }
}

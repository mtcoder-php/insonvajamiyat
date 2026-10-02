<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Subject;
use App\Services\Audit\AuditLogger;
use App\Services\Reports\AnalyticsService;
use App\Services\Reports\ReportExporter;
use App\Services\Reports\ReviewerStatsService;
use App\Support\ReportPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Statistika va hisobotlar (super admin analistic page.png).
 *
 * Filtrlar (query): from, to (Y-m-d), subject (yo'nalish slug), tab (overview|reviewers|exports),
 * jadval uchun: status (AnalyticsService::TABLE_TABS), q, page.
 * Noto'g'ri qiymatlar xato bermaydi — standart qiymat olinadi.
 */
class ReportController extends Controller
{
    public const TABS = ['overview', 'reviewers', 'exports'];

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly ReviewerStatsService $reviewers,
    ) {}

    public function index(Request $request): Response
    {
        $period = $this->period($request);
        $subject = $this->subject($request);
        $subjectId = $subject?->id;
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $status = $request->string('status')->toString();
        $status = array_key_exists($status, AnalyticsService::TABLE_TABS) ? $status : 'all';
        $search = $request->string('q')->trim()->limit(100, '')->toString() ?: null;
        $overview = $tab === 'overview';

        return Inertia::render('admin/reports/Index', [
            'filters' => [
                ...$period->toArray(),
                'subject' => $subject?->slug,
                'tab' => $tab,
                'status' => $status,
                'q' => $search,
            ],
            'subjects' => fn () => $this->analytics->subjectOptions(),
            'kpis' => fn () => $this->analytics->kpis($period, $subjectId),
            'quick' => fn () => $this->analytics->quick(),
            'system' => fn () => $this->analytics->system($period),
            'topAuthors' => fn () => $this->analytics->topAuthors($period, $subjectId),

            'dynamics' => $overview ? fn () => $this->analytics->dynamics($period, $subjectId) : null,
            'subjectsChart' => $overview ? fn () => $this->analytics->subjects($period, $subjectId) : null,
            'countries' => $overview ? fn () => $this->analytics->countries($period, $subjectId) : null,
            'organizations' => $overview ? fn () => $this->analytics->organizations($period, $subjectId) : null,
            'revenue' => $overview ? fn () => $this->analytics->revenue($period, $subjectId) : null,
            'ai' => $overview ? fn () => $this->analytics->ai($period) : null,
            'articles' => $overview ? function () use ($period, $subjectId, $status, $search): array {
                $page = $this->analytics->articlesTable($period, $subjectId, $status, $search);

                return [
                    'data' => array_map(
                        fn (Article $article): array => AnalyticsService::tableRow($article),
                        $page->items(),
                    ),
                    'meta' => [
                        'currentPage' => $page->currentPage(),
                        'lastPage' => $page->lastPage(),
                        'total' => $page->total(),
                        'from' => $page->firstItem(),
                        'to' => $page->lastItem(),
                    ],
                ];
            } : null,

            'reviewers' => $tab === 'reviewers' ? fn () => $this->reviewers->table($period, $subjectId) : null,
            'exports' => fn () => collect(ReportExporter::TYPES)
                ->map(fn (string $label, string $type): array => [
                    'type' => $type,
                    'label' => $label,
                    'url' => route('admin.reports.export', ['type' => $type, 'from' => $period->from->toDateString(), 'to' => $period->to->toDateString(), 'subject' => $subject?->slug]),
                ])
                ->values()
                ->all(),
            'printUrl' => route('admin.reports.print', ['from' => $period->from->toDateString(), 'to' => $period->to->toDateString(), 'subject' => $subject?->slug]),
        ]);
    }

    /** CSV (Excel) eksport */
    public function export(Request $request, string $type, ReportExporter $exporter, AuditLogger $audit): StreamedResponse
    {
        abort_unless(array_key_exists($type, ReportExporter::TYPES), 404);

        $period = $this->period($request);
        $subject = $this->subject($request);

        $audit->log(AuditEvent::ReportExported, properties: [
            'type' => $type,
            'format' => 'csv',
            'from' => $period->from->toDateString(),
            'to' => $period->to->toDateString(),
            'subject' => $subject?->slug,
        ], description: ReportExporter::TYPES[$type].' (CSV)');

        return $exporter->download($type, $period, $subject?->id);
    }

    /** Umumiy hisobot — chop etish / "PDF sifatida saqlash" sahifasi (A4) */
    public function print(Request $request, AuditLogger $audit): View
    {
        $period = $this->period($request);
        $subject = $this->subject($request);
        $subjectId = $subject?->id;

        $audit->log(AuditEvent::ReportExported, properties: [
            'type' => 'summary',
            'format' => 'pdf',
            'from' => $period->from->toDateString(),
            'to' => $period->to->toDateString(),
            'subject' => $subject?->slug,
        ], description: 'Umumiy hisobot (PDF)');

        return view('reports.summary', [
            'journal' => config('journal'),
            'period' => $period,
            'subject' => $subject,
            'kpis' => $this->analytics->kpis($period, $subjectId),
            'subjects' => $this->analytics->subjects($period, $subjectId),
            'countries' => $this->analytics->countries($period, $subjectId),
            'organizations' => $this->analytics->organizations($period, $subjectId),
            'revenue' => $this->analytics->revenue($period, $subjectId),
            'topAuthors' => $this->analytics->topAuthors($period, $subjectId, 10),
            'reviewers' => $this->reviewers->table($period, $subjectId),
            'generatedAt' => now(),
            'backUrl' => route('admin.reports.index', ['from' => $period->from->toDateString(), 'to' => $period->to->toDateString(), 'subject' => $subject?->slug]),
        ]);
    }

    private function period(Request $request): ReportPeriod
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return ReportPeriod::make(is_string($from) ? $from : null, is_string($to) ? $to : null);
    }

    private function subject(Request $request): ?Subject
    {
        $slug = $request->query('subject');

        return is_string($slug) && $slug !== ''
            ? Subject::query()->where('slug', $slug)->first()
            : null;
    }
}

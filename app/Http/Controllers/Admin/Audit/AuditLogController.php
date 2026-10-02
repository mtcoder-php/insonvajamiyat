<?php

namespace App\Http\Controllers\Admin\Audit;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\AuditLogQuery;
use App\Services\Reports\ReportExporter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Audit log (TZ 4.2.9): kim, qachon, qayerdan, nima qildi.
 * Faqat ko'rish va eksport — yozuvlarni tahrirlash/o'chirish imkoniyati yo'q.
 */
class AuditLogController extends Controller
{
    public const EXPORT_LIMIT = 20000;

    public function __construct(private readonly AuditLogQuery $logs) {}

    public function index(Request $request): Response
    {
        $filters = AuditLogQuery::filters($request->query->all());
        $page = $this->logs->paginate($filters);
        $links = AuditLogQuery::links($page->items());

        return Inertia::render('admin/audit/Index', [
            'filters' => $filters,
            'logs' => [
                'data' => array_map(fn (AuditLog $log): array => AuditLogQuery::row($log, $links), $page->items()),
                'meta' => [
                    'currentPage' => $page->currentPage(),
                    'lastPage' => $page->lastPage(),
                    'total' => $page->total(),
                    'from' => $page->firstItem(),
                    'to' => $page->lastItem(),
                ],
            ],
            'stats' => fn () => $this->logs->stats(),
            'categories' => fn () => collect(AuditEvent::categories())
                ->map(fn (string $label, string $key): array => ['value' => $key, 'label' => $label])
                ->values()
                ->all(),
            'events' => fn () => array_map(fn (AuditEvent $e): array => [
                'value' => $e->value,
                'label' => $e->label(),
                'category' => $e->category(),
            ], AuditEvent::cases()),
            'users' => fn () => $this->logs->userOptions(),
            'retentionDays' => (int) config('journal.audit.retention_days', 365),
            'exportUrl' => route('admin.audit.export', array_filter($filters, fn (mixed $v): bool => $v !== null)),
        ]);
    }

    /** Filtrlangan yozuvlar CSV (Excel) ko'rinishida, eng ko'pi 20 000 ta */
    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $filters = AuditLogQuery::filters($request->query->all());

        $audit->log(AuditEvent::ReportExported, properties: [
            'type' => 'audit',
            'format' => 'csv',
            'filters' => array_filter($filters, fn (mixed $v): bool => $v !== null),
        ], description: 'Audit log (CSV)');

        $query = $this->logs->query($filters)->with('user')->latest('created_at')->latest('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Vaqt', 'Foydalanuvchi', 'Email', 'Amal', 'Obyekt', 'Izoh', 'IP manzil', 'Brauzer'], ';', '"', '');

            $written = 0;

            foreach ($query->lazy(500) as $log) {
                /** @var AuditLog $log */
                if (++$written > self::EXPORT_LIMIT) {
                    break;
                }

                fputcsv($out, array_map(ReportExporter::cell(...), [
                    $log->created_at->format('d.m.Y H:i:s'),
                    $log->user?->name,
                    $log->user?->email,
                    $log->event->label(),
                    $log->subject_label,
                    $log->description,
                    $log->ip_address,
                    $log->user_agent,
                ]), ';', '"', '');
            }

            fclose($out);
        }, 'audit-log_'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

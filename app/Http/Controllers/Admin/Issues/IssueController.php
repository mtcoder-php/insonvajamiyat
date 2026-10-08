<?php

namespace App\Http\Controllers\Admin\Issues;

use App\Enums\AuditEvent;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Issues\IssueRequest;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Indexing\CrossrefDeposit;
use App\Services\Issues\IssuePdfBuilder;
use App\Services\Issues\IssueService;
use App\Services\Issues\IssueWorkspace;
use App\Services\Publishing\PublishService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Jurnallar (TZ 4.2.3): sonlar ro'yxati, son yaratish va tahrirlash, fayllar, mundarija.
 */
class IssueController extends Controller
{
    public function __construct(
        private readonly IssueService $issues,
        private readonly IssueWorkspace $workspace,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $year = $request->filled('year') ? $request->integer('year') : null;
        $status = IssueStatus::tryFrom($request->string('status')->toString())?->value;

        return Inertia::render('admin/issues/Index', [
            'filters' => ['year' => $year, 'status' => $status],
            'issues' => $this->workspace->list($year, $status),
            'stats' => $this->workspace->stats(),
            'years' => $this->workspace->years(),
            'next' => $this->nextNumber(),
            'storeUrl' => route('admin.issues.store'),
        ]);
    }

    public function store(IssueRequest $request): RedirectResponse
    {
        $issue = $this->issues->create($request->issue(), $this->user($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':label soni yaratildi.', ['label' => $issue->label])]);

        return to_route('admin.issues.show', $issue->slug);
    }

    public function show(Request $request, JournalIssue $issue): Response
    {
        return Inertia::render('admin/issues/Show', [
            'issue' => $this->workspace->detail($issue, $this->user($request)),
            'available' => fn () => $this->workspace->available(),
        ]);
    }

    public function update(IssueRequest $request, JournalIssue $issue): RedirectResponse
    {
        $this->issues->update($issue, $request->issue());

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Son ma'lumotlari saqlandi.")]);

        return to_route('admin.issues.show', $issue->slug);
    }

    public function destroy(JournalIssue $issue): RedirectResponse
    {
        $this->issues->delete($issue);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Son o'chirildi.")]);

        return to_route('admin.issues.index');
    }

    /** Sonni chop etish: tayyor maqolalar nashr etiladi, son saytda ko'rinadi */
    public function publish(Request $request, JournalIssue $issue, PublishService $publisher): RedirectResponse
    {
        $published = $publisher->publishIssue($issue, $this->user($request));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':label soni chop etildi (:count ta maqola).', ['label' => $issue->label, 'count' => $published->count()]),
        ]);

        return to_route('admin.issues.show', $issue->slug);
    }

    /** Muqova (rasm), butun son PDF yoki mundarija PDF */
    public function storeFile(Request $request, JournalIssue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(IssueService::FILES))],
        ]);
        $type = (string) $validated['type'];

        $request->validate(
            ['file' => $type === 'cover'
                ? ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=300,min_height=400']
                : ['required', 'file', 'mimes:pdf', 'max:51200']],
            [],
            ['file' => $type === 'cover' ? __('Muqova') : __('PDF fayl')],
        );

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        $this->issues->uploadFile($issue, $type, $file);

        return $this->done(__('Fayl yuklandi.'));
    }

    /** To'liq son PDF ni avtomatik yig'ish (navbatda) */
    public function buildPdf(Request $request, JournalIssue $issue, IssuePdfBuilder $builder): RedirectResponse
    {
        $builder->queue($issue, $this->user($request));

        return $this->done(__("Son PDF ini yig'ish boshlandi. Tayyor bo'lgach shu yerda paydo bo'ladi."));
    }

    public function destroyFile(JournalIssue $issue, string $type): RedirectResponse
    {
        abort_unless(array_key_exists($type, IssueService::FILES), 404);

        $this->issues->removeFile($issue, $type);

        return $this->done(__("Fayl o'chirildi."));
    }

    /**
     * Crossref DOI deposit XML (chop etilgan son) — Crossref kabinetiga yuklash uchun.
     */
    public function crossref(JournalIssue $issue, CrossrefDeposit $crossref): HttpResponse|RedirectResponse
    {
        abort_if($issue->status === IssueStatus::Draft, 404);

        try {
            $xml = $crossref->build($issue);
        } catch (RuntimeException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Sonda DOI\'si bor nashr etilgan maqola yo\'q.')]);

            return back();
        }

        $this->audit->log(AuditEvent::ReportExported, $issue, ['format' => 'crossref', 'articles' => $crossref->articles($issue)->count()], 'Crossref XML');

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="crossref-'.$issue->slug.'.xml"',
        ]);
    }

    /** Mundarija — chop etish uchun sahifa (brauzerda "PDF sifatida saqlash") */
    public function toc(JournalIssue $issue): View
    {
        return view('issues.toc', [
            'issue' => $issue,
            'groups' => $this->workspace->toc($issue),
            'journal' => config('journal'),
        ]);
    }

    /**
     * Yangi son uchun taklif: joriy yil va keyingi raqam.
     *
     * @return array{year: int, number: int, volume: int|null}
     */
    private function nextNumber(): array
    {
        $year = (int) now()->year;
        $last = JournalIssue::query()->where('year', $year)->max('number');
        $volume = JournalIssue::query()->latest('year')->latest('number')->value('volume');

        return [
            'year' => $year,
            'number' => (int) $last + 1,
            'volume' => is_numeric($volume) ? (int) $volume : null,
        ];
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}

<?php

namespace App\Http\Controllers\Admin\Issues;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Issues\IssueCoverService;
use App\Services\Issues\IssuePdfBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Admin → Jurnallar → «Umumiy muqova»: barcha sonlar uchun bitta muqova rasmi,
 * son raqami/yilini yozish sozlamalari va namuna (PDF).
 */
class IssueCoverController extends Controller
{
    public function __construct(
        private readonly IssueCoverService $covers,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=600,min_height=800'],
        ], attributes: ['file' => __('Muqova')]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $this->covers->upload($file, $this->user($request));
        $this->audit->log(AuditEvent::SettingsUpdated, properties: ['section' => 'issue_cover', 'action' => 'upload'], description: 'Umumiy muqova');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Umumiy muqova yuklandi.')]);

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stamp' => ['required', 'boolean'],
            'position' => ['required', Rule::in(IssueCoverService::POSITIONS)],
            'color' => ['required', Rule::in(IssueCoverService::COLORS)],
        ]);

        $this->covers->updateStamp(
            (bool) $data['stamp'],
            $data['position'] === 'top' ? 'top' : 'bottom',
            match ($data['color']) {
                'white' => 'white',
                'navy' => 'navy',
                default => 'gold',
            },
            $this->user($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Muqova sozlamalari saqlandi.')]);

        return back();
    }

    public function destroy(): RedirectResponse
    {
        $this->covers->remove();
        $this->audit->log(AuditEvent::SettingsUpdated, properties: ['section' => 'issue_cover', 'action' => 'delete'], description: 'Umumiy muqova');

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Umumiy muqova o'chirildi — muqova avtomatik chiziladi.")]);

        return back();
    }

    /** Namuna: eng so'nggi son uchun muqova sahifasi (PDF, brauzerda) */
    public function preview(IssuePdfBuilder $builder): Response
    {
        $issue = JournalIssue::query()->orderByDesc('year')->orderByDesc('number')->first()
            ?? new JournalIssue(['year' => (int) now()->year, 'number' => 1]);

        return response($builder->coverPdf($issue), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="muqova-namuna.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}

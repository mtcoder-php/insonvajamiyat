<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\AuditEvent;
use App\Enums\BackupType;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Zaxira nusxa (settings.manage): yaratish, yuklab olish, o'chirish va jadval.
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): Response
    {
        return Inertia::render('admin/backups/Index', [
            'backups' => fn (): array => $this->backups->list(),
            'stats' => fn (): array => $this->backups->stats(),
            'settings' => $this->backups->settings(),
            'types' => array_map(fn (BackupType $t): array => ['value' => $t->value, 'label' => $t->label()], BackupType::cases()),
            'database' => (string) config('database.default'),
            'urls' => [
                'store' => route('admin.backups.store'),
                'settings' => route('admin.backups.settings'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', Rule::enum(BackupType::class)]]);
        $this->backups->start(BackupType::from((string) $data['type']), $this->user($request));

        return $this->done(__("Zaxira nusxa navbatga qo'yildi. Tayyor bo'lgach ro'yxatda paydo bo'ladi."));
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::enum(BackupType::class)],
            'keep' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->backups->saveSettings([
            'enabled' => (bool) $data['enabled'],
            'time' => (string) $data['time'],
            'type' => (string) $data['type'],
            'keep' => (int) $data['keep'],
        ], $this->user($request));

        $this->backups->prune();

        return $this->done(__('Jadval saqlandi.'));
    }

    public function download(Request $request, Backup $backup, AuditLogger $audit): StreamedResponse
    {
        abort_unless($this->backups->fileExists($backup) && $backup->path !== null, 404);

        $audit->log(AuditEvent::BackupDownloaded, null, ['uuid' => $backup->uuid, 'path' => $backup->path], actor: $this->user($request));

        return Storage::disk($backup->disk)->download($backup->path, basename($backup->path));
    }

    public function destroy(Request $request, Backup $backup): RedirectResponse
    {
        $this->backups->delete($backup, $this->user($request));

        return $this->done(__("Zaxira nusxa o'chirildi."));
    }

    private function done(mixed $message): RedirectResponse
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

<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Zaxira nusxani navbatda yaratish (baza dump + fayllarni zip qilish uzoq davom etishi mumkin).
 */
class RunBackup implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public readonly Backup $backup) {}

    public function handle(BackupService $backups): void
    {
        $backups->run($this->backup);
    }

    public function failed(?Throwable $exception): void
    {
        $this->backup->forceFill([
            'status' => Backup::FAILED,
            'error' => mb_substr((string) $exception?->getMessage(), 0, 1000),
            'finished_at' => now(),
        ])->save();
    }
}

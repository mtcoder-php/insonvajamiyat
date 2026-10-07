<?php

namespace App\Console\Commands;

use App\Enums\BackupType;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Zaxira nusxa:
 *   php artisan backup:run                 — darhol (navbatsiz) to'liq zaxira
 *   php artisan backup:run --type=database — faqat baza
 *   php artisan backup:run --scheduled     — admin paneldagi jadval bo'yicha (scheduler har 10 daqiqada chaqiradi)
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--type= : full | database | files} {--scheduled : Jadval bo\'yicha (vaqti kelgan bo\'lsa)}';

    protected $description = 'Ma\'lumotlar bazasi va fayllarning zaxira nusxasini yaratish';

    public function handle(BackupService $backups): int
    {
        if ($this->option('scheduled')) {
            if (! $backups->isDue()) {
                return self::SUCCESS;
            }

            $type = BackupType::tryFrom($backups->settings()['type']) ?? BackupType::Full;

            try {
                $backups->start($type, null, 'schedule');
                $this->info('Jadval bo\'yicha zaxira navbatga qo\'yildi.');
            } catch (ValidationException) {
                $this->warn('Boshqa zaxira jarayoni ketmoqda.');
            }

            return self::SUCCESS;
        }

        $option = $this->option('type');
        $type = BackupType::tryFrom(is_string($option) ? $option : 'full');

        if ($type === null) {
            $this->error('Noto\'g\'ri tur. Mumkin: full, database, files');

            return self::FAILURE;
        }

        $backup = new Backup;
        $backup->forceFill([
            'type' => $type,
            'status' => Backup::QUEUED,
            'trigger' => 'manual',
            'disk' => $backups->diskName(),
        ])->save();

        $this->info('Zaxira yaratilmoqda...');
        $backups->run($backup->refresh());
        $backup->refresh();

        if ($backup->status !== Backup::DONE) {
            $this->error('Xato: '.$backup->error);

            return self::FAILURE;
        }

        $this->info(sprintf('Tayyor: %s (%s MB, %d ms)', $backup->path, number_format(($backup->size ?? 0) / 1048576, 1), $backup->duration_ms ?? 0));

        return self::SUCCESS;
    }
}

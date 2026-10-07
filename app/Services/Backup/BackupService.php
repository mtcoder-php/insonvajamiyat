<?php

namespace App\Services\Backup;

use App\Enums\AuditEvent;
use App\Enums\BackupType;
use App\Jobs\RunBackup;
use App\Models\Backup;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsStore;
use App\Support\Backup\DatabaseDumper;
use App\Support\MediaUrl;
use Carbon\CarbonInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use ZipArchive;

/**
 * Zaxira nusxalar: yaratish (navbat orqali), arxivlash, saqlash muddati, jadval.
 *
 * Arxiv tarkibi (.zip):
 *   manifest.json         — sana, tur, baza drayveri, ilova versiyasi;
 *   database.sql.gz       — baza (BackupType::includesDatabase);
 *   files/public/...      — public disk (muqovalar, bannerlar, avatarlar);
 *   files/private/...     — local disk (maqola fayllari, PDF lar) — backups papkasidan tashqari.
 */
class BackupService
{
    public const GROUP = 'backup';

    /** Shuncha vaqtdan beri "running" bo'lgan zaxira osilib qolgan hisoblanadi */
    private const STALE_MINUTES = 180;

    public function __construct(
        private readonly SettingsStore $store,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{enabled: bool, time: string, type: string, keep: int}
     */
    public function settings(): array
    {
        $time = $this->store->get(self::GROUP, 'time', config('backup.defaults.time'));
        $type = $this->store->get(self::GROUP, 'type', config('backup.defaults.type'));
        $keep = $this->store->get(self::GROUP, 'keep', config('backup.defaults.keep'));

        return [
            'enabled' => (bool) $this->store->get(self::GROUP, 'enabled', (bool) config('backup.defaults.enabled')),
            'time' => is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) === 1 ? $time : '03:30',
            'type' => is_string($type) && BackupType::tryFrom($type) !== null ? $type : BackupType::Full->value,
            'keep' => is_numeric($keep) ? max(1, (int) $keep) : 14,
        ];
    }

    /**
     * @param  array{enabled: bool, time: string, type: string, keep: int}  $data
     */
    public function saveSettings(array $data, User $actor): void
    {
        $this->store->set(self::GROUP, 'enabled', $data['enabled'], $actor);
        $this->store->set(self::GROUP, 'time', $data['time'], $actor);
        $this->store->set(self::GROUP, 'type', $data['type'], $actor);
        $this->store->set(self::GROUP, 'keep', $data['keep'], $actor);

        $this->audit->log(AuditEvent::SettingsUpdated, null, ['group' => 'backup', 'keys' => array_keys($data)], actor: $actor);
    }

    /**
     * Yangi zaxira nusxani navbatga qo'yadi (bir vaqtda faqat bittasi).
     */
    public function start(BackupType $type, ?User $actor, string $trigger = 'manual'): Backup
    {
        $this->expireStale();

        if (Backup::query()->whereIn('status', [Backup::QUEUED, Backup::RUNNING])->exists()) {
            throw ValidationException::withMessages([
                'type' => __('Zaxira nusxa allaqachon yaratilmoqda. Tugashini kuting.'),
            ]);
        }

        $backup = new Backup;
        $backup->forceFill([
            'type' => $type,
            'status' => Backup::QUEUED,
            'trigger' => $trigger,
            'disk' => $this->diskName(),
            'created_by' => $actor?->id,
        ])->save();

        if ($actor !== null) {
            $this->audit->log(AuditEvent::BackupCreated, null, ['type' => $type->value, 'uuid' => $backup->uuid], actor: $actor);
        }

        RunBackup::dispatch($backup);

        return $backup;
    }

    /**
     * Arxivni yaratish (RunBackup job ichida).
     */
    public function run(Backup $backup): void
    {
        $started = microtime(true);
        $backup->forceFill(['status' => Backup::RUNNING, 'started_at' => now()])->save();

        $disk = $this->disk();
        $directory = $this->directory();
        $disk->makeDirectory($directory);

        $name = sprintf('backup-%s-%s.zip', now()->format('Y-m-d-His'), $backup->type->value);
        $relative = $directory.'/'.$name;
        $target = $disk->path($relative);
        $work = storage_path('app/backup-work/'.$backup->uuid);
        File::ensureDirectoryExists($work);

        try {
            $zip = new ZipArchive;

            if ($zip->open($target.'.partial', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Arxiv faylini yaratib bo\'lmadi: '.$target);
            }

            $dumper = new DatabaseDumper;
            $files = 0;

            if ($backup->type->includesDatabase()) {
                $sql = $work.'/database.sql';
                $dumper->dump($sql);
                DatabaseDumper::gzip($sql, $sql.'.gz');
                $zip->addFile($sql.'.gz', 'database.sql.gz');
            }

            if ($backup->type->includesFiles()) {
                $files += $this->addDirectory($zip, Storage::disk(MediaUrl::DISK)->path(''), 'files/public');
                $files += $this->addDirectory($zip, Storage::disk('local')->path(''), 'files/private', [$directory, 'backup-work']);
            }

            $zip->addFromString('manifest.json', (string) json_encode([
                'app' => config('app.name'),
                'url' => config('app.url'),
                'type' => $backup->type->value,
                'created_at' => now()->toIso8601String(),
                'database' => $backup->type->includesDatabase() ? $dumper->driver() : null,
                'files' => $files,
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if (! $zip->close()) {
                throw new RuntimeException('Arxivni yopib bo\'lmadi (disk to\'lgan bo\'lishi mumkin).');
            }

            rename($target.'.partial', $target);

            $backup->forceFill([
                'status' => Backup::DONE,
                'path' => $relative,
                'size' => (int) filesize($target),
                'files_count' => $files,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            @unlink($target.'.partial');

            $backup->forceFill([
                'status' => Backup::FAILED,
                'error' => mb_substr($e->getMessage(), 0, 1000),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'finished_at' => now(),
            ])->save();

            report($e);
        } finally {
            File::deleteDirectory($work);
        }

        $this->prune();
    }

    /**
     * Saqlash: oxirgi N ta muvaffaqiyatli arxiv qoladi; 30 kundan eski xato yozuvlar o'chiriladi.
     *
     * @return int o'chirilgan arxivlar soni
     */
    public function prune(): int
    {
        $keep = $this->settings()['keep'];
        $old = Backup::query()
            ->where('status', Backup::DONE)
            ->orderByDesc('id')
            ->skip($keep)
            ->take(1000)
            ->get();

        foreach ($old as $backup) {
            $this->removeFile($backup);
            $backup->delete();
        }

        Backup::query()
            ->where('status', Backup::FAILED)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        return $old->count();
    }

    public function delete(Backup $backup, User $actor): void
    {
        if (in_array($backup->status, [Backup::QUEUED, Backup::RUNNING], true)) {
            throw ValidationException::withMessages([
                'backup' => __("Yaratilayotgan zaxirani o'chirib bo'lmaydi."),
            ]);
        }

        $this->removeFile($backup);
        $backup->delete();

        $this->audit->log(AuditEvent::BackupDeleted, null, ['uuid' => $backup->uuid, 'path' => $backup->path], actor: $actor);
    }

    public function fileExists(Backup $backup): bool
    {
        return $backup->status === Backup::DONE
            && $backup->path !== null
            && Storage::disk($backup->disk)->exists($backup->path);
    }

    /**
     * Jadval bo'yicha zaxira vaqti keldimi: yoqilgan, belgilangan vaqtdan o'tgan
     * va bugun jadval bo'yicha zaxira hali yaratilmagan.
     */
    public function isDue(?CarbonInterface $now = null): bool
    {
        $settings = $this->settings();

        if (! $settings['enabled']) {
            return false;
        }

        $now ??= now();
        [$hour, $minute] = array_map('intval', explode(':', $settings['time']));

        if ($now->lt($now->copy()->setTime($hour, $minute))) {
            return false;
        }

        return ! Backup::query()
            ->where('trigger', 'schedule')
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->exists();
    }

    /**
     * @return array{count: int, totalSize: int, last: array<string, mixed>|null, freeSpace: int|null, nextRun: string|null}
     */
    public function stats(): array
    {
        $last = Backup::query()->where('status', Backup::DONE)->latest('id')->first();
        $free = @disk_free_space($this->disk()->path(''));
        $settings = $this->settings();
        $next = null;

        if ($settings['enabled']) {
            [$hour, $minute] = array_map('intval', explode(':', $settings['time']));
            $candidate = now()->setTime($hour, $minute);
            $next = ($this->isDue() || $candidate->isPast() ? $candidate->addDay() : $candidate)->toIso8601String();
        }

        return [
            'count' => Backup::query()->where('status', Backup::DONE)->count(),
            'totalSize' => (int) Backup::query()->where('status', Backup::DONE)->sum('size'),
            'last' => $last !== null ? ['createdAt' => $last->created_at?->toIso8601String(), 'type' => $last->type->label()] : null,
            'freeSpace' => $free === false ? null : (int) $free,
            'nextRun' => $next,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 50): array
    {
        $this->expireStale();

        return Backup::query()
            ->with('creator:id,name')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Backup $b): array => [
                'uuid' => $b->uuid,
                'type' => $b->type->value,
                'typeLabel' => $b->type->label(),
                'status' => $b->status,
                'trigger' => $b->trigger,
                'size' => $b->size,
                'filesCount' => $b->files_count,
                'durationMs' => $b->duration_ms,
                'error' => $b->error,
                'creator' => $b->creator?->name,
                'createdAt' => $b->created_at?->toIso8601String(),
                'fileName' => $b->path !== null ? basename($b->path) : null,
                'downloadUrl' => $this->fileExists($b) ? route('admin.backups.download', $b->uuid) : null,
                'destroyUrl' => route('admin.backups.destroy', $b->uuid),
            ])
            ->all();
    }

    public function disk(): FilesystemAdapter
    {
        $disk = Storage::disk($this->diskName());

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Zaxira diski lokal fayl tizimi bo\'lishi kerak.');
        }

        return $disk;
    }

    public function diskName(): string
    {
        $disk = config('backup.disk');

        return is_string($disk) && $disk !== '' ? $disk : 'local';
    }

    private function directory(): string
    {
        $directory = config('backup.directory');

        return is_string($directory) && $directory !== '' ? trim($directory, '/') : 'backups';
    }

    private function removeFile(Backup $backup): void
    {
        if ($backup->path !== null) {
            Storage::disk($backup->disk)->delete($backup->path);
        }
    }

    /** Osilib qolgan (server qayta ishga tushgan va h.k.) zaxiralarni xato deb belgilash */
    private function expireStale(): void
    {
        Backup::query()
            ->whereIn('status', [Backup::QUEUED, Backup::RUNNING])
            ->where('created_at', '<', now()->subMinutes(self::STALE_MINUTES))
            ->update(['status' => Backup::FAILED, 'error' => __("Jarayon tugamadi (navbat ishlamayotgan bo'lishi mumkin: php artisan queue:work)."), 'updated_at' => now()]);
    }

    /**
     * @param  array<int, string>  $exclude  ildizga nisbatan chiqarib tashlanadigan papkalar
     */
    private function addDirectory(ZipArchive $zip, string $root, string $prefix, array $exclude = []): int
    {
        $root = rtrim($root, '/\\');

        if (! is_dir($root)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');

            foreach ($exclude as $skip) {
                if ($relative === $skip || str_starts_with($relative, rtrim($skip, '/').'/')) {
                    continue 2;
                }
            }

            if ($relative === '.gitignore') {
                continue;
            }

            $zip->addFile($file->getPathname(), $prefix.'/'.$relative);
            $count++;
        }

        return $count;
    }
}

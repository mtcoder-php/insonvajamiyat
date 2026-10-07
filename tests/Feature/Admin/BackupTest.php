<?php

namespace Tests\Feature\Admin;

use App\Enums\BackupType;
use App\Enums\RoleName;
use App\Models\Backup;
use App\Models\User;
use App\Services\Backup\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

/**
 * Admin → Zaxira nusxa: arxiv tarkibi (baza + fayllar), yuklab olish, o'chirish,
 * saqlash soni va jadval bo'yicha avtomatik zaxira.
 */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->admin = User::factory()->withRole(RoleName::SuperAdmin)->createOne(['name' => 'Zaxira Admin']);
    }

    public function test_full_backup_contains_database_and_files(): void
    {
        UploadedFile::fake()->image('cover.jpg', 300, 400)->storeAs('covers', 'cover.jpg', 'public');
        Storage::disk('local')->put('articles/abc/final.pdf', '%PDF-1.4 test');

        $this->actingAs($this->admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/backups/Index')
                ->has('backups', 0)
                ->has('types', 3)
                ->where('settings.enabled', false)
            );

        $this->actingAs($this->admin)
            ->post(route('admin.backups.store'), ['type' => 'full'])
            ->assertSessionHasNoErrors();

        $backup = Backup::query()->firstOrFail();
        $this->assertSame(Backup::DONE, $backup->status, (string) $backup->error);
        $this->assertNotNull($backup->path);
        $this->assertSame(2, $backup->files_count);
        Storage::disk('local')->assertExists((string) $backup->path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path((string) $backup->path)) === true);
        $names = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = (string) $zip->getNameIndex($i);
        }

        $this->assertContains('manifest.json', $names);
        $this->assertContains('database.sql.gz', $names);
        $this->assertContains('files/public/covers/cover.jpg', $names);
        $this->assertContains('files/private/articles/abc/final.pdf', $names);
        $this->assertEmpty(array_filter($names, fn (string $n): bool => str_starts_with($n, 'files/private/backups/')));

        $sql = (string) gzdecode((string) $zip->getFromName('database.sql.gz'));
        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
        $this->assertStringContainsString('Zaxira Admin', $sql);
        $zip->close();

        $this->assertDatabaseHas('audit_logs', ['event' => 'settings.backup_created']);

        // Yuklab olish va o'chirish
        $this->actingAs($this->admin)
            ->get(route('admin.backups.download', $backup->uuid))
            ->assertOk()
            ->assertDownload(basename((string) $backup->path));
        $this->assertDatabaseHas('audit_logs', ['event' => 'settings.backup_downloaded']);

        $this->actingAs($this->admin)
            ->delete(route('admin.backups.destroy', $backup->uuid))
            ->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing((string) $backup->path);
        $this->assertModelMissing($backup);
    }

    public function test_only_one_backup_runs_at_a_time_and_old_ones_are_pruned(): void
    {
        $running = new Backup;
        $running->forceFill(['type' => BackupType::Database, 'status' => Backup::RUNNING, 'disk' => 'local'])->save();

        $this->actingAs($this->admin)
            ->post(route('admin.backups.store'), ['type' => 'database'])
            ->assertSessionHasErrors('type');

        $running->delete();

        $this->actingAs($this->admin)
            ->put(route('admin.backups.settings'), ['enabled' => false, 'time' => '02:00', 'type' => 'database', 'keep' => 2])
            ->assertSessionHasNoErrors();

        foreach (range(1, 3) as $i) {
            $this->travel(1)->minutes();
            $this->actingAs($this->admin)
                ->post(route('admin.backups.store'), ['type' => 'database'])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Backup::query()->where('status', Backup::DONE)->count());
        $this->assertCount(2, Storage::disk('local')->files('backups'));
    }

    public function test_scheduled_backup_runs_once_per_day_when_enabled(): void
    {
        $service = app(BackupService::class);

        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();
        $this->assertSame(0, Backup::query()->count());

        $service->saveSettings(['enabled' => true, 'time' => '00:00', 'type' => 'database', 'keep' => 7], $this->admin);

        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();
        $this->artisan('backup:run', ['--scheduled' => true])->assertSuccessful();

        $this->assertSame(1, Backup::query()->where('trigger', 'schedule')->count());
        $this->assertSame(Backup::DONE, Backup::query()->firstOrFail()->status);

        // Qo'lda (konsoldan)
        $this->artisan('backup:run', ['--type' => 'files'])->assertSuccessful();
        $this->assertSame(2, Backup::query()->count());

        $this->actingAs(User::factory()->withRole(RoleName::ChiefEditor)->createOne())
            ->get(route('admin.backups.index'))
            ->assertForbidden();
    }
}

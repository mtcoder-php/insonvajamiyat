<?php

namespace App\Services\Issues;

use App\Models\User;
use App\Services\Settings\SettingsStore;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Barcha sonlar uchun umumiy muqova (admin → Jurnallar → «Umumiy muqova»).
 *
 * Odatda bitta tayyor muqova rasmi (PNG/JPG) yuklanadi; son PDF ini yig'ishda u 1-betga to'liq
 * qo'yiladi va ustiga shu sonning raqami, yili va umumiy tartib raqami yoziladi («№ 4 | 2026»,
 * «(40-son)»). Son uchun alohida muqova yuklangan bo'lsa — o'shasi ustun (raqam yozilmaydi).
 * Umumiy muqova ham yuklanmagan bo'lsa — muqova to'liq avtomatik chiziladi (IssuePdfLayout::coverPage).
 */
class IssueCoverService
{
    public const GROUP = 'issues';

    public const DIR = 'issues/covers';

    public const POSITIONS = ['bottom', 'top'];

    public const COLORS = ['gold', 'white', 'navy'];

    public function __construct(private readonly SettingsStore $settings) {}

    /** Yuklangan umumiy muqova yo'li (public disk) */
    public function path(): ?string
    {
        $path = $this->settings->get(self::GROUP, 'cover_path');

        return is_string($path) && $path !== '' && Storage::disk(MediaUrl::DISK)->exists($path) ? $path : null;
    }

    public function binary(): ?string
    {
        $path = $this->path();

        return $path !== null ? Storage::disk(MediaUrl::DISK)->get($path) : null;
    }

    /** Saytdagi son kartochkalari uchun: umumiy muqova yoki config dagi standart rasm */
    public function url(): ?string
    {
        $path = $this->path();

        return $path !== null ? MediaUrl::from($path) : MediaUrl::publicAsset(config('journal.default_issue_cover'));
    }

    public function stamp(): bool
    {
        return (bool) $this->settings->get(self::GROUP, 'cover_stamp', true);
    }

    /** @return 'bottom'|'top' */
    public function position(): string
    {
        return $this->settings->get(self::GROUP, 'cover_stamp_position') === 'top' ? 'top' : 'bottom';
    }

    /** @return 'gold'|'white'|'navy' */
    public function color(): string
    {
        $color = $this->settings->get(self::GROUP, 'cover_stamp_color');

        return match ($color) {
            'white' => 'white',
            'navy' => 'navy',
            default => 'gold',
        };
    }

    /**
     * Admin sahifasi uchun.
     *
     * @return array{url: string|null, custom: bool, name: string|null, size: int|null, stamp: bool, position: string, color: string}
     */
    public function summary(): array
    {
        $path = $this->path();

        return [
            'url' => $this->url(),
            'custom' => $path !== null,
            'name' => $path !== null ? basename($path) : null,
            'size' => $path !== null ? MediaUrl::size($path) : null,
            'stamp' => $this->stamp(),
            'position' => $this->position(),
            'color' => $this->color(),
        ];
    }

    public function upload(UploadedFile $file, User $by): void
    {
        $old = $this->path();
        $extension = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'png'));
        $path = $file->storeAs(self::DIR, 'muqova-'.Str::lower(Str::random(10)).'.'.$extension, MediaUrl::DISK);

        $this->settings->set(self::GROUP, 'cover_path', $path, $by, description: 'Umumiy muqova (son PDF)');

        if ($old !== null && $old !== $path) {
            Storage::disk(MediaUrl::DISK)->delete($old);
        }
    }

    public function remove(): void
    {
        $old = $this->path();
        $this->settings->forget(self::GROUP, 'cover_path');

        if ($old !== null) {
            Storage::disk(MediaUrl::DISK)->delete($old);
        }
    }

    /**
     * @param  'bottom'|'top'  $position
     * @param  'gold'|'white'|'navy'  $color
     */
    public function updateStamp(bool $stamp, string $position, string $color, User $by): void
    {
        $this->settings->set(self::GROUP, 'cover_stamp', $stamp, $by);
        $this->settings->set(self::GROUP, 'cover_stamp_position', $position, $by);
        $this->settings->set(self::GROUP, 'cover_stamp_color', $color, $by);
    }
}

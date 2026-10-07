<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Models\Banner;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\MediaUrl;
use App\Support\Translations;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bosh sahifa slayderi (bannerlar): rasm public diskda banners/ papkasida.
 * Ko'rsatish muddati (starts_at / ends_at) bo'lsa — faqat shu oraliqda chiqadi.
 */
class BannerService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{title: array<string, string>, subtitle: array<string, string>, button_text: array<string, string>, link_url: string|null, is_active: bool, sort_order: int, starts_at: CarbonImmutable|null, ends_at: CarbonImmutable|null}  $data
     */
    public function save(?Banner $banner, array $data, ?UploadedFile $image, User $user): Banner
    {
        $banner ??= new Banner;
        $isNew = ! $banner->exists;

        if ($isNew && $image === null) {
            throw new RuntimeException('Banner image is required.');
        }

        $banner->replaceTranslations('title', $data['title']);
        $banner->replaceTranslations('subtitle', $data['subtitle']);
        $banner->replaceTranslations('button_text', $data['button_text']);
        $banner->forceFill([
            'link_url' => $data['link_url'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
        ]);

        if ($image !== null) {
            $old = $isNew ? null : $banner->image_path;
            $banner->image_path = $this->storeImage($image);

            if ($old !== null && $old !== '' && ! str_starts_with($old, 'http')) {
                Storage::disk(MediaUrl::DISK)->delete($old);
            }
        }

        $banner->save();

        $this->audit->log(AuditEvent::ContentSaved, $banner, [
            'type' => 'banner',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $banner;
    }

    public function delete(Banner $banner, User $user): void
    {
        $path = $banner->image_path;
        $name = $banner->getTranslation('title', 'uz', false);
        $banner->delete();

        if ($path !== '' && ! str_starts_with($path, 'http')) {
            Storage::disk(MediaUrl::DISK)->delete($path);
        }

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'banner', 'name' => $name], actor: $user);
    }

    private function storeImage(UploadedFile $image): string
    {
        $extension = strtolower($image->getClientOriginalExtension()) ?: 'jpg';
        $path = $image->storeAs('banners', 'banner-'.Str::lower(Str::random(10)).'.'.$extension, MediaUrl::DISK);

        if ($path === false) {
            throw new RuntimeException('Banner image could not be stored.');
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{title: array<string, string>, subtitle: array<string, string>, button_text: array<string, string>, link_url: string|null, is_active: bool, sort_order: int, starts_at: CarbonImmutable|null, ends_at: CarbonImmutable|null}
     */
    public static function data(array $input): array
    {
        $link = $input['link_url'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'title' => Translations::clean($input['title'] ?? []),
            'subtitle' => Translations::clean($input['subtitle'] ?? []),
            'button_text' => Translations::clean($input['button_text'] ?? []),
            'link_url' => is_string($link) && trim($link) !== '' ? trim($link) : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
            'starts_at' => self::date($input['starts_at'] ?? null),
            'ends_at' => self::date($input['ends_at'] ?? null, endOfDay: true),
        ];
    }

    private static function date(mixed $value, bool $endOfDay = false): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = CarbonImmutable::parse($value);

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}

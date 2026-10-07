<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Enums\PartnerType;
use App\Models\Partner;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\Translations;
use Illuminate\Http\UploadedFile;

/**
 * Hamkor tashkilotlar va indekslash bazalari (logo public diskda partners/ papkasida).
 */
class PartnerService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{type: PartnerType, name: array<string, string>, subtitle: array<string, string>, url: string|null, is_active: bool, sort_order: int}  $data
     */
    public function save(?Partner $partner, array $data, ?UploadedFile $logo, bool $removeLogo, User $user): Partner
    {
        $partner ??= new Partner;
        $isNew = ! $partner->exists;

        $partner->replaceTranslations('name', $data['name']);
        $partner->replaceTranslations('subtitle', $data['subtitle']);
        $partner->forceFill([
            'type' => $data['type'],
            'url' => $data['url'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
        $partner->logo_path = ContentMedia::replace($partner->logo_path, $logo, $removeLogo, 'partners', 'partner');
        $partner->save();

        $this->audit->log(AuditEvent::ContentSaved, $partner, [
            'type' => 'partner',
            'name' => $data['name']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $partner;
    }

    public function delete(Partner $partner, User $user): void
    {
        $path = $partner->logo_path;
        $name = $partner->getTranslation('name', 'uz', false);
        $partner->delete();
        ContentMedia::delete($path);

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'partner', 'name' => $name], actor: $user);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{type: PartnerType, name: array<string, string>, subtitle: array<string, string>, url: string|null, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $type = $input['type'] ?? null;
        $url = $input['url'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'type' => PartnerType::tryFrom(is_string($type) ? $type : '') ?? PartnerType::Partner,
            'name' => Translations::clean($input['name'] ?? []),
            'subtitle' => Translations::clean($input['subtitle'] ?? []),
            'url' => is_string($url) && trim($url) !== '' ? trim($url) : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }
}

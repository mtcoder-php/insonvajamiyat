<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Enums\EditorialBoardRole;
use App\Models\EditorialBoardMember;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\MediaUrl;
use App\Support\Translations;
use Illuminate\Http\UploadedFile;

/**
 * Tahririyat kengashi ("Jurnal haqida" sahifasi): a'zolar, rasmlar (public diskda board/ papkasi).
 */
class EditorialBoardService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Saytdagi ko'rinish: rollar bo'yicha guruhlangan (bosh muharrir → a'zolar).
     *
     * @return list<array{role: string, label: string, members: list<array<string, mixed>>}>
     */
    public function public(): array
    {
        $members = EditorialBoardMember::query()->where('is_active', true)->ordered()->get();
        $groups = [];

        foreach (EditorialBoardRole::cases() as $role) {
            $items = $members->filter(fn (EditorialBoardMember $m): bool => $m->role === $role)->values();

            if ($items->isEmpty()) {
                continue;
            }

            $groups[] = [
                'role' => $role->value,
                'label' => $role->label(),
                'members' => array_values($items->map(fn (EditorialBoardMember $m): array => [
                    'id' => $m->id,
                    'name' => $m->full_name,
                    'position' => $m->position,
                    'organization' => $m->organization,
                    'degree' => $m->academic_degree,
                    'country' => $m->country,
                    'orcid' => $m->orcid,
                    'photoUrl' => MediaUrl::from($m->photo_path),
                ])->all()),
            ];
        }

        return $groups;
    }

    /**
     * Admin ro'yxati.
     *
     * @return list<array<string, mixed>>
     */
    public function admin(): array
    {
        return array_values(EditorialBoardMember::query()->ordered()->get()
            ->map(fn (EditorialBoardMember $m): array => [
                'id' => $m->id,
                'name' => $m->getTranslation('full_name', 'uz', false) ?: $m->full_name,
                'role' => $m->role->value,
                'roleLabel' => $m->role->label(),
                'translations' => [
                    'full_name' => Translations::form($m->getTranslations('full_name')),
                    'position' => Translations::form($m->getTranslations('position')),
                    'organization' => Translations::form($m->getTranslations('organization')),
                    'academic_degree' => Translations::form($m->getTranslations('academic_degree')),
                ],
                'country' => $m->country,
                'email' => $m->email,
                'orcid' => $m->orcid,
                'photoUrl' => MediaUrl::from($m->photo_path),
                'isActive' => $m->is_active,
                'sortOrder' => $m->sort_order,
                'urls' => [
                    'update' => route('admin.settings.board.update', $m->id),
                    'destroy' => route('admin.settings.board.destroy', $m->id),
                ],
            ])
            ->all());
    }

    /**
     * @param  array{role: EditorialBoardRole, full_name: array<string, string>, position: array<string, string>, organization: array<string, string>, academic_degree: array<string, string>, country: string|null, email: string|null, orcid: string|null, is_active: bool, sort_order: int}  $data
     */
    public function save(?EditorialBoardMember $member, array $data, ?UploadedFile $photo, bool $removePhoto, User $user): EditorialBoardMember
    {
        $member ??= new EditorialBoardMember;
        $isNew = ! $member->exists;

        foreach (['full_name', 'position', 'organization', 'academic_degree'] as $field) {
            $member->replaceTranslations($field, $data[$field]);
        }

        $member->forceFill([
            'role' => $data['role'],
            'country' => $data['country'],
            'email' => $data['email'],
            'orcid' => $data['orcid'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
        $member->photo_path = ContentMedia::replace($member->photo_path, $photo, $removePhoto, 'board', 'member');
        $member->save();

        $this->audit->log(AuditEvent::ContentSaved, null, [
            'type' => 'editorial_board',
            'name' => $data['full_name']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $member;
    }

    public function delete(EditorialBoardMember $member, User $user): void
    {
        $path = $member->photo_path;
        $name = $member->getTranslation('full_name', 'uz', false);
        $member->delete();
        ContentMedia::delete($path);

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'editorial_board', 'name' => $name], actor: $user);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{role: EditorialBoardRole, full_name: array<string, string>, position: array<string, string>, organization: array<string, string>, academic_degree: array<string, string>, country: string|null, email: string|null, orcid: string|null, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $text = fn (string $key): ?string => is_string($input[$key] ?? null) && trim($input[$key]) !== '' ? trim($input[$key]) : null;
        $role = $input['role'] ?? null;
        $sort = $input['sort_order'] ?? 0;
        $country = $text('country');
        $orcid = $text('orcid');

        return [
            'role' => EditorialBoardRole::tryFrom(is_string($role) ? $role : '') ?? EditorialBoardRole::Member,
            'full_name' => Translations::clean($input['full_name'] ?? []),
            'position' => Translations::clean($input['position'] ?? []),
            'organization' => Translations::clean($input['organization'] ?? []),
            'academic_degree' => Translations::clean($input['academic_degree'] ?? []),
            'country' => $country !== null ? strtoupper($country) : null,
            'email' => $text('email'),
            'orcid' => $orcid !== null ? strtoupper($orcid) : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function roles(): array
    {
        return array_map(fn (EditorialBoardRole $r): array => ['value' => $r->value, 'label' => $r->label()], EditorialBoardRole::cases());
    }
}

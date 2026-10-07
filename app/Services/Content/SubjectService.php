<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Models\Subject;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Translations;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ilmiy yo'nalishlar (rukn/fan sohalari): saytdagi filtrlar, maqola yuborish formasi va hisobotlarda ishlatiladi.
 * Maqolasi bor yo'nalish o'chirilmaydi — faolsizlantiriladi (eski maqolalar bog'lanishi saqlanadi).
 */
class SubjectService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: array<string, string>, code: string|null, parent_id: int|null, is_active: bool, sort_order: int}  $data
     */
    public function save(?Subject $subject, array $data, User $user): Subject
    {
        $subject ??= new Subject;
        $isNew = ! $subject->exists;

        if ($data['parent_id'] !== null && $subject->exists && $data['parent_id'] === $subject->id) {
            throw ValidationException::withMessages(['parent_id' => __("Yo'nalish o'ziga bo'ysunishi mumkin emas.")]);
        }

        $subject->replaceTranslations('name', $data['name']);
        $subject->forceFill([
            'code' => $data['code'],
            'parent_id' => $data['parent_id'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);

        if ($isNew) {
            $subject->slug = $this->uniqueSlug($data['name']['uz'] ?? 'yonalish');
        }

        $subject->save();

        $this->audit->log(AuditEvent::ContentSaved, $subject, [
            'type' => 'subject',
            'name' => $data['name']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $subject;
    }

    public function delete(Subject $subject, User $user): void
    {
        if ($subject->articles()->exists()) {
            throw ValidationException::withMessages([
                'subject' => __("Bu yo'nalishda maqolalar bor — o'chirib bo'lmaydi. Uni faolsizlantiring."),
            ]);
        }

        $subject->children()->update(['parent_id' => null]);
        $name = $subject->getTranslation('name', 'uz', false);
        $subject->delete();

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'subject', 'name' => $name], actor: $user);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(Str::ascii($name)) ?: 'yonalish';
        $slug = $base;
        $i = 2;

        while (Subject::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: array<string, string>, code: string|null, parent_id: int|null, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $code = $input['code'] ?? null;
        $parent = $input['parent_id'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'name' => Translations::clean($input['name'] ?? []),
            'code' => is_string($code) && trim($code) !== '' ? trim($code) : null,
            'parent_id' => is_numeric($parent) ? (int) $parent : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }
}

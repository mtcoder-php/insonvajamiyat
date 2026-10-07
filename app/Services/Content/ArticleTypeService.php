<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Models\ArticleType;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Translations;
use Illuminate\Support\Str;

/**
 * Maqola turlari va nashr narxlari (TZ 4.2.8). Narx 0 — bepul (maqola to'lovsiz navbatga tushadi).
 * O'chirish yumshoq (soft delete): eski maqolalar va to'lovlar turi bilan bog'liq qoladi.
 * Narx o'zgarishi alohida audit yozuvi bilan qayd etiladi.
 */
class ArticleTypeService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: array<string, string>, description: array<string, string>, price: float, review_days: int|null, is_active: bool, sort_order: int}  $data
     */
    public function save(?ArticleType $type, array $data, User $user): ArticleType
    {
        $type ??= new ArticleType;
        $isNew = ! $type->exists;
        $oldPrice = $isNew ? null : (float) $type->price;

        $type->replaceTranslations('name', $data['name']);
        $type->replaceTranslations('description', $data['description']);
        $type->forceFill([
            'price' => $data['price'],
            'review_days' => $data['review_days'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);

        if ($isNew) {
            $type->forceFill(['currency' => 'UZS', 'slug' => $this->uniqueSlug($data['name']['uz'] ?? 'maqola-turi')]);
        }

        $type->save();

        $this->audit->log(AuditEvent::ContentSaved, $type, [
            'type' => 'article_type',
            'name' => $data['name']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        if ($oldPrice !== null && abs($oldPrice - $data['price']) > 0.001) {
            $this->audit->log(AuditEvent::PriceChanged, $type, [
                'name' => $data['name']['uz'] ?? null,
                'old' => $oldPrice,
                'new' => $data['price'],
            ], actor: $user);
        }

        return $type;
    }

    public function delete(ArticleType $type, User $user): void
    {
        $name = $type->getTranslation('name', 'uz', false);
        $type->delete();

        $this->audit->log(AuditEvent::ContentDeleted, $type, ['type' => 'article_type', 'name' => $name], actor: $user);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(Str::ascii($name), '_') ?: 'maqola_turi';
        $slug = $base;
        $i = 2;

        while (ArticleType::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: array<string, string>, description: array<string, string>, price: float, review_days: int|null, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $price = $input['price'] ?? 0;
        $days = $input['review_days'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'name' => Translations::clean($input['name'] ?? []),
            'description' => Translations::clean($input['description'] ?? []),
            'price' => is_numeric($price) ? round((float) $price, 2) : 0.0,
            'review_days' => is_numeric($days) ? (int) $days : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }
}

<?php

namespace App\Services\Content;

use App\Models\ArticleType;
use App\Models\Banner;
use App\Models\Subject;
use App\Support\MediaUrl;
use App\Support\Translations;

/**
 * Admin → Sozlamalar sahifasi ma'lumotlari (yo'nalishlar, maqola turlari va narxlar, bannerlar).
 */
class SettingsWorkspace
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function subjects(): array
    {
        return Subject::query()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Subject $s): array => [
                'id' => $s->id,
                'name' => $s->getTranslation('name', 'uz', false) ?: $s->name,
                'translations' => Translations::form($s->getTranslations('name')),
                'slug' => $s->slug,
                'code' => $s->code,
                'parentId' => $s->parent_id,
                'isActive' => $s->is_active,
                'sortOrder' => $s->sort_order,
                'articlesCount' => (int) $s->getAttribute('articles_count'),
                'urls' => [
                    'update' => route('admin.settings.subjects.update', $s->id),
                    'destroy' => route('admin.settings.subjects.destroy', $s->id),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function articleTypes(): array
    {
        return ArticleType::query()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ArticleType $t): array => [
                'id' => $t->id,
                'name' => $t->getTranslation('name', 'uz', false) ?: $t->name,
                'translations' => [
                    'name' => Translations::form($t->getTranslations('name')),
                    'description' => Translations::form($t->getTranslations('description')),
                ],
                'slug' => $t->slug,
                'price' => (float) $t->price,
                'currency' => $t->currency,
                'reviewDays' => $t->review_days,
                'isActive' => $t->is_active,
                'sortOrder' => $t->sort_order,
                'articlesCount' => (int) $t->getAttribute('articles_count'),
                'urls' => [
                    'update' => route('admin.settings.types.update', $t->id),
                    'destroy' => route('admin.settings.types.destroy', $t->id),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        $now = now();

        return Banner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Banner $b): array => [
                'id' => $b->id,
                'title' => $b->getTranslation('title', 'uz', false) ?: $b->title,
                'translations' => [
                    'title' => Translations::form($b->getTranslations('title')),
                    'subtitle' => Translations::form($b->getTranslations('subtitle')),
                    'button_text' => Translations::form($b->getTranslations('button_text')),
                ],
                'imageUrl' => MediaUrl::from($b->image_path),
                'linkUrl' => $b->link_url,
                'isActive' => $b->is_active,
                'sortOrder' => $b->sort_order,
                'startsAt' => $b->starts_at?->toDateString(),
                'endsAt' => $b->ends_at?->toDateString(),
                'visible' => $b->is_active
                    && ($b->starts_at === null || $b->starts_at->lte($now))
                    && ($b->ends_at === null || $b->ends_at->gte($now)),
                'urls' => [
                    'update' => route('admin.settings.banners.update', $b->id),
                    'destroy' => route('admin.settings.banners.destroy', $b->id),
                ],
            ])
            ->all();
    }
}

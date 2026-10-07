<?php

namespace App\Services\Content;

use App\Enums\PartnerType;
use App\Enums\PostType;
use App\Models\ArticleType;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Post;
use App\Models\RecommendedBook;
use App\Models\Subject;
use App\Support\MediaUrl;
use App\Support\Translations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin → Sozlamalar sahifasi ma'lumotlari: yo'nalishlar, maqola turlari va narxlar, bannerlar,
 * yangiliklar, tadbirlar, tavsiya etilgan kitoblar va hamkorlar.
 */
class SettingsWorkspace
{
    public const PER_PAGE = 12;

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

    /**
     * Ro'yxat filtrlari (?type, ?when, ?q, ?page) — faqat ruxsat etilgan qiymatlar.
     *
     * @param  array<string, mixed>  $input
     * @return array{type: string, when: string, q: string}
     */
    public static function filters(array $input): array
    {
        $type = $input['type'] ?? '';
        $when = $input['when'] ?? '';
        $q = $input['q'] ?? '';

        return [
            'type' => is_string($type) && PostType::tryFrom($type) !== null ? $type : '',
            'when' => is_string($when) && in_array($when, ['upcoming', 'past'], true) ? $when : '',
            'q' => is_string($q) ? mb_substr(trim($q), 0, 100) : '',
        ];
    }

    /**
     * Yangiliklar va e'lonlar (yangilari birinchi, sahifalangan).
     *
     * @param  array{type: string, when: string, q: string}  $filters
     * @return array<string, mixed>
     */
    public function posts(array $filters, int $page = 1): array
    {
        $now = now();
        $query = Post::query()->with('author:id,name');
        $type = PostType::tryFrom($filters['type']);

        if ($type !== null) {
            $query->ofType($type);
        }

        $this->search($query, ['title', 'excerpt'], $filters['q']);

        $paginator = $query
            ->orderByDesc('is_pinned')
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, page: $page);

        $counts = Post::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return [
            'data' => array_map(fn (Post $p): array => [
                'id' => $p->id,
                'type' => $p->type->value,
                'slug' => $p->slug,
                'title' => $p->getTranslation('title', 'uz', false) ?: $p->title,
                'translations' => [
                    'title' => Translations::form($p->getTranslations('title')),
                    'excerpt' => Translations::form($p->getTranslations('excerpt')),
                    'body' => Translations::form($p->getTranslations('body')),
                ],
                'imageUrl' => MediaUrl::from($p->image_path),
                'isPublished' => $p->is_published,
                'isPinned' => $p->is_pinned,
                'publishedAt' => $p->published_at?->format('Y-m-d\TH:i'),
                'status' => ! $p->is_published ? 'draft' : ($p->published_at !== null && $p->published_at->gt($now) ? 'scheduled' : 'published'),
                'author' => $p->author?->name,
                'url' => $p->is_published ? route('news.show', $p->slug) : null,
                'urls' => [
                    'update' => route('admin.settings.posts.update', $p->id),
                    'destroy' => route('admin.settings.posts.destroy', $p->id),
                ],
            ], $paginator->items()),
            'meta' => $this->meta($paginator),
            'counts' => [
                'all' => (int) $counts->sum(),
                'news' => (int) ($counts[PostType::News->value] ?? 0),
                'announcement' => (int) ($counts[PostType::Announcement->value] ?? 0),
            ],
        ];
    }

    /**
     * Tadbirlar: kelgusi (eng yaqini birinchi) yoki o'tgan (oxirgisi birinchi).
     *
     * @param  array{type: string, when: string, q: string}  $filters
     * @return array<string, mixed>
     */
    public function events(array $filters, int $page = 1): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $query = Event::query();

        if ($filters['when'] === 'upcoming') {
            $query->upcoming();
        } elseif ($filters['when'] === 'past') {
            $query->where('starts_at', '<', $today)
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '<', $today))
                ->orderByDesc('starts_at');
        } else {
            $query->orderByDesc('starts_at');
        }

        $this->search($query, ['title', 'location'], $filters['q']);
        $paginator = $query->orderByDesc('id')->paginate(self::PER_PAGE, page: $page);

        return [
            'data' => array_map(function (Event $e) use ($today): array {
                $end = $e->ends_at ?? $e->starts_at;

                return [
                    'id' => $e->id,
                    'slug' => $e->slug,
                    'title' => $e->getTranslation('title', 'uz', false) ?: $e->title,
                    'translations' => [
                        'title' => Translations::form($e->getTranslations('title')),
                        'description' => Translations::form($e->getTranslations('description')),
                        'location' => Translations::form($e->getTranslations('location')),
                    ],
                    'location' => $e->getTranslation('location', 'uz', false) ?: null,
                    'startsAt' => $e->starts_at->format('Y-m-d\TH:i'),
                    'endsAt' => $e->ends_at?->format('Y-m-d\TH:i'),
                    'registrationUrl' => $e->registration_url,
                    'imageUrl' => MediaUrl::from($e->image_path),
                    'isPublished' => $e->is_published,
                    'isPast' => $end->lt($today),
                    'url' => $e->is_published ? route('events.show', $e->slug) : null,
                    'urls' => [
                        'update' => route('admin.settings.events.update', $e->id),
                        'destroy' => route('admin.settings.events.destroy', $e->id),
                    ],
                ];
            }, $paginator->items()),
            'meta' => $this->meta($paginator),
            'counts' => [
                'all' => Event::query()->count(),
                'upcoming' => Event::query()->upcoming()->count(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function books(): array
    {
        return RecommendedBook::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RecommendedBook $b): array => [
                'id' => $b->id,
                'title' => $b->getTranslation('title', 'uz', false) ?: $b->title,
                'translations' => ['title' => Translations::form($b->getTranslations('title'))],
                'author' => $b->author,
                'year' => $b->year,
                'url' => $b->url,
                'coverUrl' => MediaUrl::from($b->cover_image_path),
                'isActive' => $b->is_active,
                'sortOrder' => $b->sort_order,
                'urls' => [
                    'update' => route('admin.settings.books.update', $b->id),
                    'destroy' => route('admin.settings.books.destroy', $b->id),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function partners(): array
    {
        return Partner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Partner $p): array => [
                'id' => $p->id,
                'type' => $p->type->value,
                'name' => $p->getTranslation('name', 'uz', false) ?: $p->name,
                'translations' => [
                    'name' => Translations::form($p->getTranslations('name')),
                    'subtitle' => Translations::form($p->getTranslations('subtitle')),
                ],
                'url' => $p->url,
                'logoUrl' => MediaUrl::from($p->logo_path),
                'isActive' => $p->is_active,
                'sortOrder' => $p->sort_order,
                'urls' => [
                    'update' => route('admin.settings.partners.update', $p->id),
                    'destroy' => route('admin.settings.partners.destroy', $p->id),
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function partnerTypes(): array
    {
        return array_map(fn (PartnerType $t): array => ['value' => $t->value, 'label' => $t->label()], PartnerType::cases());
    }

    /**
     * Tarjima qilinadigan maydonlar bo'yicha qidiruv (uz / ru / en), registrdan qat'i nazar
     * (MySQL'da JSON qiymati utf8mb4_bin — shuning uchun lower()).
     * SQL faqat kod ichidagi literal maydon/til nomlaridan yig'iladi; qidiruv so'zi — parametr.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<'title'|'excerpt'|'location'>  $fields
     */
    private function search(Builder $query, array $fields, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = '%'.mb_strtolower($term).'%';
        $driver = $query->getConnection()->getDriverName();

        $query->where(function ($q) use ($fields, $like, $driver) {
            foreach ($fields as $field) {
                foreach (['uz', 'ru', 'en'] as $locale) {
                    $path = '\'$."'.$locale.'"\'';
                    $sql = match ($driver) {
                        'mysql', 'mariadb' => 'lower(json_unquote(json_extract(`'.$field.'`, '.$path.'))) like ?',
                        'pgsql' => 'lower("'.$field.'"->>\''.$locale.'\') like ?',
                        default => 'lower(json_extract("'.$field.'", '.$path.')) like ?',
                    };

                    $q->orWhereRaw($sql, [$like]);
                }
            }
        });
    }

    /**
     * @param  LengthAwarePaginator<int, *>  $paginator
     * @return array{currentPage: int, lastPage: int, total: int, from: int|null, to: int|null}
     */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}

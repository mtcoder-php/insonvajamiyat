<?php

namespace Database\Seeders;

use App\Models\ArticleType;
use Illuminate\Database\Seeder;

/**
 * Maqola turlari (TZ 4.1.3: ilmiy maqola, tezis, sharh-maqola, tezkor nashr).
 * Mavjud yozuvlar slug bo'yicha topiladi va o'zgartirilmaydi — narx va tavsifni
 * admin panel boshqaradi. Yangi turlar narxsiz (0) yaratiladi.
 *
 *   php artisan db:seed --class=ArticleTypeSeeder
 */
class ArticleTypeSeeder extends Seeder
{
    /** @var array<int, array{slug: string, uz: string, ru: string, en: string, description: string, review_days: int}> */
    public const TYPES = [
        [
            'slug' => 'scientific_article', 'uz' => 'Ilmiy maqola', 'ru' => 'Научная статья', 'en' => 'Research article',
            'description' => "To'liq hajmli ilmiy tadqiqot natijalari (odatda 8–20 bet).", 'review_days' => 30,
        ],
        [
            'slug' => 'review_article', 'uz' => 'Sharh-maqola', 'ru' => 'Обзорная статья', 'en' => 'Review article',
            'description' => 'Muayyan mavzu bo\'yicha mavjud tadqiqotlarning tahliliy sharhi.', 'review_days' => 30,
        ],
        [
            'slug' => 'thesis', 'uz' => 'Tezis', 'ru' => 'Тезисы', 'en' => 'Abstract (thesis)',
            'description' => 'Qisqa hajmli ilmiy xabar yoki konferensiya tezisi (2–4 bet).', 'review_days' => 15,
        ],
        [
            'slug' => 'express', 'uz' => 'Tezkor nashr', 'ru' => 'Ускоренная публикация', 'en' => 'Fast-track publication',
            'description' => "Ilmiy maqola — qisqartirilgan muddatda ko'rib chiqiladi.", 'review_days' => 7,
        ],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $index => $type) {
            ArticleType::query()->firstOrCreate(['slug' => $type['slug']], [
                'name' => ['uz' => $type['uz'], 'ru' => $type['ru'], 'en' => $type['en']],
                'description' => ['uz' => $type['description']],
                'price' => 0,
                'review_days' => $type['review_days'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}

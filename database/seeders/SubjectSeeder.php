<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Jurnalning asosiy ilmiy yo'nalishlari (TZ). Qayta ishga tushirish xavfsiz:
 * mavjud yozuvlar slug bo'yicha yangilanadi, admin qo'shganlari o'zgarmaydi.
 */
class SubjectSeeder extends Seeder
{
    /** @var array<int, array{slug: string, uz: string, ru: string, en: string}> */
    public const SUBJECTS = [
        ['slug' => 'history', 'uz' => 'Tarix', 'ru' => 'История', 'en' => 'History'],
        ['slug' => 'ethnology', 'uz' => 'Etnologiya', 'ru' => 'Этнология', 'en' => 'Ethnology'],
        ['slug' => 'ethnography', 'uz' => 'Etnografiya', 'ru' => 'Этнография', 'en' => 'Ethnography'],
        ['slug' => 'anthropology', 'uz' => 'Antropologiya', 'ru' => 'Антропология', 'en' => 'Anthropology'],
        ['slug' => 'philosophy', 'uz' => 'Falsafa', 'ru' => 'Философия', 'en' => 'Philosophy'],
        ['slug' => 'philology', 'uz' => 'Filologiya', 'ru' => 'Филология', 'en' => 'Philology'],
        ['slug' => 'sociology', 'uz' => 'Sotsiologiya', 'ru' => 'Социология', 'en' => 'Sociology'],
        ['slug' => 'cultural-studies', 'uz' => 'Madaniyatshunoslik', 'ru' => 'Культурология', 'en' => 'Cultural studies'],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $index => $subject) {
            Subject::query()->updateOrCreate(
                ['slug' => $subject['slug']],
                [
                    'name' => ['uz' => $subject['uz'], 'ru' => $subject['ru'], 'en' => $subject['en']],
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }
}

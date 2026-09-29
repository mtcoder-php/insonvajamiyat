<?php

/*
|--------------------------------------------------------------------------
| Jurnal ma'lumotlari
|--------------------------------------------------------------------------
| Sayt header/footer, "Jurnal haqida" va SEO uchun asosiy rekvizitlar.
| Qiymatlar .env orqali o'zgartiriladi; keyinchalik admin paneldagi
| "Sozlamalar" bo'limi settings jadvalidan ustun qo'yadi.
*/

return [

    'name' => env('JOURNAL_NAME', 'Inson va Jamiyat'),

    'subtitle' => env('JOURNAL_SUBTITLE', 'Scientific Journal'),

    'description' => env(
        'JOURNAL_DESCRIPTION',
        'Tarix, etnologiya, antropologiya va falsafaga doir ilmiy-tadqiqotlar jurnali',
    ),

    'issn' => env('JOURNAL_ISSN'),              // bosma nashr (print)

    'eissn' => env('JOURNAL_EISSN'),            // elektron nashr (online)

    'doi_prefix' => env('JOURNAL_DOI_PREFIX'),  // masalan 10.5281/zenodo

    'frequency' => env('JOURNAL_FREQUENCY', 'Yiliga 4 marta (kvartal)'),

    // Bosh sahifa dizayni: 'modern' (home_2.png) yoki 'classic' (home.png).
    // Vaqtincha ?variant=classic|modern orqali ham tanlash mumkin.
    'home_variant' => env('JOURNAL_HOME_VARIANT', 'modern'),

    'contact' => [
        'email' => env('JOURNAL_EMAIL', 'info@insonvajamiyat.uz'),
        'phone' => env('JOURNAL_PHONE', '+998 71 234 56 78'),
        'address' => env('JOURNAL_ADDRESS', "Toshkent, O'zbekiston"),
    ],

    // Bo'sh qoldirilgan tarmoqlar footer'da ko'rsatilmaydi
    'socials' => [
        'telegram' => env('JOURNAL_TELEGRAM_URL'),
        'facebook' => env('JOURNAL_FACEBOOK_URL'),
        'instagram' => env('JOURNAL_INSTAGRAM_URL'),
        'youtube' => env('JOURNAL_YOUTUBE_URL'),
        'linkedin' => env('JOURNAL_LINKEDIN_URL'),
    ],

];

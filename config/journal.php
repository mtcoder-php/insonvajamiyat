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

    /*
    | Bosh sahifa slayderining standart slaydlari.
    | Admin paneldagi "Bannerlar" bo'limida faol banner bo'lsa, o'shalar
    | ishlatiladi. Rasm public/ ichidagi yo'l; fayl hali yo'q bo'lsa slayd
    | brend fonida chiqadi (tavsiya: 1920×720, .webp yoki .jpg).
    */
    'hero_slides' => [
        [
            'title' => 'Insonni anglash — jamiyatni anglashdir.',
            'subtitle' => 'Understanding Humanity, Understanding Society.',
            'image' => 'images/hero/slide-1.webp',
            'button_text' => "Maqolalarni ko'rish",
            'route' => 'articles.index',
        ],
        [
            'title' => 'Navbatdagi son uchun maqolalar qabul qilinmoqda',
            'subtitle' => "Tarix, etnologiya, antropologiya va falsafa bo'yicha original tadqiqotlaringizni yuboring.",
            'image' => 'images/hero/slide-2.webp',
            'button_text' => 'Maqola yuborish',
            'route' => 'register',
        ],
        [
            'title' => 'Ochiq kirish va xalqaro standartlar',
            'subtitle' => 'Har bir maqola yashirin taqriz asosida baholanadi va DOI bilan ochiq nashr etiladi.',
            'image' => 'images/hero/slide-3.webp',
            'button_text' => "Mualliflar uchun yo'riqnoma",
            'route' => 'guidelines',
        ],
    ],

];

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

    // Nashr oldidan tekshiruv: plagiat (o'xshashlik) foizining ruxsat etilgan chegarasi
    'plagiarism_max' => (float) env('JOURNAL_PLAGIARISM_MAX', 20),

    'frequency' => env('JOURNAL_FREQUENCY', 'Yiliga 4 marta (kvartal)'),

    // Jurnal soniga alohida muqova yuklanmagan bo'lsa ishlatiladigan umumiy muqova
    // (public/ ichidagi yo'l). Fayl topilmasa — muqova avtomatik chiziladi.
    'default_issue_cover' => env('JOURNAL_DEFAULT_ISSUE_COVER', 'coverimg/cover.png'),

    // Bosh sahifadagi "So'nggi son" bloki uchun muqova (songa alohida muqova
    // yuklanmagan bo'lsa). Fayl topilmasa — default_issue_cover ishlatiladi.
    'latest_issue_cover' => env('JOURNAL_LATEST_ISSUE_COVER', 'web/latest_issue/latest_issue.png'),

    // Ichki sahifalar sarlavhasi (hero) rasmlari — public/ ichidagi yo'l.
    // O'z rasmingizni qo'ying (masalan, web/heroes/catalog.jpg) va .env da ko'rsating.
    'heroes' => [
        'catalog' => env('JOURNAL_HERO_CATALOG', 'sliders/slide2.png'),
        'issues' => env('JOURNAL_HERO_ISSUES', 'sliders/slide1.png'),
    ],

    // Muallif kabineti: "Maqola shablonini yuklab olish" (public/ ichidagi yo'l).
    // Fayl topilmasa tugma ko'rsatilmaydi.
    'article_template' => env('JOURNAL_ARTICLE_TEMPLATE', 'downloads/maqola-shablon.docx'),

    'contact' => [
        'email' => env('JOURNAL_EMAIL', 'info@insonvajamiyat.uz'),
        'phone' => env('JOURNAL_PHONE', '+998 71 234 56 78'),
        'address' => env('JOURNAL_ADDRESS', "Toshkent, O'zbekiston"),
    ],

    // Nashr to'lovi uchun bank rekvizitlari (muallif kabinetida "To'lov kutilmoqda" holatida
    // ko'rsatiladi; to'lovni admin qo'lda tasdiqlaydi). Bo'sh maydonlar ko'rsatilmaydi.
    'payment' => [
        'recipient' => env('JOURNAL_PAYMENT_RECIPIENT'),   // Qabul qiluvchi tashkilot
        'bank' => env('JOURNAL_PAYMENT_BANK'),             // Bank nomi
        'account' => env('JOURNAL_PAYMENT_ACCOUNT'),       // Hisob raqami
        'mfo' => env('JOURNAL_PAYMENT_MFO'),               // MFO
        'inn' => env('JOURNAL_PAYMENT_INN'),               // STIR (INN)
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
    | ishlatiladi. Rasm public/ ichidagi yo'l (public/sliders/slide1.png ...);
    | fayl yo'q bo'lsa slayd brend fonida chiqadi (tavsiya: 1920×720).
    */
    'hero_slides' => [
        [
            'title' => 'Insonni anglash — jamiyatni anglashdir.',
            'subtitle' => 'Understanding Humanity, Understanding Society.',
            'image' => 'sliders/slide1.png',
            'button_text' => "Maqolalarni ko'rish",
            'route' => 'articles.index',
        ],
        [
            'title' => 'Navbatdagi son uchun maqolalar qabul qilinmoqda',
            'subtitle' => "Tarix, etnologiya, antropologiya va falsafa bo'yicha original tadqiqotlaringizni yuboring.",
            'image' => 'sliders/slide2.png',
            'button_text' => 'Maqola yuborish',
            'route' => 'register',
        ],
        [
            'title' => 'Ochiq kirish va xalqaro standartlar',
            'subtitle' => 'Har bir maqola yashirin taqriz asosida baholanadi va DOI bilan ochiq nashr etiladi.',
            'image' => 'sliders/slide3.png',
            'button_text' => "Mualliflar uchun yo'riqnoma",
            'route' => 'guidelines',
        ],
    ],

    // Korrektura (muallif yakuniy PDF ni tekshiradi): javob muddati va eslatma.
    // Muddat o'tgach bosh muharrir sababini yozib muallifsiz tasdiqlashi mumkin.
    'proof' => [
        'deadline_days' => (int) env('JOURNAL_PROOF_DAYS', 5),
        'reminder_hours' => (int) env('JOURNAL_PROOF_REMINDER_HOURS', 24),
    ],

    // Audit log: yozuvlar shuncha kun saqlanadi, keyin `php artisan model:prune` o'chiradi
    'audit' => [
        'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
    ],

];

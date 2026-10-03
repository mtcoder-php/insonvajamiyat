<?php

/*
|--------------------------------------------------------------------------
| AI Studio — Anthropic Claude API (Proofreader, Translator, Analytics)
|--------------------------------------------------------------------------
| Bu yerdagi qiymatlar standart. Super Admin ularni admin panel → AI Studio →
| Sozlamalar orqali o'zgartiradi (settings jadvali, "ai" guruhi; API kaliti shifrlanadi).
| Bazada qiymat bo'lmasa — .env dagi qiymat ishlatiladi.
*/

return [

    'enabled' => (bool) env('AI_ENABLED', true),

    'api_key' => env('ANTHROPIC_API_KEY'),

    // Masalan: Anthropic konsolida ko'rsatilgan model identifikatori
    'model' => env('ANTHROPIC_MODEL'),

    'api_url' => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages'),
    'api_version' => '2023-06-01',
    'timeout' => (int) env('AI_TIMEOUT', 120),

    // Oylik token limiti (kiruvchi + chiquvchi). Foydalanuvchiga alohida limit
    // users.ai_monthly_token_limit da (null — rol bo'yicha standart). 0 — cheklanmagan.
    'limits' => [
        'author' => (int) env('AI_AUTHOR_MONTHLY_TOKENS', 50_000),
        'staff' => (int) env('AI_STAFF_MONTHLY_TOKENS', 200_000),
    ],

    // Bitta so'rovdagi matn uzunligi (belgi) va bo'lak hajmi
    'max_input_chars' => (int) env('AI_MAX_INPUT_CHARS', 30_000),
    'chunk_chars' => 5_000,

    // Tahlil (Analytics) uchun matnning boshidan olinadigan qism
    'analysis_chars' => 12_000,

    // Narx (USD, 1 mln token uchun) — xarajatni hisoblash uchun; 0 bo'lsa hisoblanmaydi
    'pricing' => [
        'input_per_mtok' => (float) env('AI_PRICE_INPUT_PER_MTOK', 0),
        'output_per_mtok' => (float) env('AI_PRICE_OUTPUT_PER_MTOK', 0),
    ],

    // Navbat (QUEUE_CONNECTION=database bo'lsa `php artisan queue:work` ishlab turishi kerak)
    'queue' => env('AI_QUEUE', 'default'),

];

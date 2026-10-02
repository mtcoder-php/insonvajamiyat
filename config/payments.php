<?php

/*
|--------------------------------------------------------------------------
| Onlayn to'lov tizimlari — Click (SHOP API) va Payme (Merchant API)
|--------------------------------------------------------------------------
| Kalitlar shartnoma tuzilgach kabinetlardan olinadi va faqat .env da saqlanadi.
|
| Click kabinetida (merchant.click.uz → Xizmatlar) ko'rsatiladigan manzillar:
|   Prepare URL:  {APP_URL}/payments/click/prepare
|   Complete URL: {APP_URL}/payments/click/complete
|
| Payme biznes kabinetida (merchant.paycom.uz → Kassa → Sozlamalar):
|   Endpoint URL: {APP_URL}/payments/payme
|   Hisob (account) maydoni: payment_id
*/

return [

    // To'lov sahifasidan qaytgandan keyin holatni avtomatik tekshirish davomiyligi (soniya)
    'return_poll_seconds' => (int) env('PAYMENTS_RETURN_POLL_SECONDS', 120),

    'click' => [
        'enabled' => (bool) env('CLICK_ENABLED', false),
        'service_id' => env('CLICK_SERVICE_ID'),
        'merchant_id' => env('CLICK_MERCHANT_ID'),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID'),
        'secret_key' => env('CLICK_SECRET_KEY'),
        'checkout_url' => env('CLICK_CHECKOUT_URL', 'https://my.click.uz/services/pay'),
    ],

    'payme' => [
        'enabled' => (bool) env('PAYME_ENABLED', false),
        'merchant_id' => env('PAYME_MERCHANT_ID'),
        // Test rejimida kabinetdagi "test kaliti", ishchi rejimda asosiy kalit
        'key' => env('PAYME_KEY'),
        'test_mode' => (bool) env('PAYME_TEST_MODE', true),
        'checkout_url' => env('PAYME_CHECKOUT_URL', 'https://checkout.paycom.uz'),
        'test_checkout_url' => env('PAYME_TEST_CHECKOUT_URL', 'https://checkout.test.paycom.uz'),
        'login' => 'Paycom',
        // Hisob maydoni nomi (kabinetdagi "account" sozlamasi bilan bir xil bo'lishi shart)
        'account_key' => env('PAYME_ACCOUNT_KEY', 'payment_id'),
        // Tranzaksiya amal qilish muddati — Payme talabi 12 soat (ms)
        'timeout_ms' => 43_200_000,
        // Elektron chek (fiskalizatsiya): MXIK kodi va qadoq kodi — tax.soliq.uz/classifier
        'fiscal' => [
            'ikpu' => env('PAYME_IKPU'),
            'package_code' => env('PAYME_PACKAGE_CODE'),
            'vat_percent' => (int) env('PAYME_VAT_PERCENT', 0),
        ],
    ],

];

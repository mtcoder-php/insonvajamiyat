<?php

use App\Enums\PartnerType;
use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;

/**
 * Oldin demo sifatida qo'shilgan hamkor tashkilotlarga sayt manzili berilmagan edi —
 * saytdagi kartalar havolasiz chiqardi. Faqat manzili bo'sh va nomi aynan mos yozuvlar
 * to'ldiriladi (admin kiritgan manzillarga tegilmaydi).
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const URLS = [
        "O'zbekiston Respublikasi Oliy ta'lim, fan va innovatsiyalar vazirligi" => 'https://edu.uz',
        'Yangi Asr universiteti' => 'https://yangiasr.uz',
        "O'zbekiston Milliy universiteti" => 'https://nuu.uz',
        'Toshkent davlat pedagogika universiteti' => 'https://tdpu.uz',
    ];

    public function up(): void
    {
        Partner::query()
            ->where('type', PartnerType::Partner->value)
            ->where(fn ($query) => $query->whereNull('url')->orWhere('url', ''))
            ->get()
            ->each(function (Partner $partner): void {
                $url = self::URLS[$partner->getTranslation('name', 'uz', false)] ?? null;

                if ($url !== null) {
                    $partner->forceFill(['url' => $url])->save();
                }
            });
    }

    public function down(): void
    {
        // Ma'lumot migratsiyasi — qaytarilmaydi
    }
};

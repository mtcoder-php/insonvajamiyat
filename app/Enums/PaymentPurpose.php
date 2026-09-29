<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * payments.purpose — to'lov nima uchun.
 * Idempotency: bitta maqolaga faqat bitta muvaffaqiyatli "publication" to'lovi.
 */
enum PaymentPurpose: string
{
    use EnumHelpers;

    case Publication = 'publication';   // Maqola nashri (+ addonlar: tezkor, qo'shimcha sahifa)
    case ArticleAddon = 'article_addon'; // Nashr to'lovidan keyin alohida olingan addon
    case Service = 'service';           // Mustaqil xizmat (tarjima, AI)
    case IssuePurchase = 'issue_purchase'; // Jurnal sonini sotib olish

    public function label(): string
    {
        return match ($this) {
            self::Publication => __('Maqola nashri'),
            self::ArticleAddon => __("Maqola uchun qo'shimcha xizmat"),
            self::Service => __('Xizmat'),
            self::IssuePurchase => __("Jurnal to'plami"),
        };
    }
}

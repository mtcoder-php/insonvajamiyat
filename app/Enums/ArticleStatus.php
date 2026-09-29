<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Maqolaning hayotiy sikli (TZ 4.1.3).
 *
 * Oqim:
 *   draft → submitted → awaiting_payment → under_review → in_review
 *        → revision_required → resubmitted → ... → accepted → in_production → published
 *   Istalgan ko'rib chiqish bosqichidan → rejected
 *   Muallif nashrgacha → withdrawn
 */
enum ArticleStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';                         // Qoralama (hali yuborilmagan)
    case Submitted = 'submitted';                 // Yuborildi
    case AwaitingPayment = 'awaiting_payment';    // To'lov kutilmoqda
    case UnderReview = 'under_review';            // Ko'rib chiqilmoqda (muharrir navbatida)
    case InReview = 'in_review';                  // Taqrizda
    case RevisionRequired = 'revision_required';  // Tuzatish talab etiladi
    case Resubmitted = 'resubmitted';             // Tuzatilgan holda qayta yuborildi
    case Accepted = 'accepted';                   // Qabul qilindi
    case InProduction = 'in_production';          // Maketlash (Layout/Publisher)
    case Published = 'published';                 // Nashr etildi
    case Rejected = 'rejected';                   // Rad etildi
    case Withdrawn = 'withdrawn';                 // Muallif tomonidan qaytarib olindi

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Qoralama'),
            self::Submitted => __('Yuborildi'),
            self::AwaitingPayment => __("To'lov kutilmoqda"),
            self::UnderReview => __("Ko'rib chiqilmoqda"),
            self::InReview => __('Taqrizda'),
            self::RevisionRequired => __('Tuzatish talab etiladi'),
            self::Resubmitted => __('Qayta yuborildi'),
            self::Accepted => __('Qabul qilindi'),
            self::InProduction => __('Nashrga tayyorlanmoqda'),
            self::Published => __('Nashr etildi'),
            self::Rejected => __('Rad etildi'),
            self::Withdrawn => __('Qaytarib olindi'),
        };
    }

    /** Tailwind rang nomi — badge komponenti uchun */
    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn => 'gray',
            self::Submitted, self::Resubmitted => 'blue',
            self::AwaitingPayment => 'amber',
            self::UnderReview, self::InReview => 'indigo',
            self::RevisionRequired => 'orange',
            self::Accepted, self::InProduction => 'teal',
            self::Published => 'green',
            self::Rejected => 'red',
        };
    }

    /**
     * Ruxsat etilgan o'tishlar (state machine).
     *
     * @return array<int, self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Withdrawn],
            self::Submitted => [self::AwaitingPayment, self::UnderReview, self::Withdrawn],
            self::AwaitingPayment => [self::UnderReview, self::Withdrawn],
            self::UnderReview => [self::InReview, self::RevisionRequired, self::Accepted, self::Rejected],
            self::InReview => [self::UnderReview, self::RevisionRequired, self::Accepted, self::Rejected],
            self::RevisionRequired => [self::Resubmitted, self::Rejected, self::Withdrawn],
            self::Resubmitted => [self::UnderReview, self::InReview, self::RevisionRequired, self::Accepted, self::Rejected],
            self::Accepted => [self::InProduction, self::Published],
            self::InProduction => [self::Accepted, self::Published],
            self::Published, self::Rejected, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->is(self::Published, self::Rejected, self::Withdrawn);
    }

    /** Muallif maqola ma'lumotlari/faylini o'zgartira oladimi */
    public function isEditableByAuthor(): bool
    {
        return $this->is(self::Draft, self::Submitted, self::AwaitingPayment, self::RevisionRequired);
    }

    /**
     * Muharrirlar navbatida ko'rinadigan statuslar
     *
     * @return array<int, self>
     */
    public static function editorialQueue(): array
    {
        return [self::UnderReview, self::InReview, self::Resubmitted];
    }
}

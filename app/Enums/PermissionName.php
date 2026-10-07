<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Spatie Permission ruxsatlari (TZ 3.2, 4.2).
 * Kodda: $user->can(PermissionName::ArticlesAssignReviewer->value)
 * Rol → ruxsat bog'lanishi: self::forRole()
 * Super Admin barcha tekshiruvlardan Gate::before orqali o'tadi.
 */
enum PermissionName: string
{
    use EnumHelpers;

    // Admin panelga kirish
    case AdminAccess = 'admin.access';

    // Maqolalar (Muharrir, Bosh muharrir)
    case ArticlesViewAny = 'articles.view_any';
    case ArticlesAssignReviewer = 'articles.assign_reviewer';
    case ArticlesDecide = 'articles.decide';
    case ArticlesMessageAuthor = 'articles.message_author';

    // Taqriz
    case ReviewsSubmit = 'reviews.submit';

    // Jurnal sonlari va nashr
    case IssuesManage = 'issues.manage';
    case IssuesPublish = 'issues.publish';
    case ProductionManage = 'production.manage';

    // Kontent
    case ContentManage = 'content.manage';

    // Foydalanuvchilar
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';

    // Moliya
    case PaymentsView = 'payments.view';
    case PaymentsConfirmManually = 'payments.confirm_manually';
    case PaymentsRefund = 'payments.refund';
    case PricesManage = 'prices.manage';

    // Hisobotlar va tizim
    case ReportsView = 'reports.view';
    case AuditLogView = 'audit_log.view';
    case SettingsManage = 'settings.manage';
    case AiSettingsManage = 'ai_settings.manage';

    // AI Studio (Proofreader, Translator, Analytics) — xodimlar va mualliflar
    case AiUse = 'ai.use';

    public function label(): string
    {
        return match ($this) {
            self::AdminAccess => __('Admin panelga kirish'),
            self::ArticlesViewAny => __("Barcha maqolalarni ko'rish"),
            self::ArticlesAssignReviewer => __('Taqrizchi biriktirish'),
            self::ArticlesDecide => __('Maqola bo\'yicha qaror qabul qilish'),
            self::ArticlesMessageAuthor => __('Muallif bilan yozishish'),
            self::ReviewsSubmit => __('Taqriz yozish'),
            self::IssuesManage => __('Jurnal sonlarini shakllantirish'),
            self::IssuesPublish => __('Jurnal sonini chop etish'),
            self::ProductionManage => __('Maketlash va PDF tayyorlash'),
            self::ContentManage => __('Sayt kontentini boshqarish'),
            self::UsersManage => __('Foydalanuvchilarni boshqarish'),
            self::RolesManage => __('Rollar va ruxsatlarni boshqarish'),
            self::PaymentsView => __("To'lovlarni ko'rish"),
            self::PaymentsConfirmManually => __("To'lovni qo'lda tasdiqlash"),
            self::PaymentsRefund => __("To'lovni qaytarish"),
            self::PricesManage => __('Narxlarni boshqarish'),
            self::ReportsView => __("Hisobotlarni ko'rish"),
            self::AuditLogView => __("Audit logni ko'rish"),
            self::SettingsManage => __('Tizim sozlamalari'),
            self::AiSettingsManage => __('AI sozlamalari'),
            self::AiUse => __("AI Studio'dan foydalanish"),
        };
    }

    /**
     * Har bir rolga beriladigan ruxsatlar.
     * Super Admin bu yerda yo'q — u Gate::before orqali hammasiga ega.
     *
     * @return array<int, self>
     */
    public static function forRole(RoleName $role): array
    {
        return match ($role) {
            RoleName::SuperAdmin => self::cases(),
            RoleName::ChiefEditor => [
                self::AdminAccess,
                self::AiUse,
                self::ArticlesViewAny,
                self::ArticlesAssignReviewer,
                self::ArticlesDecide,
                self::ArticlesMessageAuthor,
                self::IssuesManage,
                self::IssuesPublish,
                self::ProductionManage,
                self::PaymentsView,
                self::PaymentsRefund,
                self::ReportsView,
            ],
            RoleName::Editor => [
                self::AdminAccess,
                self::AiUse,
                self::ArticlesViewAny,
                self::ArticlesAssignReviewer,
                self::ArticlesDecide,
                self::ArticlesMessageAuthor,
                self::PaymentsView,
            ],
            RoleName::Reviewer => [
                self::AdminAccess,
                self::AiUse,
                self::ReviewsSubmit,
            ],
            RoleName::LayoutEditor => [
                self::AdminAccess,
                self::AiUse,
                self::ProductionManage,
                self::IssuesManage,
            ],
            RoleName::ContentManager => [
                self::AdminAccess,
                self::AiUse,
                self::ContentManage,
            ],
            RoleName::Author => [self::AiUse],
        };
    }

    /**
     * Ruxsatlar matritsasidagi guruh (Admin → Rollar va ruxsatlar).
     */
    public function group(): string
    {
        return match ($this) {
            self::AdminAccess, self::AiUse => 'general',
            self::ArticlesViewAny, self::ArticlesAssignReviewer, self::ArticlesDecide, self::ArticlesMessageAuthor, self::ReviewsSubmit => 'editorial',
            self::IssuesManage, self::IssuesPublish, self::ProductionManage => 'publishing',
            self::ContentManage => 'content',
            self::PaymentsView, self::PaymentsConfirmManually, self::PaymentsRefund, self::PricesManage => 'finance',
            self::UsersManage, self::RolesManage, self::ReportsView, self::AuditLogView, self::SettingsManage, self::AiSettingsManage => 'system',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'general' => 'Umumiy',
            'editorial' => 'Tahririyat va taqriz',
            'publishing' => 'Nashr',
            'content' => 'Sayt kontenti',
            'finance' => 'Moliya',
            'system' => 'Boshqaruv va tizim',
        ];
    }

    /** Muallif (admin panelga kirmaydigan) roliga berish mumkin bo'lgan ruxsatlar */
    public function forAuthors(): bool
    {
        return $this === self::AiUse;
    }
}

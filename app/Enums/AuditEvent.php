<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Audit log hodisalari (App\Services\Audit\AuditLogger).
 * Qiymat: "{kategoriya}.{amal}" — kategoriya bo'yicha filtrlash uchun.
 */
enum AuditEvent: string
{
    use EnumHelpers;

    // Kirish
    case Login = 'auth.login';
    case Logout = 'auth.logout';
    case LoginFailed = 'auth.failed';
    case Lockout = 'auth.lockout';
    case PasswordReset = 'auth.password_reset';
    case SocialLinked = 'auth.social_linked';
    case SocialUnlinked = 'auth.social_unlinked';

    // Maqolalar
    case ArticleStatusChanged = 'article.status';
    case ArticleEditorAssigned = 'article.editor';

    // Taqriz
    case ReviewInvited = 'review.invited';
    case ReviewCancelled = 'review.cancelled';
    case ReviewerAdded = 'review.reviewer_added';
    case ReviewerRemoved = 'review.reviewer_removed';
    case ReviewerPaused = 'review.reviewer_paused';
    case ReviewerResumed = 'review.reviewer_resumed';
    case ReviewerSubjectsUpdated = 'review.reviewer_subjects';

    // Nashr jarayoni
    case FinalPdfUploaded = 'production.final_pdf';
    case ProductionApproved = 'production.approved';
    case ProductionApprovalRevoked = 'production.revoked';
    case ProofApprovalWaived = 'production.proof_waived';

    // Jurnal sonlari
    case IssueCreated = 'issue.created';
    case IssueDeleted = 'issue.deleted';
    case IssuePublished = 'issue.published';
    case IssuePdfBuilt = 'issue.pdf_built';

    // To'lovlar
    case PaymentConfirmed = 'payment.confirmed';
    case PaymentWaived = 'payment.waived';
    case PaymentPaidOnline = 'payment.paid_online';
    case PaymentReminded = 'payment.reminded';
    case RefundRequested = 'payment.refund_requested';
    case PaymentRefunded = 'payment.refunded';
    case RefundFailed = 'payment.refund_failed';

    // Sayt kontenti va sozlamalar
    case ContentSaved = 'content.saved';
    case ContentDeleted = 'content.deleted';
    case NewsletterSent = 'content.newsletter';
    case PriceChanged = 'content.price';

    // AI Studio
    case AiSettingsUpdated = 'ai.settings';
    case AiPromptUpdated = 'ai.prompt';
    case AiLimitUpdated = 'ai.limit';

    // Foydalanuvchilar
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserBlocked = 'user.blocked';
    case UserUnblocked = 'user.unblocked';
    case UserDeleted = 'user.deleted';
    case UserRestored = 'user.restored';
    case UserPasswordChanged = 'user.password';
    case RolePermissionsUpdated = 'user.role_permissions';
    case BroadcastSent = 'user.broadcast';

    // Tizim sozlamalari
    case SettingsUpdated = 'settings.updated';
    case MailTestSent = 'settings.mail_test';
    case BackupCreated = 'settings.backup_created';
    case BackupDownloaded = 'settings.backup_downloaded';
    case BackupDeleted = 'settings.backup_deleted';

    // Hisobotlar
    case ReportExported = 'report.exported';

    public function label(): string
    {
        return match ($this) {
            self::Login => __('Tizimga kirdi'),
            self::Logout => __('Tizimdan chiqdi'),
            self::LoginFailed => __('Muvaffaqiyatsiz kirish urinishi'),
            self::Lockout => __('Kirish vaqtincha bloklandi'),
            self::PasswordReset => __('Parolni tikladi'),
            self::SocialLinked => __("Tashqi akkaunt bog'landi (Google / ORCID)"),
            self::SocialUnlinked => __('Tashqi akkaunt uzildi (Google / ORCID)'),
            self::ArticleStatusChanged => __("Maqola holati o'zgardi"),
            self::ArticleEditorAssigned => __("Mas'ul muharrir tayinlandi"),
            self::ReviewInvited => __('Taqrizchi taklif qilindi'),
            self::ReviewCancelled => __('Taqriz bekor qilindi'),
            self::ReviewerAdded => __("Taqrizchilar bazasiga qo'shildi"),
            self::ReviewerRemoved => __('Taqrizchilar bazasidan chiqarildi'),
            self::ReviewerPaused => __("Taqrizchi vaqtincha to'xtatildi"),
            self::ReviewerResumed => __('Taqrizchi qayta faollashtirildi'),
            self::ReviewerSubjectsUpdated => __("Taqrizchi yo'nalishlari o'zgardi"),
            self::FinalPdfUploaded => __('Yakuniy PDF yuklandi'),
            self::ProductionApproved => __("Bosh muharrir tasdig'i"),
            self::ProductionApprovalRevoked => __('Tasdiq bekor qilindi'),
            self::ProofApprovalWaived => __('Korrektura muallifsiz tasdiqlandi'),
            self::IssueCreated => __('Jurnal soni yaratildi'),
            self::IssueDeleted => __("Jurnal soni o'chirildi"),
            self::IssuePublished => __('Jurnal soni chop etildi'),
            self::IssuePdfBuilt => __("Son PDF avtomatik yig'ildi"),
            self::PaymentConfirmed => __("To'lov qo'lda tasdiqlandi"),
            self::PaymentWaived => __("To'lovdan ozod qilindi"),
            self::PaymentPaidOnline => __("Onlayn to'lov qabul qilindi"),
            self::PaymentReminded => __("To'lov eslatmasi yuborildi"),
            self::RefundRequested => __("To'lovni qaytarish so'raldi"),
            self::PaymentRefunded => __("To'lov qaytarildi"),
            self::RefundFailed => __("To'lovni qaytarib bo'lmadi"),
            self::ContentSaved => __('Kontent saqlandi'),
            self::ContentDeleted => __("Kontent o'chirildi"),
            self::NewsletterSent => __('Obunachilarga xat yuborildi'),
            self::PriceChanged => __("Maqola turi narxi o'zgartirildi"),
            self::AiSettingsUpdated => __("AI sozlamalari o'zgartirildi"),
            self::AiPromptUpdated => __("AI ko'rsatma shabloni o'zgartirildi"),
            self::AiLimitUpdated => __("Foydalanuvchi AI limiti o'zgartirildi"),
            self::UserCreated => __("Foydalanuvchi qo'shildi"),
            self::UserUpdated => __("Foydalanuvchi ma'lumotlari o'zgardi"),
            self::UserBlocked => __('Foydalanuvchi bloklandi'),
            self::UserUnblocked => __('Foydalanuvchi blokdan chiqarildi'),
            self::UserDeleted => __("Foydalanuvchi o'chirildi"),
            self::UserRestored => __('Foydalanuvchi tiklandi'),
            self::UserPasswordChanged => __("Foydalanuvchi paroli o'zgartirildi"),
            self::RolePermissionsUpdated => __("Rol ruxsatlari o'zgartirildi"),
            self::BroadcastSent => __('Ommaviy xabar yuborildi'),
            self::SettingsUpdated => __("Tizim sozlamalari o'zgartirildi"),
            self::MailTestSent => __('Test xat yuborildi'),
            self::BackupCreated => __('Zaxira nusxa yaratildi'),
            self::BackupDownloaded => __('Zaxira nusxa yuklab olindi'),
            self::BackupDeleted => __("Zaxira nusxa o'chirildi"),
            self::ReportExported => __('Hisobot yuklab olindi'),
        };
    }

    /** Kategoriya: auth, article, review, production, issue, payment, content, ai, user, report, settings */
    public function category(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    /** Ahamiyati: info | warning | danger (jadvalda rang va filtr uchun) */
    public function severity(): string
    {
        return match ($this) {
            self::LoginFailed, self::Lockout, self::UserDeleted, self::IssueDeleted, self::UserBlocked => 'danger',
            self::PaymentConfirmed, self::PaymentWaived, self::RefundRequested, self::PaymentRefunded, self::RefundFailed, self::UserPasswordChanged, self::PasswordReset,
            self::ProductionApprovalRevoked, self::ReportExported, self::UserUpdated, self::ProofApprovalWaived,
            self::AiSettingsUpdated, self::AiPromptUpdated, self::AiLimitUpdated,
            self::PriceChanged, self::ContentDeleted, self::ReviewerRemoved, self::NewsletterSent,
            self::RolePermissionsUpdated, self::SettingsUpdated, self::BroadcastSent,
            self::BackupDownloaded, self::BackupDeleted, self::SocialUnlinked => 'warning',
            default => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'auth' => __('Kirish'),
            'article' => __('Maqolalar'),
            'review' => __('Taqriz'),
            'production' => __('Nashr jarayoni'),
            'issue' => __('Jurnal sonlari'),
            'payment' => __("To'lovlar"),
            'ai' => __('AI Studio'),
            'content' => __('Kontent'),
            'user' => __('Foydalanuvchilar'),
            'report' => __('Hisobotlar'),
            'settings' => __('Tizim sozlamalari'),
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function inCategory(string $category): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event): bool => $event->category() === $category,
        ));
    }
}

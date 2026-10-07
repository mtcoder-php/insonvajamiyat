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

    // Maqolalar
    case ArticleStatusChanged = 'article.status';
    case ArticleEditorAssigned = 'article.editor';

    // Taqriz
    case ReviewInvited = 'review.invited';
    case ReviewCancelled = 'review.cancelled';

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

    // Hisobotlar
    case ReportExported = 'report.exported';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Tizimga kirdi',
            self::Logout => 'Tizimdan chiqdi',
            self::LoginFailed => 'Muvaffaqiyatsiz kirish urinishi',
            self::Lockout => 'Kirish vaqtincha bloklandi',
            self::PasswordReset => 'Parolni tikladi',
            self::ArticleStatusChanged => "Maqola holati o'zgardi",
            self::ArticleEditorAssigned => "Mas'ul muharrir tayinlandi",
            self::ReviewInvited => 'Taqrizchi taklif qilindi',
            self::ReviewCancelled => 'Taqriz bekor qilindi',
            self::FinalPdfUploaded => 'Yakuniy PDF yuklandi',
            self::ProductionApproved => "Bosh muharrir tasdig'i",
            self::ProductionApprovalRevoked => 'Tasdiq bekor qilindi',
            self::ProofApprovalWaived => 'Korrektura muallifsiz tasdiqlandi',
            self::IssueCreated => 'Jurnal soni yaratildi',
            self::IssueDeleted => "Jurnal soni o'chirildi",
            self::IssuePublished => 'Jurnal soni chop etildi',
            self::IssuePdfBuilt => "Son PDF avtomatik yig'ildi",
            self::PaymentConfirmed => "To'lov qo'lda tasdiqlandi",
            self::PaymentWaived => "To'lovdan ozod qilindi",
            self::PaymentPaidOnline => "Onlayn to'lov qabul qilindi",
            self::AiSettingsUpdated => "AI sozlamalari o'zgartirildi",
            self::AiPromptUpdated => "AI ko'rsatma shabloni o'zgartirildi",
            self::AiLimitUpdated => "Foydalanuvchi AI limiti o'zgartirildi",
            self::UserCreated => "Foydalanuvchi qo'shildi",
            self::UserUpdated => "Foydalanuvchi ma'lumotlari o'zgardi",
            self::UserBlocked => 'Foydalanuvchi bloklandi',
            self::UserUnblocked => 'Foydalanuvchi blokdan chiqarildi',
            self::UserDeleted => "Foydalanuvchi o'chirildi",
            self::UserRestored => 'Foydalanuvchi tiklandi',
            self::UserPasswordChanged => "Foydalanuvchi paroli o'zgartirildi",
            self::ReportExported => 'Hisobot yuklab olindi',
        };
    }

    /** Kategoriya: auth, article, review, production, issue, payment, ai, user, report */
    public function category(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    /** Ahamiyati: info | warning | danger (jadvalda rang va filtr uchun) */
    public function severity(): string
    {
        return match ($this) {
            self::LoginFailed, self::Lockout, self::UserDeleted, self::IssueDeleted, self::UserBlocked => 'danger',
            self::PaymentConfirmed, self::PaymentWaived, self::UserPasswordChanged, self::PasswordReset,
            self::ProductionApprovalRevoked, self::ReportExported, self::UserUpdated, self::ProofApprovalWaived,
            self::AiSettingsUpdated, self::AiPromptUpdated, self::AiLimitUpdated => 'warning',
            default => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'auth' => 'Kirish',
            'article' => 'Maqolalar',
            'review' => 'Taqriz',
            'production' => 'Nashr jarayoni',
            'issue' => 'Jurnal sonlari',
            'payment' => "To'lovlar",
            'ai' => 'AI Studio',
            'user' => 'Foydalanuvchilar',
            'report' => 'Hisobotlar',
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

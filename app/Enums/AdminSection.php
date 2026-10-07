<?php

namespace App\Enums;

/**
 * Admin panel bo'limlari (sidebar menyusi, super admin dashboard.png).
 *
 * Har bir bo'lim: URL segmenti, route nomi (admin.{routeKey}.index),
 * kerakli ruxsat va keyingi bosqichda quriladigan CRUD imkoniyatlari.
 * Bo'lim to'liq ishlab chiqilgach `isReady()` true qaytaradi va
 * routes/admin.php da o'z controller'i bilan ro'yxatdan o'tadi.
 */
enum AdminSection: string
{
    // Asosiy
    case Articles = 'articles';
    case Production = 'production';
    case Issues = 'issues';
    case Authors = 'authors';
    case Reviewers = 'reviewers';
    case Payments = 'payments';
    case Ai = 'ai';
    case Messages = 'messages';
    case Reports = 'reports';
    case Settings = 'settings';

    // Tizim boshqaruvi
    case Users = 'users';
    case Roles = 'roles';
    case Audit = 'audit-log';
    case Backups = 'backups';
    case System = 'system-settings';

    /** Route nomidagi kalit: admin.{routeKey}.index */
    public function routeKey(): string
    {
        return match ($this) {
            self::Audit => 'audit',
            self::System => 'system',
            default => $this->value,
        };
    }

    /**
     * Bo'lim to'liq ishlab chiqilganmi (o'z controller'i bor).
     * Tayyor bo'lmaganlari vaqtinchalik admin/Section sahifasini ochadi.
     */
    public function isReady(): bool
    {
        return match ($this) {
            self::Users, self::Payments, self::Articles, self::Production, self::Issues,
            self::Reports, self::Audit, self::Ai, self::Settings => true,
            default => false,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Articles => 'Maqolalar',
            self::Production => 'Nashr jarayoni',
            self::Issues => 'Jurnallar',
            self::Authors => 'Mualliflar',
            self::Reviewers => 'Taqrizchilar',
            self::Payments => "To'lovlar",
            self::Ai => 'AI Studio',
            self::Messages => 'Xabarlar',
            self::Reports => 'Statistika va hisobotlar',
            self::Settings => 'Sozlamalar',
            self::Users => 'Foydalanuvchilar',
            self::Roles => 'Rollar va ruxsatlar',
            self::Audit => 'Audit log',
            self::Backups => 'Zaxira nusxa',
            self::System => 'Tizim sozlamalari',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Articles => "Yuborilgan maqolalarni ko'rib chiqish, taqrizchi tayinlash va qaror qabul qilish.",
            self::Production => "Qabul qilingan maqolalarni maketlash, yakuniy PDF, nashr oldidan tekshiruv va bosh muharrir tasdig'i.",
            self::Issues => 'Jurnal sonlarini shakllantirish, maqolalarni joylashtirish va chop etish.',
            self::Authors => "Mualliflar ro'yxati, profillari va ularning maqolalari.",
            self::Reviewers => 'Taqrizchilar bazasi, yuklama va taqrizlar holati.',
            self::Payments => "Click, Payme va qo'lda tasdiqlangan to'lovlar, qaytarishlar.",
            self::Ai => 'AI xizmatlari (imlo tekshiruvi, tarjima, tahlil) sozlamalari va limitlari.',
            self::Messages => 'Mualliflar bilan yozishmalar va tizim bildirishnomalari.',
            self::Reports => "Maqolalar, to'lovlar va faoliyat bo'yicha hisobotlar.",
            self::Settings => "Jurnal ma'lumotlari, yo'nalishlar, maqola turlari va sayt kontenti.",
            self::Users => "Xodimlar va foydalanuvchilarni qo'shish, bloklash, rol berish.",
            self::Roles => 'Rollar va ularga biriktirilgan ruxsatlar (RBAC).',
            self::Audit => 'Tizimdagi muhim amallar tarixi: kim, qachon, nima qildi.',
            self::Backups => "Ma'lumotlar bazasi va fayllarning zaxira nusxalari.",
            self::System => "Pochta, to'lov tizimlari, AI kalitlari va boshqa texnik sozlamalar.",
        };
    }

    /**
     * Keyingi bosqichda quriladigan imkoniyatlar (sahifada reja sifatida ko'rsatiladi).
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        return match ($this) {
            self::Articles => ["Ro'yxat: qidiruv, holat va yo'nalish bo'yicha filtr", 'Maqola kartasi: fayllar, versiyalar, tarix', 'Taqrizchi tayinlash', 'Qaror: qabul / tuzatish / rad etish'],
            self::Production => ['Maketga olish va yakuniy PDF yuklash', 'Muallif korrektura tasdig\'i', 'Nashr oldidan tekshiruv', 'Bosh muharrir tasdig\'i'],
            self::Issues => ['Yangi son yaratish (yil, raqam, DOI, muqova)', 'Maqolalarni songa joylashtirish va tartiblash', 'PDF va mundarija yuklash', 'Chop etish'],
            self::Authors => ["Ro'yxat va qidiruv", 'Profil: ORCID, ish joyi, ilmiy daraja', 'Maqolalar va to\'lovlar tarixi'],
            self::Reviewers => ['Taqrizchi qo\'shish va yo\'nalish biriktirish', 'Faol taqrizlar va muddatlar', 'Taqrizchi reytingi'],
            self::Payments => ["To'lovlar ro'yxati va filtrlari", "Qo'lda tasdiqlash", 'Qaytarish (refund)', 'Narxlar va xizmatlar'],
            self::Ai => ["So'rovlar tarixi", 'Oylik token limitlari', 'Model va xizmat sozlamalari'],
            self::Messages => ['Muallif bilan yozishma', 'Ommaviy xabar yuborish', 'Bildirishnomalar tarixi'],
            self::Reports => ['Davr bo\'yicha statistika', 'Excel / PDF eksport'],
            self::Settings => ["Yo'nalishlar (CRUD)", 'Maqola turlari va narxlar', 'Bannerlar, yangiliklar, tadbirlar, kitoblar'],
            self::Users => ["Ro'yxat, qidiruv, filtr", "Xodim qo'shish va rol berish", 'Bloklash / blokdan chiqarish'],
            self::Roles => ["Rollar ro'yxati", 'Ruxsatlar matritsasi'],
            self::Audit => ['Amallar jurnali', 'Foydalanuvchi va sana bo\'yicha filtr'],
            self::Backups => ['Zaxira nusxa yaratish', 'Yuklab olish va tiklash'],
            self::System => ['Pochta (SMTP)', 'Click / Payme kalitlari', 'AI kaliti va modeli'],
        };
    }

    public function permission(): PermissionName
    {
        return match ($this) {
            self::Articles, self::Authors => PermissionName::ArticlesViewAny,
            self::Production => PermissionName::ProductionManage,
            self::Issues => PermissionName::IssuesManage,
            self::Reviewers => PermissionName::ArticlesAssignReviewer,
            self::Payments => PermissionName::PaymentsView,
            self::Ai => PermissionName::AiUse,
            self::Messages => PermissionName::ArticlesMessageAuthor,
            self::Reports => PermissionName::ReportsView,
            self::Settings => PermissionName::ContentManage,
            self::Users => PermissionName::UsersManage,
            self::Roles => PermissionName::RolesManage,
            self::Audit => PermissionName::AuditLogView,
            self::Backups, self::System => PermissionName::SettingsManage,
        };
    }
}

import {
    BookText,
    BrainCircuit,
    ClipboardList,
    Cog,
    CreditCard,
    DatabaseBackup,
    FileChartColumn,
    FileText,
    House,
    Mail,
    Settings,
    UserCheck,
    UserLock,
    UserRound,
    Users,
} from '@lucide/vue';
import { dashboard } from '@/routes/admin';
import { index as aiIndex } from '@/routes/admin/ai';
import { index as articlesIndex } from '@/routes/admin/articles';
import { index as auditIndex } from '@/routes/admin/audit';
import { index as authorsIndex } from '@/routes/admin/authors';
import { index as backupsIndex } from '@/routes/admin/backups';
import { index as issuesIndex } from '@/routes/admin/issues';
import { index as messagesIndex } from '@/routes/admin/messages';
import { index as paymentsIndex } from '@/routes/admin/payments';
import { index as reportsIndex } from '@/routes/admin/reports';
import { index as reviewersIndex } from '@/routes/admin/reviewers';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as settingsIndex } from '@/routes/admin/settings';
import { index as systemIndex } from '@/routes/admin/system';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavGroup } from '@/types';

/**
 * Admin panel menyusi (super admin dashboard.png, TZ 4.2).
 * `permission` — App\Enums\AdminSection::permission() bilan bir xil:
 * foydalanuvchi faqat o'z roliga tegishli bo'limlarni ko'radi.
 * `badge` — App\Services\Admin\NavigationBadges kalitlari.
 * Profil va saytga o'tish — header'dagi foydalanuvchi menyusida.
 */
export function adminNavigation(): NavGroup[] {
    return [
        {
            label: '',
            items: [
                {
                    title: 'Bosh sahifa',
                    href: dashboard(),
                    icon: House,
                    permission: 'admin.access',
                    exact: true,
                },
                {
                    title: 'Maqolalar',
                    href: articlesIndex(),
                    icon: FileText,
                    permission: 'articles.view_any',
                    badge: 'articles',
                },
                {
                    title: 'Jurnallar',
                    href: issuesIndex(),
                    icon: BookText,
                    permission: 'issues.manage',
                    badge: 'issues',
                },
                {
                    title: 'Mualliflar',
                    href: authorsIndex(),
                    icon: UserRound,
                    permission: 'articles.view_any',
                    badge: 'authors',
                },
                {
                    title: 'Taqrizchilar',
                    href: reviewersIndex(),
                    icon: UserCheck,
                    permission: 'articles.assign_reviewer',
                    badge: 'reviewers',
                },
                {
                    title: "To'lovlar",
                    href: paymentsIndex(),
                    icon: CreditCard,
                    permission: 'payments.view',
                    badge: 'payments',
                },
                {
                    title: 'AI xizmatlari',
                    href: aiIndex(),
                    icon: BrainCircuit,
                    permission: 'ai_settings.manage',
                    badge: 'ai',
                },
                {
                    title: 'Xabarlar',
                    href: messagesIndex(),
                    icon: Mail,
                    permission: 'articles.message_author',
                    badge: 'messages',
                },
                {
                    title: 'Hisobotlar',
                    href: reportsIndex(),
                    icon: FileChartColumn,
                    permission: 'reports.view',
                },
                {
                    title: 'Sozlamalar',
                    href: settingsIndex(),
                    icon: Settings,
                    permission: 'content.manage',
                },
            ],
        },
        {
            label: 'Tizim boshqaruvi',
            items: [
                {
                    title: 'Foydalanuvchilar',
                    href: usersIndex(),
                    icon: Users,
                    permission: 'users.manage',
                },
                {
                    title: 'Rollar va ruxsatlar',
                    href: rolesIndex(),
                    icon: UserLock,
                    permission: 'roles.manage',
                },
                {
                    title: 'Audit log',
                    href: auditIndex(),
                    icon: ClipboardList,
                    permission: 'audit_log.view',
                },
                {
                    title: 'Zaxira nusxa',
                    href: backupsIndex(),
                    icon: DatabaseBackup,
                    permission: 'settings.manage',
                },
                {
                    title: 'Tizim sozlamalari',
                    href: systemIndex(),
                    icon: Cog,
                    permission: 'settings.manage',
                },
            ],
        },
    ];
}

import {
    BookText,
    BookCheck,
    BrainCircuit,
    ClipboardList,
    ClipboardPen,
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
import { index as productionIndex } from '@/routes/admin/production';
import { index as reportsIndex } from '@/routes/admin/reports';
import { index as reviewsIndex } from '@/routes/admin/reviews';
import { index as reviewersIndex } from '@/routes/admin/reviewers';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as settingsIndex } from '@/routes/admin/settings';
import { index as systemIndex } from '@/routes/admin/system';
import { index as usersIndex } from '@/routes/admin/users';
import { tk } from '@/lib/i18n';
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
                    title: tk('Bosh sahifa'),
                    href: dashboard(),
                    icon: House,
                    permission: 'admin.access',
                    exact: true,
                },
                {
                    title: tk('Taqrizlarim'),
                    href: reviewsIndex(),
                    icon: ClipboardPen,
                    permission: 'reviews.submit',
                    badge: 'reviews',
                },
                {
                    title: tk('Maqolalar'),
                    href: articlesIndex(),
                    icon: FileText,
                    permission: 'articles.view_any',
                    badge: 'articles',
                },
                {
                    title: tk('Nashr jarayoni'),
                    href: productionIndex(),
                    icon: BookCheck,
                    permission: 'production.manage',
                    badge: 'production',
                },
                {
                    title: tk('Jurnallar'),
                    href: issuesIndex(),
                    icon: BookText,
                    permission: 'issues.manage',
                    badge: 'issues',
                },
                {
                    title: tk('Mualliflar'),
                    href: authorsIndex(),
                    icon: UserRound,
                    permission: 'articles.view_any',
                    badge: 'authors',
                },
                {
                    title: tk('Taqrizchilar'),
                    href: reviewersIndex(),
                    icon: UserCheck,
                    permission: 'articles.assign_reviewer',
                    badge: 'reviewers',
                },
                {
                    title: tk("To'lovlar"),
                    href: paymentsIndex(),
                    icon: CreditCard,
                    permission: 'payments.view',
                    badge: 'payments',
                },
                {
                    title: 'AI Studio',
                    href: aiIndex(),
                    icon: BrainCircuit,
                    permission: 'ai.use',
                    badge: 'ai',
                },
                {
                    title: tk('Xabarlar'),
                    href: messagesIndex(),
                    icon: Mail,
                    permission: 'articles.message_author',
                    badge: 'messages',
                },
                {
                    title: tk('Statistika'),
                    href: reportsIndex(),
                    icon: FileChartColumn,
                    permission: 'reports.view',
                },
                {
                    title: tk('Sozlamalar'),
                    href: settingsIndex(),
                    icon: Settings,
                    permission: 'content.manage',
                },
            ],
        },
        {
            label: tk('Tizim boshqaruvi'),
            items: [
                {
                    title: tk('Foydalanuvchilar'),
                    href: usersIndex(),
                    icon: Users,
                    permission: 'users.manage',
                },
                {
                    title: tk('Rollar va ruxsatlar'),
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
                    title: tk('Zaxira nusxa'),
                    href: backupsIndex(),
                    icon: DatabaseBackup,
                    permission: 'settings.manage',
                },
                {
                    title: tk('Tizim sozlamalari'),
                    href: systemIndex(),
                    icon: Cog,
                    permission: 'settings.manage',
                },
            ],
        },
    ];
}

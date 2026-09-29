import { Globe, LayoutGrid, UserCog } from '@lucide/vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import { edit as profileEdit } from '@/routes/profile';
import type { NavGroup } from '@/types';

/**
 * Admin panel menyusi (TZ 4.2).
 * Har bir element `permission` bilan belgilanadi — foydalanuvchi faqat
 * o'z roliga tegishli bo'limlarni ko'radi (RBAC, TZ 4.2.1).
 *
 * Keyingi modullar bilan qo'shiladi:
 *   Maqolalar (articles.view_any), Taqrizlarim (reviews.submit),
 *   Nashr jarayoni (production.manage), Jurnal sonlari (issues.manage),
 *   To'lovlar (payments.view), Kontent (content.manage),
 *   Foydalanuvchilar (users.manage), AI (ai_settings.manage),
 *   Hisobotlar (reports.view), Audit log (audit_log.view), Sozlamalar (settings.manage)
 */
export function adminNavigation(): NavGroup[] {
    return [
        {
            label: 'Boshqaruv',
            items: [
                {
                    title: 'Bosh sahifa',
                    href: dashboard(),
                    icon: LayoutGrid,
                    permission: 'admin.access',
                },
            ],
        },
        {
            label: 'Shaxsiy',
            items: [
                {
                    title: 'Profil sozlamalari',
                    href: profileEdit(),
                    icon: UserCog,
                },
                { title: 'Saytga qaytish', href: home(), icon: Globe },
            ],
        },
    ];
}

import { FileText, Globe, LayoutGrid, UserCog } from '@lucide/vue';
import { guidelines, home } from '@/routes';
import { dashboard } from '@/routes/cabinet';
import { edit as profileEdit } from '@/routes/profile';
import type { NavGroup } from '@/types';

/**
 * Muallif kabineti menyusi (TZ 4.1.3).
 * Yangi bo'limlar (Mening maqolalarim, Yangi maqola, To'lovlar, Xabarlar, AI)
 * tegishli modullar bilan birga shu yerga qo'shiladi.
 */
export function cabinetNavigation(): NavGroup[] {
    return [
        {
            label: 'Kabinet',
            items: [
                { title: 'Bosh sahifa', href: dashboard(), icon: LayoutGrid },
                {
                    title: 'Profil sozlamalari',
                    href: profileEdit(),
                    icon: UserCog,
                },
            ],
        },
        {
            label: 'Jurnal',
            items: [
                { title: 'Saytga qaytish', href: home(), icon: Globe },
                {
                    title: "Mualliflar uchun yo'riqnoma",
                    href: guidelines(),
                    icon: FileText,
                },
            ],
        },
    ];
}

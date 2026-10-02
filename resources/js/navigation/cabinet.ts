import {
    BookOpenText,
    CircleHelp,
    FilePen,
    Files,
    House,
    Mail,
    MessagesSquare,
    Send,
    UserRound,
} from '@lucide/vue';
import { contact, guidelines } from '@/routes';
import { dashboard } from '@/routes/cabinet';
import { create, index } from '@/routes/cabinet/articles';
import { index as messagesIndex } from '@/routes/cabinet/messages';
import { edit as profileEdit } from '@/routes/profile';
import type { NavGroup, NavItem } from '@/types';

/**
 * Muallif kabineti menyusi (TZ 4.1.3, dizayn: "Muallif kabineti").
 * disabled — modul hali tayyor emas ("tez orada").
 */
export type CabinetNavItem = NavItem & { disabled?: boolean };

export function cabinetMainNavigation(): CabinetNavItem[] {
    return [
        { title: 'Asosiy sahifa', href: dashboard(), icon: House, exact: true },
        { title: 'Mening maqolalarim', href: index(), icon: Files },
        { title: 'Yangi maqola yuborish', href: create(), icon: Send },
        { title: "Profil ma'lumotlari", href: profileEdit(), icon: UserRound },
        {
            title: 'Xabarlar',
            href: messagesIndex(),
            icon: Mail,
            badge: 'notifications',
        },
        {
            title: 'Tahririyat bilan aloqa',
            href: contact(),
            icon: MessagesSquare,
        },
    ];
}

export function cabinetUsefulLinks(): CabinetNavItem[] {
    const url = guidelines.url();

    return [
        { title: "Yo'riqnoma (PDF)", href: url, icon: BookOpenText },
        {
            title: "Maqola yozish bo'yicha maslahatlar",
            href: `${url}#maslahatlar`,
            icon: FilePen,
        },
        {
            title: "Tez-tez so'raladigan savollar",
            href: `${url}#faq`,
            icon: CircleHelp,
        },
    ];
}

/** Umumiy sidebar (AppSidebar, area="cabinet") uchun guruhlangan ko'rinish */
export function cabinetNavigation(): NavGroup[] {
    return [
        {
            label: 'Kabinet',
            items: cabinetMainNavigation().filter((item) => !item.disabled),
        },
        {
            label: 'Foydali havolalar',
            items: cabinetUsefulLinks(),
        },
    ];
}

import {
    BookOpenText,
    BrainCircuit,
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
import { index as aiIndex } from '@/routes/cabinet/ai';
import { create, index } from '@/routes/cabinet/articles';
import { index as messagesIndex } from '@/routes/cabinet/messages';
import { edit as profileEdit } from '@/routes/profile';
import { tk } from '@/lib/i18n';
import type { NavGroup, NavItem } from '@/types';

/**
 * Muallif kabineti menyusi (TZ 4.1.3, dizayn: "Muallif kabineti").
 * disabled — modul hali tayyor emas ("tez orada").
 */
export type CabinetNavItem = NavItem & { disabled?: boolean };

export function cabinetMainNavigation(): CabinetNavItem[] {
    return [
        {
            title: tk('Asosiy sahifa'),
            href: dashboard(),
            icon: House,
            exact: true,
        },
        { title: tk('Mening maqolalarim'), href: index(), icon: Files },
        { title: tk('Yangi maqola yuborish'), href: create(), icon: Send },
        { title: 'AI Studio', href: aiIndex(), icon: BrainCircuit },
        {
            title: tk("Profil ma'lumotlari"),
            href: profileEdit(),
            icon: UserRound,
        },
        {
            title: tk('Xabarlar'),
            href: messagesIndex(),
            icon: Mail,
            badge: 'notifications',
        },
        {
            title: tk('Tahririyat bilan aloqa'),
            href: contact(),
            icon: MessagesSquare,
        },
    ];
}

export function cabinetUsefulLinks(): CabinetNavItem[] {
    const url = guidelines.url();

    return [
        {
            title: tk("Mualliflar uchun yo'riqnoma"),
            href: url,
            icon: BookOpenText,
        },
        {
            title: tk('Shablon va namunalar'),
            href: `${url}#downloads`,
            icon: FilePen,
        },
        {
            title: tk('Maqola turlari va narxlar'),
            href: `${url}#fees`,
            icon: CircleHelp,
        },
    ];
}

/** Umumiy sidebar (AppSidebar, area="cabinet") uchun guruhlangan ko'rinish */
export function cabinetNavigation(): NavGroup[] {
    return [
        {
            label: tk('Kabinet'),
            items: cabinetMainNavigation().filter((item) => !item.disabled),
        },
        {
            label: tk('Foydali havolalar'),
            items: cabinetUsefulLinks(),
        },
    ];
}

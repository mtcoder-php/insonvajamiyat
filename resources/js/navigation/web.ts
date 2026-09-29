import { about, contact, guidelines, home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as issuesIndex } from '@/routes/issues';
import type { NavItem } from '@/types';

/**
 * Web (public) qism menyulari — header, mobil menyu va footer shu yerdan oladi.
 * Yangi sahifa qo'shilganda faqat shu fayl o'zgaradi.
 */
export function mainNavigation(): NavItem[] {
    return [
        { title: 'Bosh sahifa', href: home() },
        { title: 'Jurnal haqida', href: about() },
        { title: 'Maqolalar', href: articlesIndex() },
        { title: 'Jurnal sonlari', href: issuesIndex() },
        { title: "Yo'riqnoma", href: guidelines() },
        { title: 'Aloqa', href: contact() },
    ];
}

export function footerQuickLinks(): NavItem[] {
    return [
        { title: 'Bosh sahifa', href: home() },
        { title: 'Jurnal haqida', href: about() },
        { title: 'Maqolalar', href: articlesIndex() },
        { title: 'Jurnal sonlari', href: issuesIndex() },
        { title: 'Aloqa', href: contact() },
    ];
}

export function footerUsefulLinks(): NavItem[] {
    return [
        { title: 'Mualliflar uchun', href: guidelines() },
        { title: 'Tahririyat kengashi', href: about() },
        { title: 'Arxiv', href: issuesIndex() },
    ];
}

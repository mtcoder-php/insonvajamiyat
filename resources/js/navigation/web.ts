import { about, contact, guidelines, home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as issuesIndex } from '@/routes/issues';
import { t } from '@/lib/i18n';
import type { NavItem } from '@/types';

/**
 * Web (public) qism menyulari — header, mobil menyu va footer shu yerdan oladi.
 * Yangi sahifa qo'shilganda faqat shu fayl o'zgaradi.
 * Tarjima reaktiv bo'lishi uchun computed() ichida chaqiring.
 */
export function mainNavigation(): NavItem[] {
    return [
        { title: t('Bosh sahifa'), href: home() },
        { title: t('Jurnal haqida'), href: about() },
        { title: t('Maqolalar'), href: articlesIndex() },
        { title: t('Jurnal sonlari'), href: issuesIndex() },
        { title: t("Yo'riqnoma"), href: guidelines() },
        { title: t('Aloqa'), href: contact() },
    ];
}

export function footerQuickLinks(): NavItem[] {
    return [
        { title: t('Bosh sahifa'), href: home() },
        { title: t('Jurnal haqida'), href: about() },
        { title: t('Maqolalar'), href: articlesIndex() },
        { title: t('Jurnal sonlari'), href: issuesIndex() },
        { title: t('Aloqa'), href: contact() },
    ];
}

export function footerUsefulLinks(): NavItem[] {
    return [
        { title: t('Mualliflar uchun'), href: guidelines() },
        { title: t('Tahririyat kengashi'), href: about() },
        { title: t('Arxiv'), href: issuesIndex() },
    ];
}

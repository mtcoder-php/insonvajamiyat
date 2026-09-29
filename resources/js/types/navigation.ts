import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** Faqat shu ruxsat bo'lsa ko'rinadi (app/Enums/PermissionName.php) */
    permission?: string;
};

/** Sidebar'dagi sarlavhali guruh */
export type NavGroup = {
    label: string;
    items: NavItem[];
};

/** Tizimning qaysi qismi: muallif kabineti yoki admin panel */
export type AppArea = 'cabinet' | 'admin';

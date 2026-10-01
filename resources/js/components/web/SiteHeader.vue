<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import SiteAccountMenu from '@/components/web/SiteAccountMenu.vue';
import SiteMobileMenu from '@/components/web/SiteMobileMenu.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { mainNavigation } from '@/navigation/web';
import { home } from '@/routes';
import type { NavItem } from '@/types';

/**
 * Sayt header'i.
 *   variant="light" — oq fon (bosh sahifa, home_2.png)
 *   variant="dark"  — to'q ko'k fon (ichki sahifalar: katalog, sonlar, maqola)
 */
const props = withDefaults(
    defineProps<{
        variant?: 'light' | 'dark';
        /** Keng konteyner (muallif kabineti: sidebar + kontent + o'ng ustun) */
        wide?: boolean;
    }>(),
    { variant: 'dark', wide: false },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const navItems = mainNavigation();
const isDark = computed(() => props.variant === 'dark');

// Bosh sahifa faqat aniq mos kelganda faol, qolganlari ichki sahifalarda ham
const isActive = (item: NavItem, index: number): boolean =>
    index === 0 ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href);
</script>

<template>
    <header
        :class="
            cn(
                'sticky top-0 z-40 border-b backdrop-blur',
                isDark
                    ? 'border-white/10 bg-navy-950/95 text-white supports-[backdrop-filter]:bg-navy-950/85'
                    : 'border-line bg-white/95 text-navy-950 supports-[backdrop-filter]:bg-white/85',
            )
        "
    >
        <div
            :class="
                cn(
                    'mx-auto flex h-18 items-center justify-between gap-6 px-4 sm:px-6 lg:px-8',
                    wide ? 'max-w-[100rem]' : 'max-w-7xl',
                )
            "
        >
            <Link :href="home()" class="flex shrink-0 items-center">
                <BrandLogo :tone="isDark ? 'light' : 'dark'" size="sm" />
            </Link>

            <nav
                class="hidden items-center gap-1 lg:flex"
                aria-label="Asosiy menyu"
            >
                <Link
                    v-for="(item, index) in navItems"
                    :key="item.title"
                    :href="item.href"
                    :aria-current="isActive(item, index) ? 'page' : undefined"
                    :class="
                        cn(
                            'relative rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-300 ease-out outline-none',
                            'hover:-translate-y-px focus-visible:ring-2',
                            // Pastki chiziq: hover'da markazdan ikki tomonga yoyiladi
                            'after:absolute after:inset-x-3 after:-bottom-[1.1rem] after:h-0.5 after:origin-center after:scale-x-0 after:rounded-full after:transition-transform after:duration-300 after:ease-out hover:after:scale-x-100',
                            isDark
                                ? 'text-white/75 after:bg-gold-400 hover:bg-white/10 hover:text-white hover:shadow-[0_8px_20px_-10px_rgba(0,0,0,0.6)] focus-visible:ring-white/40'
                                : 'text-navy-800 after:bg-gold-500 hover:bg-brand-50 hover:text-brand-700 hover:shadow-[0_8px_20px_-12px_rgba(0,108,246,0.45)] focus-visible:ring-brand-200',
                            isActive(item, index) &&
                                (isDark
                                    ? 'text-white after:scale-x-100'
                                    : 'text-brand-700 after:scale-x-100'),
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </nav>

            <div class="flex items-center gap-2">
                <SiteAccountMenu :tone="variant" />
                <SiteMobileMenu :tone="variant" />
            </div>
        </div>
    </header>
</template>

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
    }>(),
    { variant: 'dark' },
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
            class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8"
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
                            'relative rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'after:absolute after:inset-x-3 after:-bottom-[1.1rem] after:h-0.5 after:rounded-full after:transition-colors',
                            isDark
                                ? 'text-white/75 hover:text-white'
                                : 'text-navy-800 hover:text-brand-700',
                            isActive(item, index) &&
                                (isDark
                                    ? 'text-white after:bg-gold-400'
                                    : 'text-brand-700 after:bg-brand-600'),
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

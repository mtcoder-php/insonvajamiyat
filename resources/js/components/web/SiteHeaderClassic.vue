<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import SiteAccountMenu from '@/components/web/SiteAccountMenu.vue';
import SiteMobileMenu from '@/components/web/SiteMobileMenu.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { mainNavigation } from '@/navigation/web';
import { home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import type { NavItem } from '@/types';

/**
 * Klassik header (home.png): katta logotip va shior chapda,
 * o'ngda qidiruv + hisob, ostida menyu.
 */
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const navItems = mainNavigation();

const isActive = (item: NavItem, index: number): boolean =>
    index === 0 ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href);
</script>

<template>
    <header class="relative z-40 border-b border-line bg-white">
        <div
            class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8 lg:py-5"
        >
            <Link :href="home()" class="flex min-w-0 items-center gap-4">
                <BrandLogo size="lg" class="hidden sm:inline-flex" />
                <BrandLogo size="md" class="sm:hidden" />
                <span
                    class="hidden max-w-60 border-l border-line pl-4 font-serif text-xs leading-snug text-navy-600 italic 2xl:block"
                >
                    Tarix, etnologiya, antropologiya va falsafa<br />
                    <span class="text-navy-400">
                        Journal of History, Ethnology, Anthropology and
                        Philosophy
                    </span>
                </span>
            </Link>

            <div class="flex flex-col items-end gap-3">
                <div class="flex items-center gap-2">
                    <form
                        :action="articlesIndex.url()"
                        method="get"
                        role="search"
                        class="relative hidden md:block"
                    >
                        <label for="site-search" class="sr-only">
                            Saytda qidirish
                        </label>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            id="site-search"
                            name="q"
                            type="search"
                            placeholder="Maqolalardan qidirish..."
                            class="h-10 w-64 rounded-full border border-line bg-surface-muted pr-4 pl-10 text-sm text-navy-900 placeholder:text-navy-400 focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100 focus:outline-none"
                        />
                    </form>
                    <SiteAccountMenu tone="light" />
                    <SiteMobileMenu tone="light" />
                </div>

                <nav
                    class="hidden items-center gap-1 lg:flex"
                    aria-label="Asosiy menyu"
                >
                    <Link
                        v-for="(item, index) in navItems"
                        :key="item.title"
                        :href="item.href"
                        :aria-current="
                            isActive(item, index) ? 'page' : undefined
                        "
                        :class="
                            cn(
                                'relative px-3 py-1.5 font-serif text-[15px] font-medium whitespace-nowrap text-navy-800 transition-colors hover:text-brand-700',
                                'after:absolute after:inset-x-3 after:-bottom-1 after:h-0.5 after:rounded-full',
                                isActive(item, index) &&
                                    'text-brand-700 after:bg-gold-500',
                            )
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </div>
        </div>
    </header>
</template>

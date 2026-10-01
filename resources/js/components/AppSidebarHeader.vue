<script setup lang="ts">
import { PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import HeaderSearch from '@/components/app/HeaderSearch.vue';
import HeaderUserMenu from '@/components/app/HeaderUserMenu.vue';
import NotificationBell from '@/components/app/NotificationBell.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { useSidebar } from '@/components/ui/sidebar';
import LocaleSwitcher from '@/components/web/LocaleSwitcher.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Admin panel va muallif kabineti header'i (super admin dashboard.png):
 * fon rasmi ustida to'q ko'k panel — sidebar tugmasi, qidiruv (Ctrl+K),
 * til, bildirishnomalar, foydalanuvchi; ostida sahifa yo'li (breadcrumbs).
 */
withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { isMobile, state, toggleSidebar } = useSidebar();

// Header fon rasmi (public/ papkasida)
const bannerUrl = '/images/admin/banner.png';
</script>

<template>
    <header class="sticky top-0 z-30">
        <div
            class="relative flex h-16 items-center gap-3 overflow-hidden bg-navy-950 px-4 md:px-6"
        >
            <!-- Fon: public/images/admin/banner.png, chapdan to'q ko'k qatlam -->
            <div
                class="pointer-events-none absolute inset-0 bg-cover bg-[position:right_center] bg-no-repeat"
                :style="{ backgroundImage: `url('${bannerUrl}')` }"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-r from-navy-950 via-navy-950/90 to-navy-900/35"
                aria-hidden="true"
            />

            <button
                type="button"
                data-sidebar="trigger"
                class="relative -ml-1 flex size-10 shrink-0 items-center justify-center rounded-lg text-white/85 transition-all duration-300 hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                :aria-label="
                    isMobile || state === 'collapsed'
                        ? 'Menyuni ochish'
                        : 'Menyuni yig\'ish'
                "
                @click="toggleSidebar"
            >
                <PanelLeftOpen
                    v-if="isMobile || state === 'collapsed'"
                    class="size-5"
                />
                <PanelLeftClose v-else class="size-5" />
            </button>

            <div class="relative mx-auto hidden w-full max-w-xl md:block">
                <HeaderSearch />
            </div>
            <div class="flex-1 md:hidden" />

            <div class="relative flex shrink-0 items-center gap-1.5 sm:gap-3">
                <LocaleSwitcher tone="glass" />
                <NotificationBell />
                <div class="hidden h-8 w-px bg-white/15 sm:block" />
                <HeaderUserMenu />
            </div>
        </div>

        <div
            v-if="breadcrumbs.length > 0"
            class="flex h-11 items-center border-b border-line bg-surface/95 px-4 backdrop-blur md:px-6"
        >
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>

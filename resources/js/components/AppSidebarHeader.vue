<script setup lang="ts">
import { PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import HeaderUserMenu from '@/components/app/HeaderUserMenu.vue';
import NotificationBell from '@/components/app/NotificationBell.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { useSidebar } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

/**
 * Admin panel va muallif kabineti header'i:
 * to'q ko'k panel (sidebar tugmasi, bildirishnomalar, foydalanuvchi)
 * va uning ostida sahifa yo'li (breadcrumbs).
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
</script>

<template>
    <header class="sticky top-0 z-30">
        <div
            class="relative flex h-16 items-center gap-3 overflow-hidden bg-navy-gradient px-4 md:px-6"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-girih opacity-[0.05]"
                aria-hidden="true"
            />
            <button
                type="button"
                data-sidebar="trigger"
                class="relative -ml-1 flex size-10 items-center justify-center rounded-lg text-white/85 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
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

            <div class="flex-1" />

            <div class="relative flex items-center gap-1 sm:gap-3">
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

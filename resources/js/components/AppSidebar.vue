<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { adminNavigation } from '@/navigation/admin';
import { cabinetNavigation } from '@/navigation/cabinet';
import { dashboard as adminDashboard } from '@/routes/admin';
import { dashboard as cabinetDashboard } from '@/routes/cabinet';
import type { AppArea } from '@/types';

/**
 * Admin panel / muallif kabineti sidebar'i (to'q ko'k, to'liq balandlik).
 * Menyu `area` ga qarab navigation/admin.ts yoki navigation/cabinet.ts dan olinadi.
 */
const props = defineProps<{
    area: AppArea;
}>();

const groups = computed(() =>
    props.area === 'admin' ? adminNavigation() : cabinetNavigation(),
);

const homeHref = computed(() =>
    props.area === 'admin' ? adminDashboard() : cabinetDashboard(),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="border-r-0">
        <SidebarHeader
            class="h-16 justify-center border-b border-sidebar-border px-3"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-transparent active:bg-transparent"
                    >
                        <Link :href="homeHref">
                            <AppLogo :area="area" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="scrollbar-thin gap-1 py-3">
            <NavMain
                v-for="group in groups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter class="p-3 group-data-[collapsible=icon]:hidden">
            <figure
                class="relative overflow-hidden rounded-xl border border-sidebar-border bg-white/[0.03] p-4"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-girih opacity-[0.06]"
                    aria-hidden="true"
                />
                <blockquote
                    class="relative font-serif text-[15px] leading-snug text-white/90 italic"
                >
                    “Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.”
                </blockquote>
                <div class="relative mt-3 gold-rule w-20" />
            </figure>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

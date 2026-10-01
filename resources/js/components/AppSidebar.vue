<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
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

// Sidebar banner rasmi hali public/ ga qo'yilmagan bo'lsa — naqshli fon
const bannerFailed = ref(false);

const year = new Date().getFullYear();

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

            <!-- Iqtibos va mualliflik huquqi: menyu bilan birga aylanadi (dizayn bo'yicha) -->
            <div class="mt-auto p-3 pt-4 group-data-[collapsible=icon]:hidden">
                <figure
                    class="group/quote relative isolate flex min-h-56 flex-col overflow-hidden rounded-xl border border-sidebar-border bg-navy-950 p-4 shadow-[0_12px_32px_-16px_rgba(0,0,0,0.6)] transition-all duration-500 hover:border-gold-500/40"
                >
                    <!-- Fon: public/images/admin/sidebar-banner.png -->
                    <img
                        v-if="!bannerFailed"
                        src="/images/admin/sidebar-banner.png"
                        alt=""
                        loading="lazy"
                        class="absolute inset-0 -z-20 size-full object-cover object-bottom transition-transform duration-[1500ms] ease-out group-hover/quote:scale-105"
                        @error="bannerFailed = true"
                    />
                    <div
                        v-else
                        class="pointer-events-none absolute inset-0 -z-20 bg-girih opacity-[0.06]"
                        aria-hidden="true"
                    />
                    <!-- Matn o'qilishi uchun yuqoridan qoraytirish -->
                    <div
                        class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-navy-950/90 via-navy-950/40 to-navy-950/10"
                        aria-hidden="true"
                    />
                    <blockquote
                        class="font-serif text-[15px] leading-snug text-white/95 italic drop-shadow-sm"
                    >
                        “Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.”
                    </blockquote>
                    <div
                        class="mt-3 gold-rule w-20 transition-all duration-500 group-hover/quote:w-28"
                    />
                </figure>
                <p
                    class="mt-3 px-1 text-[11px] leading-relaxed text-sidebar-foreground/45"
                >
                    © {{ year }} Inson va Jamiyat<br />
                    Ilmiy jurnali. Barcha huquqlar himoyalangan.
                </p>
            </div>
        </SidebarContent>
    </Sidebar>
    <slot />
</template>

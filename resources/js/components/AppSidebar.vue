<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeHref">
                            <AppLogo :area="area" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="group in groups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

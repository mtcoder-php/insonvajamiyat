<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import type { NavItem } from '@/types';

/**
 * Sidebar menyu guruhi. Faol element — asosiy ko'k fonda (dizayn bo'yicha).
 * Ruxsati yo'q elementlar umuman ko'rsatilmaydi.
 */
const props = withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
    }>(),
    {
        label: '',
    },
);

const { isCurrentUrl } = useCurrentUrl();
const { can } = usePermissions();

const visibleItems = computed(() =>
    props.items.filter((item) => !item.permission || can(item.permission)),
);
</script>

<template>
    <SidebarGroup v-if="visibleItems.length" class="px-3 py-1">
        <SidebarGroupLabel
            v-if="label"
            class="text-[11px] font-semibold tracking-wider text-sidebar-foreground/50 uppercase"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="h-10 text-sidebar-foreground/85 data-[active=true]:bg-sidebar-primary data-[active=true]:text-sidebar-primary-foreground data-[active=true]:shadow-sm [&>svg]:size-[18px]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

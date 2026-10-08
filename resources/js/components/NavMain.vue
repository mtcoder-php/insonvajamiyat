<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
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
import { t } from '@/lib/i18n';

/**
 * Sidebar menyu guruhi (super admin dashboard.png):
 * faol element — ko'k gradient fonda, raqamlar — o'ngda ko'k "pill".
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

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { can } = usePermissions();
const page = usePage();

const visibleItems = computed(() =>
    props.items.filter((item) => !item.permission || can(item.permission)),
);

function isActive(item: NavItem): boolean {
    return item.exact
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);
}

function badgeOf(item: NavItem): string | null {
    const value = item.badge ? page.props.adminBadges?.[item.badge] : undefined;

    if (!value) {
        return null;
    }

    return value > 999 ? '999+' : String(value);
}
</script>

<template>
    <SidebarGroup v-if="visibleItems.length" class="px-3 py-1">
        <template v-if="label">
            <div
                class="mx-2 mt-1 mb-3 h-px bg-sidebar-border"
                aria-hidden="true"
            />
            <SidebarGroupLabel
                class="text-[11px] font-semibold tracking-wider text-sidebar-foreground/50 uppercase"
            >
                {{ t(label) }}
            </SidebarGroupLabel>
        </template>
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item)"
                    :tooltip="t(item.title)"
                    class="group/nav h-10 text-sidebar-foreground/85 transition-all duration-200 hover:bg-white/[0.06] hover:text-white data-[active=true]:bg-gradient-to-r data-[active=true]:from-brand-600 data-[active=true]:to-brand-500 data-[active=true]:text-white data-[active=true]:shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] [&>svg]:size-[18px]"
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            class="transition-transform duration-200 group-hover/nav:scale-110"
                        />
                        <span
                            class="transition-transform duration-200 group-hover/nav:translate-x-0.5"
                        >
                            {{ t(item.title) }}
                        </span>
                        <span
                            v-if="badgeOf(item)"
                            :class="[
                                'ml-auto min-w-6 rounded-full px-1.5 py-0.5 text-center text-[11px] leading-4 font-semibold tabular-nums group-data-[collapsible=icon]:hidden',
                                isActive(item)
                                    ? 'bg-white/20 text-white'
                                    : 'bg-brand-600 text-white shadow-[0_2px_8px_-2px_rgba(0,108,246,0.8)]',
                            ]"
                        >
                            {{ badgeOf(item) }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

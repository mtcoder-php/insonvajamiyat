<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, LayoutDashboard, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';
import { dashboard, login } from '@/routes';
import type { User } from '@/types';

/**
 * Header'dagi hisob tugmasi: kirgan foydalanuvchi menyusi yoki
 * "Kirish / Ro'yxatdan o'tish" tugmasi.
 */
const props = withDefaults(defineProps<{ tone?: 'light' | 'dark' }>(), {
    tone: 'light',
});

const { auth, isStaff } = usePermissions();
const { getInitials } = useInitials();

const isDark = computed(() => props.tone === 'dark');
const user = computed(() => auth.value.user as User | null);
const accountLabel = computed(() =>
    isStaff.value ? 'Admin panel' : 'Muallif kabineti',
);
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'flex items-center gap-2 rounded-full py-1 pr-2 pl-1 text-sm transition-colors',
                        isDark ? 'hover:bg-white/10' : 'hover:bg-navy-50',
                    )
                "
            >
                <span
                    class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white"
                >
                    {{ getInitials(user.name) }}
                </span>
                <span class="hidden max-w-40 truncate font-medium sm:inline">
                    {{ user.name }}
                </span>
                <ChevronDown class="size-4 opacity-70" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-60">
            <DropdownMenuItem as-child>
                <Link
                    :href="dashboard()"
                    class="flex w-full cursor-pointer items-center"
                >
                    <LayoutDashboard class="mr-2 size-4" />
                    {{ accountLabel }}
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>

    <Button
        v-else
        as-child
        :variant="isDark ? 'outline' : 'default'"
        :class="
            cn(
                'hidden h-10 sm:inline-flex',
                isDark
                    ? 'border-white/30 bg-transparent text-white hover:bg-white/10 hover:text-white'
                    : 'bg-navy-900 hover:bg-navy-800',
            )
        "
    >
        <Link :href="login()">
            <UserRound class="size-4" />
            Kirish / Ro'yxatdan o'tish
        </Link>
    </Button>
</template>

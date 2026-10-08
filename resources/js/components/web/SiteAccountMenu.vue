<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, LayoutDashboard, UserRound } from '@lucide/vue';
import { computed } from 'vue';
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
import { t } from '@/lib/i18n';

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
    isStaff.value ? t('Admin panel') : t('Muallif kabineti'),
);
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'group flex items-center gap-2 rounded-full py-1 pr-2 pl-1 text-sm transition-all duration-300 hover:-translate-y-px',
                        isDark
                            ? 'hover:bg-white/10 hover:shadow-[0_8px_20px_-10px_rgba(0,0,0,0.6)]'
                            : 'hover:bg-navy-50 hover:shadow-[0_8px_20px_-12px_rgba(0,36,66,0.4)]',
                    )
                "
            >
                <span
                    class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white ring-2 ring-transparent transition-all duration-300 group-hover:ring-gold-400"
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

    <Link
        v-else
        :href="login()"
        :class="
            cn(
                'group relative hidden h-10 items-center gap-2.5 overflow-hidden rounded-lg pr-4 pl-1.5 text-sm font-semibold transition-all duration-300 ease-out outline-none sm:inline-flex',
                'hover:-translate-y-0.5 focus-visible:ring-2 active:translate-y-0',
                isDark
                    ? 'border border-white/30 text-white hover:border-white/60 hover:bg-white/10 hover:shadow-[0_10px_24px_-10px_rgba(0,0,0,0.6)] focus-visible:ring-white/40'
                    : 'bg-navy-900 text-white shadow-md shadow-navy-900/20 hover:bg-navy-800 hover:shadow-[0_12px_28px_-10px_rgba(0,36,66,0.55)] focus-visible:ring-brand-300',
            )
        "
    >
        <!-- Hover'da tugma bo'ylab o'tadigan yaltiroq -->
        <span
            class="pointer-events-none absolute inset-y-0 -left-2/3 w-1/2 -skew-x-12 bg-gradient-to-r from-transparent via-white/25 to-transparent transition-all duration-700 ease-out group-hover:left-[130%]"
            aria-hidden="true"
        />
        <span
            class="relative flex size-7 items-center justify-center rounded-md bg-white/10 transition-all duration-300 group-hover:bg-gold-400 group-hover:text-navy-950"
        >
            <UserRound
                class="size-4 transition-transform duration-300 group-hover:scale-110"
            />
        </span>
        <span class="relative">{{ t("Kirish / Ro'yxatdan o'tish") }}</span>
    </Link>
</template>

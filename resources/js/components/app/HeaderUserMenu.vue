<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import { usePermissions } from '@/composables/usePermissions';
import { primaryRoleLabel } from '@/lib/roles';
import type { User } from '@/types';

/**
 * Admin/kabinet header'idagi foydalanuvchi: avatar, ism, rol va menyu.
 */
const { auth } = usePermissions();
const { getInitials } = useInitials();

const user = computed(() => auth.value.user as User);
const roleLabel = computed(() => primaryRoleLabel(auth.value.roles));
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex items-center gap-3 rounded-full py-1 pr-2 pl-1 text-left text-white transition-colors hover:bg-white/10"
                data-test="header-user-menu"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold ring-2 ring-white/20"
                >
                    {{ getInitials(user.name) }}
                </span>
                <span class="hidden min-w-0 leading-tight sm:block">
                    <span class="block max-w-44 truncate text-sm font-semibold">
                        {{ user.name }}
                    </span>
                    <span class="block text-xs text-white/65">
                        {{ roleLabel }}
                    </span>
                </span>
                <ChevronDown class="size-4 text-white/70" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64">
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>

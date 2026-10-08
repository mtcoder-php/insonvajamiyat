<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Globe, LogOut, Settings } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { home, logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { t } from '@/lib/i18n';

type Props = {
    user: User;
    /** Admin/kabinet header'ida — "Saytga o'tish" havolasi */
    showSiteLink?: boolean;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ t('Sozlamalar') }}
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="showSiteLink" :as-child="true">
            <Link class="block w-full cursor-pointer" :href="home()">
                <Globe class="mr-2 h-4 w-4" />
                {{ t("Saytga o'tish") }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('Chiqish') }}
        </Link>
    </DropdownMenuItem>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, Menu } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';
import { mainNavigation } from '@/navigation/web';
import { dashboard, login, register } from '@/routes';
import type { NavItem } from '@/types';

/**
 * Mobil menyu (lg dan kichik ekranlar): o'ngdan chiquvchi panel.
 */
const props = withDefaults(defineProps<{ tone?: 'light' | 'dark' }>(), {
    tone: 'light',
});

const { auth, isStaff } = usePermissions();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const navItems = mainNavigation();
const isDark = computed(() => props.tone === 'dark');
const accountLabel = computed(() =>
    isStaff.value ? 'Admin panel' : 'Muallif kabineti',
);

const isActive = (item: NavItem, index: number): boolean =>
    index === 0 ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href);
</script>

<template>
    <Sheet>
        <SheetTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                :class="
                    cn(
                        'lg:hidden',
                        isDark &&
                            'text-white hover:bg-white/10 hover:text-white',
                    )
                "
                aria-label="Menyuni ochish"
            >
                <Menu class="size-5" />
            </Button>
        </SheetTrigger>
        <SheetContent side="right" class="w-80">
            <SheetHeader>
                <SheetTitle class="sr-only">Menyu</SheetTitle>
                <BrandLogo size="sm" />
            </SheetHeader>
            <nav class="flex flex-col gap-1 px-4">
                <Link
                    v-for="(item, index) in navItems"
                    :key="item.title"
                    :href="item.href"
                    :class="
                        cn(
                            'rounded-md px-3 py-2.5 text-sm font-medium hover:bg-accent',
                            isActive(item, index)
                                ? 'bg-accent text-brand-700'
                                : 'text-navy-900',
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </nav>
            <div v-if="!auth.user" class="mt-4 grid gap-2 px-4">
                <Button as-child class="h-11">
                    <Link :href="login()">Kirish</Link>
                </Button>
                <Button as-child variant="outline" class="h-11">
                    <Link :href="register()">Ro'yxatdan o'tish</Link>
                </Button>
            </div>
            <div v-else class="mt-4 px-4">
                <Button as-child class="h-11 w-full">
                    <Link :href="dashboard()">
                        <LayoutDashboard class="size-4" />
                        {{ accountLabel }}
                    </Link>
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>

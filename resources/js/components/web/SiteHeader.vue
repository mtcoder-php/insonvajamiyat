<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, LayoutDashboard, Menu, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';
import { mainNavigation } from '@/navigation/web';
import { dashboard, home, login, register } from '@/routes';
import type { NavItem, User } from '@/types';

/**
 * Sayt header'i.
 *   variant="light" — oq fon (bosh sahifa, home_2.png)
 *   variant="dark"  — to'q ko'k fon (ichki sahifalar: katalog, sonlar, maqola)
 */
const props = withDefaults(
    defineProps<{
        variant?: 'light' | 'dark';
    }>(),
    { variant: 'dark' },
);

const { auth, isStaff } = usePermissions();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { getInitials } = useInitials();

const navItems = mainNavigation();
const isDark = computed(() => props.variant === 'dark');
const user = computed(() => auth.value.user as User | null);
const accountLabel = computed(() =>
    isStaff.value ? 'Admin panel' : 'Muallif kabineti',
);

// Bosh sahifa faqat aniq mos kelganda faol, qolganlari ichki sahifalarda ham
const isActive = (item: NavItem, index: number): boolean =>
    index === 0 ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href);
</script>

<template>
    <header
        :class="
            cn(
                'sticky top-0 z-40 border-b backdrop-blur',
                isDark
                    ? 'border-white/10 bg-navy-950/95 text-white supports-[backdrop-filter]:bg-navy-950/85'
                    : 'border-line bg-white/95 text-navy-950 supports-[backdrop-filter]:bg-white/85',
            )
        "
    >
        <div
            class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8"
        >
            <Link :href="home()" class="flex shrink-0 items-center">
                <BrandLogo :tone="isDark ? 'light' : 'dark'" size="sm" />
            </Link>

            <nav
                class="hidden items-center gap-1 lg:flex"
                aria-label="Asosiy menyu"
            >
                <Link
                    v-for="(item, index) in navItems"
                    :key="item.title"
                    :href="item.href"
                    :aria-current="isActive(item, index) ? 'page' : undefined"
                    :class="
                        cn(
                            'relative rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'after:absolute after:inset-x-3 after:-bottom-[1.1rem] after:h-0.5 after:rounded-full after:transition-colors',
                            isDark
                                ? 'text-white/75 hover:text-white'
                                : 'text-navy-800 hover:text-brand-700',
                            isActive(item, index) &&
                                (isDark
                                    ? 'text-white after:bg-gold-400'
                                    : 'text-brand-700 after:bg-brand-600'),
                        )
                    "
                >
                    {{ item.title }}
                </Link>
            </nav>

            <div class="flex items-center gap-2">
                <DropdownMenu v-if="user">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            :class="
                                cn(
                                    'flex items-center gap-2 rounded-full py-1 pr-2 pl-1 text-sm transition-colors',
                                    isDark
                                        ? 'hover:bg-white/10'
                                        : 'hover:bg-navy-50',
                                )
                            "
                        >
                            <span
                                class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white"
                            >
                                {{ getInitials(user.name) }}
                            </span>
                            <span
                                class="hidden max-w-40 truncate font-medium sm:inline"
                            >
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

                <template v-else>
                    <Button
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
                        <div v-if="!user" class="mt-4 grid gap-2 px-4">
                            <Button as-child class="h-11">
                                <Link :href="login()">Kirish</Link>
                            </Button>
                            <Button as-child variant="outline" class="h-11">
                                <Link :href="register()">
                                    Ro'yxatdan o'tish
                                </Link>
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
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, LogIn, Menu } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import {
    about,
    contact,
    dashboard,
    guidelines,
    home,
    login,
    register,
} from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as issuesIndex } from '@/routes/issues';
import type { NavItem } from '@/types';

/**
 * Web (public) qism layouti — resources/js/pages/web/* sahifalari uchun.
 * Dizayn bosqichida home_2.png bo'yicha qayta bezatiladi; hozir funksional.
 */
const { auth, isStaff } = usePermissions();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const navItems: NavItem[] = [
    { title: 'Bosh sahifa', href: home() },
    { title: 'Jurnal haqida', href: about() },
    { title: 'Maqolalar', href: articlesIndex() },
    { title: 'Jurnal sonlari', href: issuesIndex() },
    { title: "Yo'riqnoma", href: guidelines() },
    { title: 'Aloqa', href: contact() },
];

const isActive = (item: NavItem): boolean =>
    item.href === navItems[0].href
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);

const accountLabel = computed(() =>
    isStaff.value ? 'Admin panel' : 'Kabinet',
);

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
            >
                <Link :href="home()" class="flex items-center gap-2">
                    <AppLogoIcon class="size-8 fill-current text-primary" />
                    <span class="leading-tight">
                        <span class="block text-sm font-bold tracking-wide">
                            INSON VA JAMIYAT
                        </span>
                        <span class="block text-xs text-muted-foreground">
                            Scientific Journal
                        </span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 lg:flex">
                    <Link
                        v-for="item in navItems"
                        :key="item.title"
                        :href="item.href"
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                        :class="
                            isActive(item)
                                ? 'text-primary'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="auth.user">
                        <Button as-child size="sm">
                            <Link :href="dashboard()">
                                <LayoutDashboard class="size-4" />
                                {{ accountLabel }}
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            as-child
                            variant="ghost"
                            size="sm"
                            class="hidden sm:inline-flex"
                        >
                            <Link :href="register()">Ro'yxatdan o'tish</Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="login()">
                                <LogIn class="size-4" />
                                Kirish
                            </Link>
                        </Button>
                    </template>

                    <Sheet>
                        <SheetTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="lg:hidden"
                                aria-label="Menyu"
                            >
                                <Menu class="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" class="w-72">
                            <SheetHeader>
                                <SheetTitle>Menyu</SheetTitle>
                            </SheetHeader>
                            <nav class="flex flex-col gap-1 px-4">
                                <Link
                                    v-for="item in navItems"
                                    :key="item.title"
                                    :href="item.href"
                                    class="rounded-md px-3 py-2 text-sm font-medium hover:bg-accent"
                                    :class="
                                        isActive(item)
                                            ? 'text-primary'
                                            : 'text-foreground'
                                    "
                                >
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:px-6 lg:px-8"
            >
                <p>
                    © {{ year }} "Inson va Jamiyat" ilmiy jurnali. Barcha
                    huquqlar himoyalangan.
                </p>
                <Link :href="contact()" class="hover:text-foreground">
                    Aloqa
                </Link>
            </div>
        </footer>

        <Toaster />
    </div>
</template>

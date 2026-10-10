<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CabinetSidebar from '@/components/cabinet/CabinetSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import SiteFooter from '@/components/web/SiteFooter.vue';
import SiteHeader from '@/components/web/SiteHeader.vue';
import { useCabinetNav } from '@/composables/useCabinetNav';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muallif kabineti layouti (dizayn: "Muallif kabineti") — resources/js/pages/cabinet/*
 * va muallifning shaxsiy sozlamalari uchun: sayt header/footer, chapda kabinet paneli.
 * Mobil ekranda panel o'rniga gorizontal menyu chiqadi.
 *
 * breadcrumbs — sahifalar o'z sarlavhasida (CabinetPageHeader) ko'rsatadi;
 * layout darajasida qabul qilinadi, boshqa layoutlar bilan mos bo'lishi uchun.
 */
defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const { items, isActive } = useCabinetNav();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#f3f6fa]">
        <SiteHeader variant="dark" wide />

        <div
            class="mx-auto grid w-full max-w-[100rem] flex-1 grid-cols-1 gap-5 px-4 py-5 sm:px-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:px-8 lg:py-6"
        >
            <CabinetSidebar class="hidden lg:flex" />

            <!-- Mobil menyu -->
            <nav
                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:hidden"
                :aria-label="t('Muallif kabineti menyusi')"
            >
                <template v-for="item in items" :key="item.title">
                    <Link
                        v-if="!item.disabled"
                        :href="item.href"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        :class="
                            cn(
                                'flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-medium whitespace-nowrap transition-colors',
                                isActive(item)
                                    ? 'border-brand-600 bg-brand-600 text-white'
                                    : 'border-line bg-white text-navy-700 hover:border-brand-200 hover:text-brand-700',
                            )
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ t(item.title) }}
                    </Link>
                </template>
            </nav>

            <main id="main" class="flex min-w-0 flex-col">
                <slot />
            </main>
        </div>

        <SiteFooter wide />
        <Toaster />
    </div>
</template>

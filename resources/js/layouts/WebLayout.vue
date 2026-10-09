<script setup lang="ts">
import SeoDescription from '@/components/seo/SeoDescription.vue';
import { Toaster } from '@/components/ui/sonner';
import { t } from '@/lib/i18n';
import ScrollToTop from '@/components/web/ScrollToTop.vue';
import SiteFooter from '@/components/web/SiteFooter.vue';
import SiteHeader from '@/components/web/SiteHeader.vue';
import SiteTopbar from '@/components/web/SiteTopbar.vue';

/**
 * Web (public) qism layouti — resources/js/pages/web/* sahifalari uchun
 * (app.ts'dagi resolver biriktiradi).
 *
 * Sahifa header rangini tanlaydi:
 *   defineOptions({ layout: { header: 'light' } })   // bosh sahifa
 * Standart — 'dark' (ichki sahifalar, dizayndagi to'q ko'k header).
 */
const { header = 'dark' } = defineProps<{
    header?: 'light' | 'dark';
}>();
</script>

<template>
    <SeoDescription />
    <div class="flex min-h-screen flex-col bg-page">
        <!-- Klaviatura foydalanuvchilari uchun: menyuni chetlab o'tib asosiy kontentga -->
        <a
            href="#main"
            class="sr-only rounded-lg bg-brand-600 text-sm font-semibold text-white shadow-lg focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2.5 focus:ring-4 focus:ring-brand-200 focus:outline-none"
        >
            {{ t("Asosiy kontentga o'tish") }}
        </a>
        <SiteTopbar />
        <SiteHeader :variant="header" />

        <main id="main" tabindex="-1" class="flex-1 outline-none">
            <slot />
        </main>

        <SiteFooter />
        <ScrollToTop />
        <Toaster />
    </div>
</template>

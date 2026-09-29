<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
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
    <div class="flex min-h-screen flex-col bg-page">
        <SiteTopbar />
        <SiteHeader :variant="header" />

        <main id="main" class="flex-1">
            <slot />
        </main>

        <SiteFooter />
        <Toaster />
    </div>
</template>

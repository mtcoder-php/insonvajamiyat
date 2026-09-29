<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import SiteFooter from '@/components/web/SiteFooter.vue';
import SiteHeader from '@/components/web/SiteHeader.vue';
import SiteHeaderClassic from '@/components/web/SiteHeaderClassic.vue';
import SiteTopbar from '@/components/web/SiteTopbar.vue';
import SiteTopbarClassic from '@/components/web/SiteTopbarClassic.vue';

/**
 * Web (public) qism layouti — resources/js/pages/web/* sahifalari uchun
 * (app.ts'dagi resolver biriktiradi).
 *
 * Sahifa header rangini tanlaydi:
 *   defineOptions({ layout: { header: 'light' } })   // bosh sahifa
 * Standart — 'dark' (ichki sahifalar, dizayndagi to'q ko'k header).
 * 'classic' — home.png varianti (katta logotip, qidiruv, tillar).
 */
const { header = 'dark' } = defineProps<{
    header?: 'light' | 'dark' | 'classic';
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-page">
        <template v-if="header === 'classic'">
            <SiteTopbarClassic />
            <SiteHeaderClassic />
        </template>
        <template v-else>
            <SiteTopbar />
            <SiteHeader :variant="header" />
        </template>

        <main id="main" class="flex-1">
            <slot />
        </main>

        <SiteFooter />
        <Toaster />
    </div>
</template>

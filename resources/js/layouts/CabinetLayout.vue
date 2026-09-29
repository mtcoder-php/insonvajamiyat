<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Muallif kabineti layouti — resources/js/pages/cabinet/* sahifalari uchun
 * (app.ts'dagi layout resolver avtomatik biriktiradi).
 */
const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppSidebarLayout area="cabinet" :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>

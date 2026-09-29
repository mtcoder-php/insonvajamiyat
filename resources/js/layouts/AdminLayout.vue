<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Admin panel layouti — resources/js/pages/admin/* sahifalari uchun
 * (app.ts'dagi layout resolver avtomatik biriktiradi).
 */
const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppSidebarLayout area="admin" :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>

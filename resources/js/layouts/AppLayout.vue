<script setup lang="ts">
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Umumiy sahifalar (masalan, settings/*) uchun: xodim bo'lsa admin panel
 * menyusi, aks holda muallif kabineti menyusi bilan chiqadi.
 */
const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const { isStaff } = usePermissions();

const area = computed(() => (isStaff.value ? 'admin' : 'cabinet'));
</script>

<template>
    <AppSidebarLayout :area="area" :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>

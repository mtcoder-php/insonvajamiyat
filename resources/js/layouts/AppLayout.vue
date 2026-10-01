<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * Umumiy sahifalar (masalan, settings/*) uchun: xodim bo'lsa admin panel
 * qobig'i, aks holda muallif kabineti (sayt header/footer + kabinet paneli).
 */
const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const { isStaff } = usePermissions();
</script>

<template>
    <AppSidebarLayout v-if="isStaff" area="admin" :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <CabinetLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </CabinetLayout>
</template>

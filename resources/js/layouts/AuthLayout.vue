<script setup lang="ts">
import AuthBrandLayout from '@/layouts/auth/AuthBrandLayout.vue';

/**
 * resources/js/pages/auth/* sahifalari uchun (app.ts'dagi resolver biriktiradi).
 * Sahifa defineOptions({ layout: { title, description, wide } }) orqali sozlaydi.
 */
const {
    title = '',
    description = '',
    wide = false,
} = defineProps<{
    title?: string;
    description?: string;
    wide?: boolean;
}>();
</script>

<template>
    <AuthBrandLayout :title="title" :description="description" :wide="wide">
        <slot />
    </AuthBrandLayout>
</template>

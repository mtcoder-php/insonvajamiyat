<script setup lang="ts">
import LocaleBoundary from '@/components/app/LocaleBoundary';
import SeoDescription from '@/components/seo/SeoDescription.vue';
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
    <SeoDescription />
    <AuthBrandLayout :title="title" :description="description" :wide="wide">
        <LocaleBoundary><slot /></LocaleBoundary>
    </AuthBrandLayout>
</template>

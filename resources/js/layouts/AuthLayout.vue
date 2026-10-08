<script setup lang="ts">
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
        <slot />
    </AuthBrandLayout>
</template>

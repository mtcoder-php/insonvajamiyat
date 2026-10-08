<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageIntro from '@/components/web/PageIntro.vue';
import { t } from '@/lib/i18n';
</script>

<template>
    <Head :title="t('Aloqa')" />

    <PageIntro
        :title="t('Aloqa')"
        :description="t('Tahririyat bilan bog\'lanish ma\'lumotlari.')"
    />
</template>

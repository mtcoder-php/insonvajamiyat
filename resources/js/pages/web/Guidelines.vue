<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageIntro from '@/components/web/PageIntro.vue';
import { t } from '@/lib/i18n';
</script>

<template>
    <Head :title="t('Mualliflar uchun yo\'riqnoma')" />

    <PageIntro
        :title="t('Mualliflar uchun yo\'riqnoma')"
        :description="
            t('Maqola tayyorlash qoidalari va standart Word shablon.')
        "
    />
</template>

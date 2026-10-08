<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageIntro from '@/components/web/PageIntro.vue';
import { t } from '@/lib/i18n';
</script>

<template>
    <Head :title="t('Jurnal haqida')" />

    <PageIntro
        :title="t('Jurnal haqida')"
        :description="
            t(
                'Jurnal missiyasi, tahririyat kengashi, ISSN va indekslash ma\'lumotlari.',
            )
        "
    />
</template>

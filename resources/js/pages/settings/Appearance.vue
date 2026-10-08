<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Palette } from '@lucide/vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { edit } from '@/routes/appearance';
import { t } from '@/lib/i18n';

defineOptions({
    layout: {
        breadcrumbs: [{ title: "Ko'rinish", href: edit() }],
    },
});
</script>

<template>
    <Head :title="t('Ko\'rinish')" />

    <SectionCard
        :title="t('Ko\'rinish')"
        :description="
            t(
                'Admin panel va kabinet uchun yorug\' yoki qorong\'i mavzu. Ommaviy sayt doim yorug\' ko\'rinishda.',
            )
        "
        :icon="Palette"
        class="max-w-2xl"
    >
        <AppearanceTabs />
    </SectionCard>
</template>

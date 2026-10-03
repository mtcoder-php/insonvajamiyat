<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BrainCircuit } from '@lucide/vue';
import AiStudio from '@/components/ai/AiStudio.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import { index } from '@/routes/cabinet/ai';
import type { AiStudioPageProps } from '@/types';

/**
 * Muallif kabineti → AI Studio (TZ 4.1.5, 4.1.6): imlo va uslub tekshiruvi, ilmiy tarjima, tahlil.
 * Ish maydoni admin panel bilan umumiy — components/ai/AiStudio.vue.
 */
const props = defineProps<AiStudioPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'AI Studio', href: index() }],
    },
});
</script>

<template>
    <Head title="AI Studio" />

    <AiStudio v-bind="props">
        <template #header>
            <CabinetPageHeader
                title="AI Studio"
                description="Maqolangizni sun'iy intellekt yordamida tekshiring, ilmiy uslubda tarjima qiling va baholang. Natija faqat siz tasdiqlagandan keyin qo'llanadi."
                :icon="BrainCircuit"
                :breadcrumbs="[{ title: 'AI Studio', href: index() }]"
            />
        </template>
    </AiStudio>
</template>

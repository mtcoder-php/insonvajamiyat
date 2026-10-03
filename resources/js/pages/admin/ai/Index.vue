<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BrainCircuit } from '@lucide/vue';
import AiStudio from '@/components/ai/AiStudio.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/ai';
import type { AiStudioPageProps } from '@/types';

/**
 * Admin → AI Studio (super admin ai page.png). Ish maydoni — components/ai/AiStudio.vue.
 */
const props = defineProps<AiStudioPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'AI Studio', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="AI Studio" />

    <div class="flex flex-1 flex-col bg-[#f5f7fb] p-4 md:p-6">
        <AiStudio v-bind="props">
            <template #header>
                <PageHeader
                    title="AI Studio"
                    description="Ilmiy maqolalar uchun sun'iy intellekt yordamida tahrirlash, tarjima va tahlil qilish."
                >
                    <template #before>
                        <span class="mb-2 inline-flex items-center gap-2">
                            <span
                                class="inline-flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-md"
                            >
                                <BrainCircuit class="size-5" />
                            </span>
                            <span
                                class="rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-bold text-violet-700"
                                >Beta</span
                            >
                        </span>
                    </template>
                </PageHeader>
            </template>
        </AiStudio>
    </div>
</template>

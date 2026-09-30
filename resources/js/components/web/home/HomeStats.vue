<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import type { HomeStats } from '@/types';

/**
 * Jurnal ko'rsatkichlari qatori (hero ostida, oq fonda).
 */
const props = defineProps<{ stats: HomeStats }>();

const items = computed(() => [
    { label: 'Nashr etilgan maqolalar', value: props.stats.articles },
    { label: 'Jurnal sonlari', value: props.stats.issues },
    { label: 'Mualliflar', value: props.stats.authors },
    { label: "Ilmiy yo'nalishlar", value: props.stats.subjects },
]);
</script>

<template>
    <section
        class="border-b border-line bg-white"
        aria-label="Jurnal ko'rsatkichlari"
    >
        <dl
            class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4 lg:px-8"
        >
            <div
                v-for="(item, index) in items"
                :key="item.label"
                :class="[
                    'flex flex-col-reverse gap-1 py-7 lg:px-8',
                    index % 2 === 1 && 'border-l border-line pl-6',
                    index > 1 && 'border-t border-line lg:border-t-0',
                    index === 2 && 'lg:border-l',
                    index === 0 && 'lg:pl-0',
                ]"
            >
                <dt class="text-sm text-navy-500">{{ item.label }}</dt>
                <dd
                    class="font-serif text-3xl font-semibold text-navy-950 sm:text-4xl"
                >
                    {{ formatNumber(item.value) }}
                </dd>
            </div>
        </dl>
    </section>
</template>

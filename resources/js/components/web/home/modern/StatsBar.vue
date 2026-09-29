<script setup lang="ts">
import { BookMarked, FileText, Globe, Star, Users } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import type { HomeStats } from '@/types';

/**
 * Hero ostidagi ko'rsatkichlar qatori (hero'ga yarim chiqib turadi).
 */
const props = defineProps<{ stats: HomeStats }>();

const items = computed<{ label: string; value: number; icon: Component }[]>(
    () => [
        {
            label: 'Jami maqolalar',
            value: props.stats.articles,
            icon: FileText,
        },
        {
            label: "Ro'yxatdan o'tgan mualliflar",
            value: props.stats.authors,
            icon: Users,
        },
        {
            label: 'Nashr etilgan sonlar',
            value: props.stats.issues,
            icon: BookMarked,
        },
        { label: 'Xalqaro indekslar', value: props.stats.indexes, icon: Globe },
        {
            label: "Ilmiy yo'nalishlar",
            value: props.stats.subjects,
            icon: Star,
        },
    ],
);
</script>

<template>
    <div class="relative z-10 mx-auto -mt-24 max-w-7xl px-4 sm:px-6 lg:px-8">
        <dl
            class="grid grid-cols-2 overflow-hidden rounded-2xl border border-line bg-surface shadow-float sm:grid-cols-3 lg:grid-cols-5"
        >
            <div
                v-for="item in items"
                :key="item.label"
                class="flex items-center gap-4 border-line p-5 not-last:border-b sm:border-r lg:border-b-0 lg:last:border-r-0"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"
                >
                    <component
                        :is="item.icon"
                        class="size-6"
                        :stroke-width="1.6"
                    />
                </span>
                <div class="flex min-w-0 flex-col-reverse">
                    <dt class="mt-1.5 text-xs leading-tight text-navy-500">
                        {{ item.label }}
                    </dt>
                    <dd
                        class="font-serif text-2xl leading-none font-semibold text-navy-950"
                    >
                        {{ formatNumber(item.value) }}
                    </dd>
                </div>
            </div>
        </dl>
    </div>
</template>

<script setup lang="ts">
import { BookMarked, FileText, Globe, Users } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber, MONTHS_SHORT } from '@/lib/format';
import type { HomeStats, MonthlyArticles } from '@/types';

/**
 * "Jurnal statistikasi": asosiy raqamlar va joriy yil bo'yicha
 * oyma-oy nashr etilgan maqolalar ustunli diagrammasi (kutubxonasiz).
 */
const props = defineProps<{
    stats: HomeStats;
    monthly: MonthlyArticles | null;
}>();

const tiles = computed<{ label: string; value: number; icon: Component }[]>(
    () => [
        {
            label: 'Jami maqolalar',
            value: props.stats.articles,
            icon: FileText,
        },
        {
            label: 'Nashr etilgan sonlar',
            value: props.stats.issues,
            icon: BookMarked,
        },
        { label: 'Mualliflar', value: props.stats.authors, icon: Users },
        { label: 'Indekslash', value: props.stats.indexes, icon: Globe },
    ],
);

const max = computed(() => Math.max(1, ...(props.monthly?.months ?? [0])));
const currentMonth = new Date().getMonth();
</script>

<template>
    <section class="flex flex-col">
        <h2
            class="mb-4 font-serif text-xl font-semibold text-navy-950 sm:text-2xl"
        >
            Jurnal statistikasi
        </h2>

        <div class="flex flex-1 flex-col surface-card p-5">
            <dl class="grid grid-cols-2 gap-4">
                <div
                    v-for="tile in tiles"
                    :key="tile.label"
                    class="flex items-start gap-2.5"
                >
                    <component
                        :is="tile.icon"
                        class="mt-0.5 size-5 shrink-0 text-brand-600"
                        :stroke-width="1.6"
                    />
                    <div class="flex min-w-0 flex-col-reverse">
                        <dt class="text-[11px] leading-tight text-navy-500">
                            {{ tile.label }}
                        </dt>
                        <dd
                            class="font-serif text-xl leading-tight font-semibold text-navy-950"
                        >
                            {{ formatNumber(tile.value) }}
                        </dd>
                    </div>
                </div>
            </dl>

            <div v-if="monthly" class="mt-6 border-t border-line pt-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-navy-900">
                        Maqolalar dinamikasi
                    </h3>
                    <span
                        class="rounded-md border border-line px-2 py-0.5 text-[11px] font-medium text-navy-600"
                    >
                        {{ monthly.year }}
                    </span>
                </div>
                <div
                    class="flex h-28 items-end gap-1"
                    role="img"
                    :aria-label="`${monthly.year}-yilda oyma-oy nashr etilgan maqolalar`"
                >
                    <div
                        v-for="(count, index) in monthly.months"
                        :key="index"
                        class="group relative flex h-full flex-1 flex-col items-center justify-end"
                    >
                        <span
                            class="pointer-events-none absolute -top-1 z-10 -translate-y-full rounded bg-navy-950 px-1.5 py-0.5 text-[10px] text-white opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            {{ count }}
                        </span>
                        <div
                            :class="[
                                'w-full max-w-4 rounded-t-sm transition-colors',
                                index === currentMonth
                                    ? 'bg-brand-600'
                                    : 'bg-brand-300 group-hover:bg-brand-500',
                            ]"
                            :style="{
                                height: `${Math.max(count > 0 ? 6 : 2, (count / max) * 100)}%`,
                            }"
                        />
                    </div>
                </div>
                <div class="mt-1.5 flex gap-1">
                    <span
                        v-for="month in MONTHS_SHORT"
                        :key="month"
                        class="flex-1 text-center text-[9px] text-navy-400"
                    >
                        {{ month.slice(0, 3) }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>

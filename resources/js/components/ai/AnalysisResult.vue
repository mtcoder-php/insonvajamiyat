<script setup lang="ts">
import { Lightbulb, ThumbsUp, TriangleAlert } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiRequestDetail, AnalysisMetricKey } from '@/types';
import ScoreRing from './ScoreRing.vue';
import { metricLabels, qualityLabel } from './aiMeta';

/**
 * Analytics natijasi: umumiy baho, 5 mezon, kuchli/kuchsiz tomonlar va tavsiyalar.
 */
const props = defineProps<{ request: AiRequestDetail }>();

const analysis = computed(() => props.request.analysis ?? null);

const metrics = computed(() =>
    (Object.keys(metricLabels) as AnalysisMetricKey[]).map((key) => ({
        key,
        label: metricLabels[key],
        value: analysis.value?.metrics[key] ?? null,
    })),
);

function barColor(value: number | null): string {
    const v = value ?? 0;

    return v >= 90
        ? 'bg-emerald-500'
        : v >= 75
          ? 'bg-brand-500'
          : v >= 60
            ? 'bg-amber-500'
            : 'bg-red-500';
}

const lists = computed<
    {
        key: string;
        title: string;
        icon: Component;
        tone: string;
        items: string[];
    }[]
>(() => [
    {
        key: 'strengths',
        title: 'Kuchli tomonlar',
        icon: ThumbsUp,
        tone: 'text-emerald-600 bg-emerald-50',
        items: analysis.value?.strengths ?? [],
    },
    {
        key: 'weaknesses',
        title: 'Kamchiliklar',
        icon: TriangleAlert,
        tone: 'text-amber-600 bg-amber-50',
        items: analysis.value?.weaknesses ?? [],
    },
    {
        key: 'recommendations',
        title: 'Tavsiyalar',
        icon: Lightbulb,
        tone: 'text-brand-600 bg-brand-50',
        items: analysis.value?.recommendations ?? [],
    },
]);
</script>

<template>
    <div v-if="analysis" class="grid grid-cols-1 gap-4">
        <section
            class="grid grid-cols-1 gap-5 rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] md:grid-cols-[auto_minmax(0,1fr)]"
        >
            <div class="flex flex-col items-center gap-2 text-center">
                <ScoreRing :value="analysis.score" :size="112" :stroke="10" />
                <p class="font-sans text-[15px] font-bold text-navy-950">
                    {{ qualityLabel(analysis.score) }}
                </p>
                <p class="text-[11px] text-navy-400">Umumiy baho</p>
            </div>
            <div class="grid content-center gap-3">
                <div v-for="metric in metrics" :key="metric.key">
                    <div class="mb-1 flex justify-between text-[13px]">
                        <span class="font-medium text-navy-700">{{
                            metric.label
                        }}</span>
                        <span
                            class="font-semibold text-navy-950 tabular-nums"
                            >{{ metric.value ?? '—' }}</span
                        >
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-[#edf2f8]">
                        <div
                            :class="
                                cn(
                                    'h-full rounded-full transition-[width] duration-700',
                                    barColor(metric.value),
                                )
                            "
                            :style="{ width: `${metric.value ?? 0}%` }"
                        />
                    </div>
                </div>
            </div>
        </section>

        <p
            v-if="analysis.summary"
            class="rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-[13px] leading-relaxed text-navy-700"
        >
            {{ analysis.summary }}
        </p>

        <div class="grid grid-cols-1 gap-3">
            <section
                v-for="list in lists"
                :key="list.key"
                class="rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow hover:shadow-[0_12px_28px_-18px_rgba(0,36,66,0.35)]"
            >
                <h3
                    class="mb-3 flex items-center gap-2 font-sans text-[14px] font-bold text-navy-950"
                >
                    <span
                        :class="
                            cn(
                                'flex size-7 items-center justify-center rounded-lg',
                                list.tone,
                            )
                        "
                    >
                        <component :is="list.icon" class="size-4" />
                    </span>
                    {{ list.title }}
                </h3>
                <ul
                    v-if="list.items.length"
                    class="grid grid-cols-1 gap-2 text-[13px] leading-relaxed text-navy-700"
                >
                    <li
                        v-for="item in list.items"
                        :key="item"
                        class="flex gap-2"
                    >
                        <span
                            class="mt-2 size-1.5 shrink-0 rounded-full bg-navy-300"
                        />
                        {{ item }}
                    </li>
                </ul>
                <p v-else class="text-xs text-navy-400">—</p>
            </section>
        </div>

        <p class="text-[11px] text-navy-400">
            Tahlil qilingan qism:
            {{ formatNumber(analysis.analysed_chars) }} belgi (matn boshidan).
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { niceScale, roundedBar, smoothLine } from '@/lib/chart';
import { MONTHS_SHORT, formatNumber } from '@/lib/format';
import type { MonthlyDynamics } from '@/types';

/**
 * "Maqolalar dinamikasi": oyma-oy yuborilgan / qabul qilingan / nashr etilgan
 * (guruhlangan ustunlar, yuborilganlar uchun trend chizig'i, hover tooltip).
 */
const props = defineProps<{ data: MonthlyDynamics }>();

const series = [
    { key: 'submitted', label: 'Jami', color: '#1a82f7' },
    { key: 'accepted', label: 'Qabul qilinganlar', color: '#0fa37f' },
    { key: 'published', label: 'Nashr etilganlar', color: '#8b5cf6' },
] as const;

const W = 640;
const H = 290;
const pad = { top: 12, right: 8, bottom: 28, left: 36 };
const plotW = W - pad.left - pad.right;
const plotH = H - pad.top - pad.bottom;
const band = plotW / 12;
const barW = Math.min(9, band * 0.2);
const gap = 2;

const scale = computed(() =>
    niceScale(
        Math.max(
            1,
            ...props.data.submitted,
            ...props.data.accepted,
            ...props.data.published,
        ),
    ),
);

const y = (value: number): number =>
    pad.top + plotH - (value / scale.value.max) * plotH;

const groupX = (month: number): number =>
    pad.left + band * month + (band - (barW * 3 + gap * 2)) / 2;

const bars = computed(() =>
    series.flatMap((s, si) =>
        props.data[s.key].map((value, month) => ({
            key: `${s.key}-${month}`,
            color: s.color,
            d: roundedBar(
                groupX(month) + si * (barW + gap),
                y(value),
                barW,
                pad.top + plotH - y(value),
            ),
        })),
    ),
);

const trend = computed(() =>
    smoothLine(
        props.data.submitted.map(
            (value, month) =>
                [groupX(month) + barW / 2, y(value) - 6] as [number, number],
        ),
    ),
);

const hovered = ref<number | null>(null);
</script>

<template>
    <DashCard title="Maqolalar dinamikasi" class="flex flex-col">
        <template #actions>
            <ul
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-navy-600"
            >
                <li
                    v-for="s in series"
                    :key="s.key"
                    class="flex items-center gap-1.5"
                >
                    <span
                        class="size-2.5 rounded-full"
                        :style="{ background: s.color }"
                    />
                    {{ s.label }}
                </li>
            </ul>
        </template>

        <div class="relative my-auto">
            <svg
                :viewBox="`0 0 ${W} ${H}`"
                class="h-auto w-full"
                role="img"
                :aria-label="`${data.year}-yil maqolalar dinamikasi`"
                @mouseleave="hovered = null"
            >
                <g class="text-[10px]" fill="currentColor">
                    <template v-for="tick in scale.ticks" :key="tick">
                        <line
                            :x1="pad.left"
                            :x2="W - pad.right"
                            :y1="y(tick)"
                            :y2="y(tick)"
                            stroke="#e6edf5"
                            :stroke-dasharray="tick === 0 ? undefined : '3 3'"
                        />
                        <text
                            :x="pad.left - 8"
                            :y="y(tick) + 3"
                            text-anchor="end"
                            class="fill-navy-400"
                        >
                            {{ formatNumber(tick) }}
                        </text>
                    </template>
                    <text
                        v-for="(month, i) in MONTHS_SHORT"
                        :key="month"
                        :x="pad.left + band * i + band / 2"
                        :y="H - 8"
                        text-anchor="middle"
                        :class="
                            hovered === i
                                ? 'fill-navy-900 font-semibold'
                                : 'fill-navy-400'
                        "
                    >
                        {{ month }}
                    </text>
                </g>

                <rect
                    v-if="hovered !== null"
                    :x="pad.left + band * hovered + 2"
                    :y="pad.top"
                    :width="band - 4"
                    :height="plotH"
                    rx="6"
                    fill="#eef4fa"
                />

                <path
                    v-for="bar in bars"
                    :key="bar.key"
                    :d="bar.d"
                    :fill="bar.color"
                />

                <path
                    :d="trend"
                    fill="none"
                    stroke="#1a82f7"
                    stroke-width="2"
                    stroke-linecap="round"
                    opacity="0.55"
                />

                <rect
                    v-for="(_, i) in 12"
                    :key="`hit-${i}`"
                    :x="pad.left + band * i"
                    :y="pad.top"
                    :width="band"
                    :height="plotH"
                    fill="transparent"
                    @mouseenter="hovered = i"
                />
            </svg>

            <div
                v-if="hovered !== null"
                class="pointer-events-none absolute top-2 z-10 min-w-40 -translate-x-1/2 rounded-lg border border-line bg-white px-3 py-2 text-xs shadow-lg"
                :style="{
                    left: `${((pad.left + band * hovered + band / 2) / W) * 100}%`,
                }"
            >
                <p class="mb-1 font-semibold text-navy-900">
                    {{ MONTHS_SHORT[hovered] }} {{ data.year }}
                </p>
                <p
                    v-for="s in series"
                    :key="s.key"
                    class="flex items-center justify-between gap-4 text-navy-600"
                >
                    <span class="flex items-center gap-1.5">
                        <span
                            class="size-2 rounded-full"
                            :style="{ background: s.color }"
                        />
                        {{ s.label }}
                    </span>
                    <span class="font-semibold text-navy-900 tabular-nums">
                        {{ data[s.key][hovered] }}
                    </span>
                </p>
            </div>
        </div>
    </DashCard>
</template>

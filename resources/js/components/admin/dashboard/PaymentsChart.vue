<script setup lang="ts">
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { niceScale, smoothLine } from '@/lib/chart';
import { MONTHS_SHORT, formatCompact, formatSum } from '@/lib/format';
import type { PaymentsMonthly } from '@/types';

/**
 * "To'lovlar statistikasi": Click, Payme va qo'lda tasdiqlangan tushumlar oyma-oy (bitta o'q),
 * yumshoq maydonli chiziqlar, kursor chizig'i va tooltip.
 */
const props = defineProps<{ data: PaymentsMonthly }>();

const series = [
    { key: 'click', label: 'Click', color: '#1a82f7' },
    { key: 'payme', label: 'Payme', color: '#0fa37f' },
    { key: 'manual', label: "Qo'lda", color: '#8b5cf6' },
] as const;

const W = 440;
const H = 220;
const pad = { top: 14, right: 12, bottom: 26, left: 40 };
const plotW = W - pad.left - pad.right;
const plotH = H - pad.top - pad.bottom;
const step = plotW / 11;

const scale = computed(() =>
    niceScale(
        Math.max(
            1,
            ...props.data.click,
            ...props.data.payme,
            ...props.data.manual,
        ),
    ),
);

const x = (month: number): number => pad.left + step * month;
const y = (value: number): number =>
    pad.top + plotH - (value / scale.value.max) * plotH;

// Hali kelmagan oylar chizilmaydi (joriy yil uchun)
const months = computed(() => {
    const now = new Date();

    return props.data.year === now.getFullYear() ? now.getMonth() + 1 : 12;
});

const lines = computed(() =>
    series.map((s) => {
        const points = props.data[s.key]
            .slice(0, months.value)
            .map((value, i) => [x(i), y(value)] as [number, number]);
        const line = smoothLine(points);
        const last = points[points.length - 1];

        return {
            ...s,
            line,
            area: last
                ? `${line} L${last[0]},${pad.top + plotH} L${pad.left},${pad.top + plotH} Z`
                : '',
        };
    }),
);

const hovered = ref<number | null>(null);
</script>

<template>
    <DashCard title="To'lovlar statistikasi">
        <template #actions>
            <span
                class="rounded-md border border-line px-2 py-1 text-xs text-navy-600"
            >
                {{ data.year }}-yil
            </span>
        </template>

        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs text-navy-500">Jami tushum</p>
                <p class="text-xl font-bold text-navy-950 tabular-nums">
                    {{ formatSum(data.total) }}
                </p>
            </div>
            <ul class="flex items-center gap-4 text-xs text-navy-600">
                <li
                    v-for="s in series"
                    :key="s.key"
                    class="flex items-center gap-1.5"
                >
                    <span
                        class="h-0.5 w-4 rounded-full"
                        :style="{ background: s.color }"
                    />
                    {{ s.label }}
                </li>
            </ul>
        </div>

        <div class="relative">
            <svg
                :viewBox="`0 0 ${W} ${H}`"
                class="h-auto w-full"
                role="img"
                :aria-label="`${data.year}-yil to'lovlar statistikasi`"
                @mouseleave="hovered = null"
            >
                <defs>
                    <linearGradient
                        v-for="s in series"
                        :id="`pay-${s.key}`"
                        :key="s.key"
                        x1="0"
                        x2="0"
                        y1="0"
                        y2="1"
                    >
                        <stop
                            offset="0%"
                            :stop-color="s.color"
                            stop-opacity="0.16"
                        />
                        <stop
                            offset="100%"
                            :stop-color="s.color"
                            stop-opacity="0"
                        />
                    </linearGradient>
                </defs>

                <g class="text-[10px]">
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
                            {{ formatCompact(tick) }}
                        </text>
                    </template>
                    <text
                        v-for="(month, i) in MONTHS_SHORT"
                        :key="month"
                        :x="x(i)"
                        :y="H - 6"
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

                <path
                    v-for="l in lines"
                    :key="`a-${l.key}`"
                    :d="l.area"
                    :fill="`url(#pay-${l.key})`"
                />
                <path
                    v-for="l in lines"
                    :key="`l-${l.key}`"
                    :d="l.line"
                    fill="none"
                    :stroke="l.color"
                    stroke-width="2"
                    stroke-linecap="round"
                />

                <template v-if="hovered !== null">
                    <line
                        :x1="x(hovered)"
                        :x2="x(hovered)"
                        :y1="pad.top"
                        :y2="pad.top + plotH"
                        stroke="#94a3b8"
                        stroke-dasharray="3 3"
                    />
                    <circle
                        v-for="s in series"
                        :key="`d-${s.key}`"
                        :cx="x(hovered)"
                        :cy="y(data[s.key][hovered])"
                        r="4.5"
                        :fill="s.color"
                        stroke="#fff"
                        stroke-width="2"
                    />
                </template>

                <rect
                    v-for="i in months"
                    :key="`hit-${i}`"
                    :x="x(i - 1) - step / 2"
                    :y="pad.top"
                    :width="step"
                    :height="plotH"
                    fill="transparent"
                    @mouseenter="hovered = i - 1"
                />
            </svg>

            <div
                v-if="hovered !== null"
                class="pointer-events-none absolute top-0 z-10 min-w-44 -translate-x-1/2 rounded-lg border border-line bg-white px-3 py-2 text-xs shadow-lg"
                :style="{
                    left: `${Math.min(80, Math.max(20, (x(hovered) / W) * 100))}%`,
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
                        {{ formatSum(data[s.key][hovered]) }}
                    </span>
                </p>
            </div>
        </div>
    </DashCard>
</template>

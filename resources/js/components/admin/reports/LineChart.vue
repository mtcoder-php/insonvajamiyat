<script setup lang="ts">
import { computed, ref } from 'vue';
import { niceScale, smoothLine } from '@/lib/chart';
import { formatCompact, formatNumber } from '@/lib/format';
import type { ChartSeries } from '@/types';

/**
 * Ko'p chiziqli grafik (kutubxonasiz SVG): yumshoq chiziqlar, birinchi seriya ostida
 * yengil maydon, kursor bo'yicha vertikal chiziq va tooltip.
 * Bo'laklar ko'p bo'lsa (kunlik) o'q yorliqlari siyraklashtiriladi.
 */
const props = withDefaults(
    defineProps<{
        labels: string[];
        series: ChartSeries[];
        height?: number;
        area?: boolean;
        format?: (value: number) => string;
        ariaLabel?: string;
    }>(),
    { height: 250, area: true, format: undefined, ariaLabel: 'Grafik' },
);

const W = 640;
const H = computed(() => props.height);
const pad = { top: 14, right: 12, bottom: 26, left: 40 };
const plotW = W - pad.left - pad.right;
const plotH = computed(() => H.value - pad.top - pad.bottom);

const count = computed(() => props.labels.length);
const step = computed(() =>
    count.value > 1 ? plotW / (count.value - 1) : plotW,
);
const x = (i: number): number =>
    count.value > 1 ? pad.left + i * step.value : pad.left + plotW / 2;

const scale = computed(() =>
    niceScale(Math.max(1, ...props.series.flatMap((s) => s.values))),
);
const y = (value: number): number =>
    pad.top + plotH.value - (value / scale.value.max) * plotH.value;

const lines = computed(() =>
    props.series.map((s) => {
        const points = s.values.map((v, i) => [x(i), y(v)] as [number, number]);
        const d = smoothLine(points);
        const base = pad.top + plotH.value;

        return {
            ...s,
            d,
            area:
                points.length > 1
                    ? `${d} L${points[points.length - 1][0]},${base} L${points[0][0]},${base} Z`
                    : '',
        };
    }),
);

// Yorliqlar: ko'pi bilan ~8 ta
const labelEvery = computed(() => Math.max(1, Math.ceil(count.value / 8)));

const fmt = (value: number): string =>
    props.format ? props.format(value) : formatNumber(value);

const hovered = ref<number | null>(null);
const gradientId = `lc-${Math.random().toString(36).slice(2, 8)}`;

function onMove(event: MouseEvent): void {
    const svg = event.currentTarget as SVGSVGElement;
    const rect = svg.getBoundingClientRect();
    const px = ((event.clientX - rect.left) / rect.width) * W;
    const index = Math.round((px - pad.left) / step.value);

    hovered.value =
        count.value > 0 ? Math.min(count.value - 1, Math.max(0, index)) : null;
}
</script>

<template>
    <div class="relative">
        <svg
            :viewBox="`0 0 ${W} ${H}`"
            class="h-auto w-full touch-none"
            role="img"
            :aria-label="ariaLabel"
            @mousemove="onMove"
            @mouseleave="hovered = null"
        >
            <defs>
                <linearGradient :id="gradientId" x1="0" x2="0" y1="0" y2="1">
                    <stop
                        offset="0%"
                        :stop-color="series[0]?.color ?? '#1a82f7'"
                        stop-opacity="0.18"
                    />
                    <stop
                        offset="100%"
                        :stop-color="series[0]?.color ?? '#1a82f7'"
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
                <template v-for="(label, i) in labels" :key="`l-${i}`">
                    <text
                        v-if="i % labelEvery === 0 || hovered === i"
                        :x="x(i)"
                        :y="H - 7"
                        text-anchor="middle"
                        :class="
                            hovered === i
                                ? 'fill-navy-900 font-semibold'
                                : 'fill-navy-400'
                        "
                    >
                        {{ label }}
                    </text>
                </template>
            </g>

            <path
                v-if="area && lines[0]?.area"
                :d="lines[0].area"
                :fill="`url(#${gradientId})`"
            />

            <line
                v-if="hovered !== null"
                :x1="x(hovered)"
                :x2="x(hovered)"
                :y1="pad.top"
                :y2="pad.top + plotH"
                stroke="#9fb3c8"
                stroke-dasharray="3 3"
            />

            <path
                v-for="line in lines"
                :key="line.key"
                :d="line.d"
                fill="none"
                :stroke="line.color"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            <template v-if="hovered !== null">
                <circle
                    v-for="line in lines"
                    :key="`dot-${line.key}`"
                    :cx="x(hovered)"
                    :cy="y(line.values[hovered] ?? 0)"
                    r="4.5"
                    :fill="line.color"
                    stroke="#fff"
                    stroke-width="2"
                />
            </template>
        </svg>

        <div
            v-if="hovered !== null"
            class="pointer-events-none absolute top-1 z-10 min-w-40 rounded-lg border border-line bg-white px-3 py-2 text-xs shadow-lg"
            :style="{
                left: `${(x(hovered) / W) * 100}%`,
                transform:
                    x(hovered) > W * 0.7
                        ? 'translateX(calc(-100% - 12px))'
                        : 'translateX(12px)',
            }"
        >
            <p class="mb-1 font-semibold text-navy-900">
                {{ labels[hovered] }}
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
                    {{ fmt(s.values[hovered] ?? 0) }}
                </span>
            </p>
        </div>
    </div>
</template>

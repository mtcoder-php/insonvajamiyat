<script setup lang="ts">
import { computed, ref } from 'vue';
import { niceScale, roundedBar } from '@/lib/chart';
import { formatCompact, formatNumber } from '@/lib/format';
import type { ChartSeries } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Ustunli grafik: bo'laklar kam bo'lsa guruhlangan, ko'p bo'lsa (kunlik) — ustma-ust (stacked).
 * Segmentlar orasida 2px oq bo'shliq, faqat ustki segment yumaloq; hover — butun bo'lak tooltip'i.
 */
const props = withDefaults(
    defineProps<{
        labels: string[];
        series: ChartSeries[];
        height?: number;
        format?: (value: number) => string;
        ariaLabel?: string;
        stackAfter?: number;
    }>(),
    { height: 250, format: undefined, ariaLabel: undefined, stackAfter: 12 },
);

const W = 640;
const H = computed(() => props.height);
const pad = { top: 14, right: 8, bottom: 26, left: 40 };
const plotW = W - pad.left - pad.right;
const plotH = computed(() => H.value - pad.top - pad.bottom);
const base = computed(() => pad.top + plotH.value);

const count = computed(() => Math.max(1, props.labels.length));
const band = computed(() => plotW / count.value);
const stacked = computed(() => props.labels.length > props.stackAfter);
const GAP = 2;

const totals = computed(() =>
    props.labels.map((_, i) =>
        props.series.reduce((sum, s) => sum + (s.values[i] ?? 0), 0),
    ),
);

const scale = computed(() =>
    niceScale(
        Math.max(
            1,
            ...(stacked.value
                ? totals.value
                : props.series.flatMap((s) => s.values)),
        ),
    ),
);
const h = (value: number): number => (value / scale.value.max) * plotH.value;
const y = (value: number): number => base.value - h(value);

const barW = computed(() => {
    if (stacked.value) {
        return Math.max(3, Math.min(18, band.value * 0.6));
    }

    const n = Math.max(1, props.series.length);

    return Math.max(3, Math.min(12, (band.value * 0.7 - GAP * (n - 1)) / n));
});

const bars = computed(() => {
    const result: { key: string; d: string; color: string }[] = [];

    props.labels.forEach((_, i) => {
        if (stacked.value) {
            const x0 =
                pad.left + band.value * i + (band.value - barW.value) / 2;
            let top = base.value;
            const visible = props.series.filter((s) => (s.values[i] ?? 0) > 0);

            visible.forEach((s, si) => {
                const height = h(s.values[i] ?? 0);
                const isTop = si === visible.length - 1;
                const segment = Math.max(0, height - (si > 0 ? GAP : 0));

                result.push({
                    key: `${s.key}-${i}`,
                    color: s.color,
                    d: roundedBar(
                        x0,
                        top - height,
                        barW.value,
                        segment,
                        isTop ? 3 : 0,
                    ),
                });
                top -= height;
            });

            return;
        }

        const n = props.series.length;
        const groupW = barW.value * n + GAP * (n - 1);
        const x0 = pad.left + band.value * i + (band.value - groupW) / 2;

        props.series.forEach((s, si) => {
            const value = s.values[i] ?? 0;

            result.push({
                key: `${s.key}-${i}`,
                color: s.color,
                d: roundedBar(
                    x0 + si * (barW.value + GAP),
                    y(value),
                    barW.value,
                    h(value),
                ),
            });
        });
    });

    return result.filter((bar) => bar.d !== '');
});

const labelEvery = computed(() =>
    Math.max(1, Math.ceil(props.labels.length / 10)),
);

const fmt = (value: number): string =>
    props.format ? props.format(value) : formatNumber(value);

const hovered = ref<number | null>(null);
</script>

<template>
    <div class="relative">
        <svg
            :viewBox="`0 0 ${W} ${H}`"
            class="h-auto w-full"
            role="img"
            :aria-label="ariaLabel ?? t('Grafik')"
            @mouseleave="hovered = null"
        >
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
                        :x="pad.left + band * i + band / 2"
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

            <rect
                v-if="hovered !== null"
                :x="pad.left + band * hovered + 1"
                :y="pad.top"
                :width="Math.max(0, band - 2)"
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

            <rect
                v-for="(_, i) in labels"
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
            class="pointer-events-none absolute top-1 z-10 min-w-44 rounded-lg border border-line bg-white px-3 py-2 text-xs shadow-lg"
            :style="{
                left: `${((pad.left + band * hovered + band / 2) / W) * 100}%`,
                transform:
                    pad.left + band * hovered > W * 0.65
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
                        class="size-2 rounded-sm"
                        :style="{ background: s.color }"
                    />
                    {{ s.label }}
                </span>
                <span class="font-semibold text-navy-900 tabular-nums">
                    {{ fmt(s.values[hovered] ?? 0) }}
                </span>
            </p>
            <p
                class="mt-1 flex justify-between gap-4 border-t border-line pt-1 font-semibold text-navy-900"
            >
                <span>{{ t('Jami') }}</span>
                <span class="tabular-nums">{{
                    fmt(totals[hovered] ?? 0)
                }}</span>
            </p>
        </div>
    </div>
</template>

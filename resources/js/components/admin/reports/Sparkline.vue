<script setup lang="ts">
import { computed } from 'vue';
import { smoothLine } from '@/lib/chart';
import { t } from '@/lib/i18n';

/**
 * Kichik trend chizig'i (o'qlarsiz) — kartalar ichida.
 */
const props = withDefaults(
    defineProps<{
        values: number[];
        color?: string;
        height?: number;
        label?: string;
    }>(),
    { color: '#1a82f7', height: 56, label: undefined },
);

const W = 240;
const pad = 4;

const path = computed(() => {
    const n = props.values.length;
    const max = Math.max(1, ...props.values);
    const h = props.height - pad * 2;
    const points = props.values.map(
        (v, i) =>
            [
                n > 1 ? pad + (i * (W - pad * 2)) / (n - 1) : W / 2,
                pad + h - (v / max) * h,
            ] as [number, number],
    );
    const line = smoothLine(points);

    return {
        line,
        area:
            points.length > 1
                ? `${line} L${points[n - 1][0]},${props.height} L${points[0][0]},${props.height} Z`
                : '',
    };
});

const id = `sp-${Math.random().toString(36).slice(2, 8)}`;
</script>

<template>
    <svg
        :viewBox="`0 0 ${W} ${height}`"
        class="h-auto w-full"
        preserveAspectRatio="none"
        role="img"
        :aria-label="label ?? t('Trend')"
    >
        <defs>
            <linearGradient :id="id" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" :stop-color="color" stop-opacity="0.22" />
                <stop offset="100%" :stop-color="color" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path v-if="path.area" :d="path.area" :fill="`url(#${id})`" />
        <path
            :d="path.line"
            fill="none"
            :stroke="color"
            stroke-width="2"
            stroke-linecap="round"
            vector-effect="non-scaling-stroke"
        />
    </svg>
</template>

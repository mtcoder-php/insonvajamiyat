<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';

/**
 * Doiraviy ko'rsatkich (0–100): rang bahoga qarab — yashil / ko'k / sariq / qizil.
 */
const props = withDefaults(
    defineProps<{ value: number | null; size?: number; stroke?: number }>(),
    { size: 72, stroke: 7 },
);

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(
    () =>
        circumference.value *
        (1 - Math.max(0, Math.min(100, props.value ?? 0)) / 100),
);
const color = computed(() => {
    const v = props.value ?? 0;

    if (v >= 90) {
        return '#0fa37f';
    }

    if (v >= 75) {
        return '#1a82f7';
    }

    if (v >= 60) {
        return '#d97706';
    }

    return '#e5484d';
});
</script>

<template>
    <div
        class="relative inline-flex shrink-0 items-center justify-center"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg
            :width="size"
            :height="size"
            class="-rotate-90"
            role="img"
            :aria-label="value === null ? t('Baho yo\'q') : `${value} / 100`"
        >
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke="#e8eef6"
                :stroke-width="stroke"
            />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="stroke"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-[stroke-dashoffset] duration-700 ease-out"
            />
        </svg>
        <span
            class="absolute font-sans font-bold text-navy-950 tabular-nums"
            :style="{ fontSize: `${Math.round(size / 4.2)}px` }"
        >
            {{ value === null ? '—' : `${value}%` }}
        </span>
    </div>
</template>

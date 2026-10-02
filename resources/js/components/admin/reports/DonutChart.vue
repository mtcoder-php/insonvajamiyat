<script setup lang="ts">
import { computed, ref } from 'vue';
import { formatNumber } from '@/lib/format';
import type { ReportShareItem } from '@/types';

/**
 * Halqa diagramma: markazda jami, yonida qiymat va ulushli legenda.
 * Ranglar tartib bilan beriladi (validatordan o'tgan palitra), "Boshqalar" — kulrang.
 */
const props = withDefaults(
    defineProps<{
        items: ReportShareItem[];
        total: number;
        centerLabel: string;
        centerValue?: number;
        emptyText?: string;
    }>(),
    { centerValue: undefined, emptyText: "Tanlangan davrda ma'lumot yo'q" },
);

const PALETTE = ['#1a82f7', '#0fa37f', '#f59e0b', '#8b5cf6', '#e5487a'];
const OTHER = '#9aabbd';

const R = 52;
const C = 2 * Math.PI * R;
const GAP = 3;

const sum = computed(() => props.items.reduce((s, i) => s + i.value, 0));

const segments = computed(() => {
    let offset = 0;
    let colorIndex = 0;

    return props.items.map((item) => {
        const share = sum.value > 0 ? item.value / sum.value : 0;
        const length = Math.max(
            0,
            share * C - (share > 0 && share < 1 ? GAP : 0),
        );
        const color =
            item.key === 'other' || item.key === 'none'
                ? OTHER
                : PALETTE[colorIndex++ % PALETTE.length];
        const segment = {
            ...item,
            color,
            percent: Math.round(share * 100),
            dash: `${length} ${C - length}`,
            offset: -offset,
        };
        offset += share * C;

        return segment;
    });
});

const hovered = ref<string | null>(null);
const active = computed(
    () => segments.value.find((s) => s.key === hovered.value) ?? null,
);
</script>

<template>
    <div class="@container">
        <p
            v-if="items.length === 0"
            class="py-10 text-center text-sm text-navy-400"
        >
            {{ emptyText }}
        </p>
        <div v-else class="flex flex-col items-center gap-5 @md:flex-row">
            <div class="relative size-36 shrink-0">
                <svg viewBox="0 0 140 140" class="size-full -rotate-90">
                    <circle
                        cx="70"
                        cy="70"
                        :r="R"
                        fill="none"
                        stroke="#eef2f7"
                        stroke-width="18"
                    />
                    <circle
                        v-for="s in segments"
                        :key="s.key"
                        cx="70"
                        cy="70"
                        :r="R"
                        fill="none"
                        :stroke="s.color"
                        :stroke-width="hovered === s.key ? 22 : 18"
                        :stroke-dasharray="s.dash"
                        :stroke-dashoffset="s.offset"
                        :opacity="hovered && hovered !== s.key ? 0.35 : 1"
                        class="cursor-pointer transition-all duration-300"
                        @mouseenter="hovered = s.key"
                        @mouseleave="hovered = null"
                    />
                </svg>
                <div
                    class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"
                >
                    <span class="text-2xl font-bold text-navy-950 tabular-nums">
                        {{
                            formatNumber(
                                active ? active.value : (centerValue ?? total),
                            )
                        }}
                    </span>
                    <span
                        class="max-w-24 text-[11px] leading-tight text-navy-500"
                    >
                        {{ active ? `${active.percent}%` : centerLabel }}
                    </span>
                </div>
            </div>

            <ul class="w-full space-y-0.5">
                <li
                    v-for="s in segments"
                    :key="s.key"
                    :class="[
                        'flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] transition-colors',
                        hovered === s.key ? 'bg-surface-muted' : '',
                    ]"
                    @mouseenter="hovered = s.key"
                    @mouseleave="hovered = null"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :style="{ background: s.color }"
                    />
                    <span
                        class="min-w-0 flex-1 truncate text-navy-700"
                        :title="s.label"
                    >
                        {{ s.label }}
                    </span>
                    <span class="text-xs text-navy-400 tabular-nums">
                        {{ s.percent }}%
                    </span>
                    <span
                        class="w-10 text-right font-semibold text-navy-950 tabular-nums"
                    >
                        {{ formatNumber(s.value) }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>

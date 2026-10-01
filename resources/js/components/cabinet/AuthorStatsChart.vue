<script setup lang="ts">
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { roundedBar } from '@/lib/chart';
import { MONTHS_SHORT } from '@/lib/format';
import type { AuthorChart } from '@/types';

/**
 * "Maqolalar statistikasi" — oxirgi 6 oy: yuborilgan, hali jarayondagi va nashr
 * etilgan maqolalar (guruhlangan ustunlar; "jarayonda" yuborilganlarning bir qismi,
 * shuning uchun ustma-ust qo'yilmaydi). Hover — oy bo'yicha tooltip.
 */
const props = defineProps<{ data: AuthorChart }>();

const series = [
    { key: 'submitted', label: 'Yuborilgan', color: '#1a82f7' },
    { key: 'inProgress', label: 'Jarayonda', color: '#f59e0b' },
    { key: 'published', label: 'Nashr etilgan', color: '#0fa37f' },
] as const;

const W = 460;
const H = 230;
const pad = { top: 10, right: 6, bottom: 24, left: 26 };
const plotW = W - pad.left - pad.right;
const plotH = H - pad.top - pad.bottom;
const months = computed(() => props.data.months.length || 6);
const band = computed(() => plotW / months.value);
const barW = computed(() => Math.min(14, band.value * 0.2));
const gap = 2;

/** Butun sonli o'q: 0..max, 4–5 ta belgi */
const scale = computed(() => {
    const max = Math.max(
        4,
        ...props.data.submitted,
        ...props.data.inProgress,
        ...props.data.published,
    );
    const step = Math.ceil(max / 4);

    return {
        max: step * 4,
        ticks: [0, 1, 2, 3, 4].map((i) => i * step),
    };
});

const y = (value: number): number =>
    pad.top + plotH - (value / scale.value.max) * plotH;

const groupX = (i: number): number =>
    pad.left + band.value * i + (band.value - (barW.value * 3 + gap * 2)) / 2;

const bars = computed(() =>
    series.flatMap((s, si) =>
        props.data[s.key].map((value, i) => ({
            key: `${s.key}-${i}`,
            color: s.color,
            d: roundedBar(
                groupX(i) + si * (barW.value + gap),
                y(value),
                barW.value,
                pad.top + plotH - y(value),
            ),
        })),
    ),
);

const monthLabel = (ym: string): string => {
    const [year, month] = ym.split('-').map(Number);

    return `${MONTHS_SHORT[(month ?? 1) - 1]}${year && year !== new Date().getFullYear() ? ` ${String(year).slice(2)}` : ''}`;
};

const hovered = ref<number | null>(null);
</script>

<template>
    <DashCard title="Maqolalar statistikasi" class="flex flex-col">
        <template #actions>
            <span
                class="rounded-md border border-line px-2 py-1 text-[11px] font-medium text-navy-600"
            >
                Oxirgi 6 oy
            </span>
        </template>

        <div class="flex flex-1 flex-col justify-center gap-3">
            <div class="relative min-w-0">
                <svg
                    :viewBox="`0 0 ${W} ${H}`"
                    class="h-auto w-full"
                    role="img"
                    aria-label="Oxirgi 6 oy bo'yicha maqolalar statistikasi"
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
                                :stroke-dasharray="
                                    tick === 0 ? undefined : '3 3'
                                "
                            />
                            <text
                                :x="pad.left - 6"
                                :y="y(tick) + 3"
                                text-anchor="end"
                                class="fill-navy-400"
                            >
                                {{ tick }}
                            </text>
                        </template>
                        <text
                            v-for="(month, i) in data.months"
                            :key="month"
                            :x="pad.left + band * i + band / 2"
                            :y="H - 6"
                            text-anchor="middle"
                            :class="
                                hovered === i
                                    ? 'fill-navy-900 font-semibold'
                                    : 'fill-navy-400'
                            "
                        >
                            {{ monthLabel(month) }}
                        </text>
                    </g>

                    <rect
                        v-if="hovered !== null"
                        :x="pad.left + band * hovered + 3"
                        :y="pad.top"
                        :width="band - 6"
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
                        v-for="(_, i) in data.months"
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
                    class="pointer-events-none absolute top-0 z-10 min-w-36 -translate-x-1/2 rounded-lg border border-line bg-white px-3 py-2 text-xs shadow-lg"
                    :style="{
                        left: `${((pad.left + band * hovered + band / 2) / W) * 100}%`,
                    }"
                >
                    <p class="mb-1 font-semibold text-navy-900">
                        {{ monthLabel(data.months[hovered] ?? '') }}
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
                            {{ data[s.key][hovered] }}
                        </span>
                    </p>
                </div>
            </div>

            <ul
                class="flex flex-wrap justify-center gap-x-5 gap-y-2 text-xs text-navy-700"
            >
                <li
                    v-for="s in series"
                    :key="s.key"
                    class="flex items-center gap-2"
                >
                    <span
                        class="size-2.5 rounded-sm"
                        :style="{ background: s.color }"
                    />
                    {{ s.label }}
                </li>
            </ul>
        </div>
    </DashCard>
</template>

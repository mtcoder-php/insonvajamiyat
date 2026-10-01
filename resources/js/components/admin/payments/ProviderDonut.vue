<script setup lang="ts">
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatNumber } from '@/lib/format';
import type { PaymentProviderKey, ProviderBreakdown } from '@/types';

/**
 * "To'lov usullari bo'yicha": muvaffaqiyatli to'lovlar soni (halqa diagramma, markazda jami).
 */
const props = defineProps<{ data: ProviderBreakdown }>();

const colors: Record<PaymentProviderKey, string> = {
    click: '#1a82f7',
    payme: '#0fa37f',
    manual: '#8b5cf6',
};

const R = 52;
const C = 2 * Math.PI * R;
const GAP = 3;

const segments = computed(() => {
    let offset = 0;

    return props.data.items.map((item) => {
        const share = props.data.total > 0 ? item.value / props.data.total : 0;
        const length = Math.max(
            0,
            share * C - (share > 0 && share < 1 ? GAP : 0),
        );
        const segment = {
            ...item,
            color: colors[item.key],
            percent: Math.round(share * 100),
            dash: `${length} ${C - length}`,
            offset: -offset,
        };
        offset += share * C;

        return segment;
    });
});

const hovered = ref<string | null>(null);
</script>

<template>
    <DashCard title="To'lov usullari bo'yicha">
        <div
            class="flex flex-col items-center gap-5 sm:flex-row xl:flex-col 2xl:flex-row"
        >
            <div class="relative size-36 shrink-0">
                <svg viewBox="0 0 140 140" class="size-full -rotate-90">
                    <circle
                        cx="70"
                        cy="70"
                        :r="R"
                        fill="none"
                        stroke="#eef2f7"
                        stroke-width="16"
                    />
                    <circle
                        v-for="segment in segments"
                        :key="segment.key"
                        cx="70"
                        cy="70"
                        :r="R"
                        fill="none"
                        :stroke="segment.color"
                        :stroke-width="hovered === segment.key ? 20 : 16"
                        :stroke-dasharray="segment.dash"
                        :stroke-dashoffset="segment.offset"
                        class="transition-all duration-300"
                        @mouseenter="hovered = segment.key"
                        @mouseleave="hovered = null"
                    />
                </svg>
                <div
                    class="absolute inset-0 flex flex-col items-center justify-center"
                >
                    <span
                        class="font-sans text-2xl font-bold text-navy-950 tabular-nums"
                    >
                        {{ formatNumber(data.total) }}
                    </span>
                    <span class="text-[11px] text-navy-500">jami to'lov</span>
                </div>
            </div>
            <ul class="grid w-full gap-2.5">
                <li
                    v-for="segment in segments"
                    :key="segment.key"
                    class="flex items-center gap-2 rounded-lg px-2 py-1 text-[13px] transition-colors"
                    :class="hovered === segment.key && 'bg-navy-50'"
                    @mouseenter="hovered = segment.key"
                    @mouseleave="hovered = null"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :style="{ background: segment.color }"
                    />
                    <span class="flex-1 text-navy-700">{{
                        segment.label
                    }}</span>
                    <span class="font-semibold text-navy-950 tabular-nums">
                        {{ formatNumber(segment.value) }}
                    </span>
                    <span
                        class="w-10 text-right text-xs text-navy-400 tabular-nums"
                    >
                        {{ segment.percent }}%
                    </span>
                </li>
            </ul>
        </div>
    </DashCard>
</template>

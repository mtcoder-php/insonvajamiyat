<script setup lang="ts">
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatNumber } from '@/lib/format';
import type { StatusBreakdown, StatusGroupKey } from '@/types';

/**
 * "Maqolalar holati": halqa diagramma (markazda jami) va qiymatli legenda.
 */
const props = defineProps<{ data: StatusBreakdown }>();

const meta: Record<StatusGroupKey, { label: string; color: string }> = {
    new: { label: 'Yangi', color: '#1a82f7' },
    reviewing: { label: "Ko'rib chiqilayotgan", color: '#f59e0b' },
    revision: { label: 'Tuzatish talab qilingan', color: '#ef4444' },
    accepted: { label: 'Qabul qilingan', color: '#0fa37f' },
    published: { label: 'Nashr etilgan', color: '#8b5cf6' },
};

const R = 52;
const C = 2 * Math.PI * R;
const GAP = 3; // segmentlar orasidagi bo'shliq (px)

const segments = computed(() => {
    const total = props.data.total;
    let offset = 0;

    return props.data.groups.map((group) => {
        const share = total > 0 ? group.value / total : 0;
        const length = Math.max(0, share * C - (share > 0 ? GAP : 0));
        const segment = {
            ...group,
            ...meta[group.key],
            percent: Math.round(share * 100),
            dash: `${length} ${C - length}`,
            offset: -offset,
        };
        offset += share * C;

        return segment;
    });
});

const hovered = ref<StatusGroupKey | null>(null);
</script>

<template>
    <DashCard title="Maqolalar holati">
        <div class="@container">
            <div class="flex flex-col items-center gap-5 @md:flex-row">
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
                            v-for="s in segments"
                            :key="s.key"
                            cx="70"
                            cy="70"
                            :r="R"
                            fill="none"
                            :stroke="s.color"
                            :stroke-width="hovered === s.key ? 20 : 16"
                            :stroke-dasharray="s.dash"
                            :stroke-dashoffset="s.offset"
                            :opacity="hovered && hovered !== s.key ? 0.35 : 1"
                            class="cursor-pointer transition-all duration-300"
                            @mouseenter="hovered = s.key"
                            @mouseleave="hovered = null"
                        />
                    </svg>
                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span
                            class="text-3xl font-bold text-navy-950 tabular-nums"
                        >
                            {{ formatNumber(data.total) }}
                        </span>
                        <span class="text-xs text-navy-500">Jami maqola</span>
                    </div>
                </div>

                <ul class="w-full space-y-1">
                    <li
                        v-for="s in segments"
                        :key="s.key"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-2 py-1 text-[13px] transition-colors',
                            hovered === s.key ? 'bg-surface-muted' : '',
                        ]"
                        @mouseenter="hovered = s.key"
                        @mouseleave="hovered = null"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :style="{ background: s.color }"
                        />
                        <span class="min-w-0 flex-1 truncate text-navy-700">{{
                            s.label
                        }}</span>
                        <span class="font-semibold text-navy-950 tabular-nums">
                            {{ formatNumber(s.value) }}
                        </span>
                        <span
                            class="w-12 text-right text-xs text-navy-400 tabular-nums"
                        >
                            ({{ s.percent }}%)
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </DashCard>
</template>

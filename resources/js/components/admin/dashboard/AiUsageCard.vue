<script setup lang="ts">
import { ArrowDown, ArrowUp, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatNumber } from '@/lib/format';
import type { AiUsage } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "AI xizmatlari ishlatilishi": joriy oydagi so'rovlar (halqa) va turlar ulushi.
 */
const props = defineProps<{ data: AiUsage }>();

const colors = ['#1a82f7', '#0fa37f', '#8b5cf6'];

const R = 40;
const C = 2 * Math.PI * R;
const GAP = 3;

const types = computed(() => {
    let offset = 0;

    return props.data.types.map((type, i) => {
        const share = props.data.total > 0 ? type.value / props.data.total : 0;
        const length = Math.max(0, share * C - (share > 0 ? GAP : 0));
        const item = {
            ...type,
            color: colors[i % colors.length],
            percent: Math.round(share * 100),
            dash: `${length} ${C - length}`,
            offset: -offset,
        };
        offset += share * C;

        return item;
    });
});

const trendUp = computed(() => (props.data.trend ?? 0) >= 0);
</script>

<template>
    <DashCard :title="t('AI xizmatlari ishlatilishi')">
        <div class="@container">
            <div class="flex flex-col items-center gap-5 @xs:flex-row">
                <div class="flex shrink-0 flex-col items-center">
                    <div class="relative size-32">
                        <svg viewBox="0 0 100 100" class="size-full -rotate-90">
                            <circle
                                cx="50"
                                cy="50"
                                :r="R"
                                fill="none"
                                stroke="#eef2f7"
                                stroke-width="9"
                            />
                            <circle
                                v-for="type in types"
                                :key="type.key"
                                cx="50"
                                cy="50"
                                :r="R"
                                fill="none"
                                :stroke="type.color"
                                stroke-width="9"
                                :stroke-dasharray="type.dash"
                                :stroke-dashoffset="type.offset"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center"
                        >
                            <Sparkles class="size-4 text-violet-500" />
                            <span
                                class="mt-0.5 text-2xl leading-none font-bold text-navy-950 tabular-nums"
                            >
                                {{ formatNumber(data.total) }}
                            </span>
                            <span class="mt-0.5 text-[10px] text-navy-500">
                                {{ t("so'rov (oylik)") }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="data.trend !== null"
                        :class="[
                            'mt-2 inline-flex items-center gap-0.5 text-xs font-semibold',
                            trendUp ? 'text-emerald-600' : 'text-red-600',
                        ]"
                    >
                        <ArrowUp v-if="trendUp" class="size-3.5" />
                        <ArrowDown v-else class="size-3.5" />
                        {{ Math.abs(data.trend) }}%
                        <span class="font-normal text-navy-400">
                            {{ t("o'tgan oyga nisbatan") }}
                        </span>
                    </p>
                </div>

                <ul class="w-full min-w-0 space-y-3">
                    <li
                        v-for="type in types"
                        :key="type.key"
                        class="flex items-center gap-2 text-[13px]"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :style="{ background: type.color }"
                        />
                        <span
                            class="min-w-0 flex-1 truncate text-navy-700"
                            :title="type.label"
                        >
                            {{ type.label }}
                        </span>
                        <span class="font-semibold text-navy-950 tabular-nums">
                            {{ formatNumber(type.value) }}
                        </span>
                        <span
                            class="w-9 text-right text-xs text-navy-400 tabular-nums"
                        >
                            {{ type.percent }}%
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </DashCard>
</template>

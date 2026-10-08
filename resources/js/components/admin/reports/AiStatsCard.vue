<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    CircleAlert,
    CircleCheck,
    DollarSign,
    Sparkles,
} from '@lucide/vue';
import type { Component } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import LineChart from '@/components/admin/reports/LineChart.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportAi } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "AI statistika": so'rovlar, muvaffaqiyatli, xatolar va xarajat + davr bo'yicha grafik.
 * Xatolar kamaysa — yashil (yaxshi), ko'paysa — qizil.
 */
const props = defineProps<{ data: ReportAi }>();

const meta: Record<
    ReportAi['metrics'][number]['key'],
    { label: string; icon: Component; tint: string; lowerIsBetter?: boolean }
> = {
    total: {
        label: t("Jami so'rovlar"),
        icon: Sparkles,
        tint: 'bg-brand-50 text-brand-600',
    },
    completed: {
        label: t('Muvaffaqiyatli'),
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    failed: {
        label: t('Xatolar'),
        icon: CircleAlert,
        tint: 'bg-red-50 text-red-600',
        lowerIsBetter: true,
    },
    cost: {
        label: t('Xarajat'),
        icon: DollarSign,
        tint: 'bg-violet-50 text-violet-600',
    },
};

const total = (): number =>
    props.data.metrics.find((m) => m.key === 'total')?.value ?? 0;

function value(metric: ReportAi['metrics'][number]): string {
    if (metric.key === 'cost') {
        return `$${metric.value.toFixed(2)}`;
    }

    const share =
        metric.key !== 'total' && total() > 0
            ? ` (${Math.round((metric.value / total()) * 100)}%)`
            : '';

    return `${formatNumber(metric.value)}${share}`;
}

function good(metric: ReportAi['metrics'][number]): boolean {
    const t = metric.trend ?? 0;

    return meta[metric.key].lowerIsBetter ? t <= 0 : t >= 0;
}
</script>

<template>
    <DashCard :title="t('AI statistika')">
        <div class="grid gap-4">
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <li
                    v-for="metric in data.metrics"
                    :key="metric.key"
                    class="group flex items-center gap-2.5"
                >
                    <span
                        :class="
                            cn(
                                'flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform group-hover:scale-110',
                                meta[metric.key].tint,
                            )
                        "
                    >
                        <component :is="meta[metric.key].icon" class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] text-navy-500">
                            {{ meta[metric.key].label }}
                        </span>
                        <span
                            class="block text-[13px] font-bold text-navy-950 tabular-nums"
                        >
                            {{ value(metric) }}
                        </span>
                    </span>
                    <span
                        v-if="metric.trend !== null"
                        :class="
                            cn(
                                'inline-flex items-center gap-0.5 text-[11px] font-semibold',
                                good(metric)
                                    ? 'text-emerald-600'
                                    : 'text-red-600',
                            )
                        "
                    >
                        <ArrowUp v-if="metric.trend >= 0" class="size-3" />
                        <ArrowDown v-else class="size-3" />
                        {{ Math.abs(metric.trend) }}%
                    </span>
                </li>
            </ul>
            <div class="border-t border-line pt-3">
                <LineChart
                    :labels="data.labels"
                    :series="[
                        {
                            key: 'ai',
                            label: t('So\'rovlar'),
                            color: '#8b5cf6',
                            values: data.series,
                        },
                    ]"
                    :height="200"
                    :aria-label="t('AI so\'rovlari dinamikasi')"
                />
                <p class="mt-1 text-right text-[11px] text-navy-400">
                    {{
                        t('Tokenlar: :count', {
                            count: formatNumber(data.tokens),
                        })
                    }}
                </p>
            </div>
        </div>
    </DashCard>
</template>

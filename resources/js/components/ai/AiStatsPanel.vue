<script setup lang="ts">
import { CircleCheck, CircleX, Clock3, Coins, Sparkles } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatDate, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiStats } from '@/types';
import { t } from '@/lib/i18n';

/** "AI statistika" (oxirgi 30 kun) */
const props = defineProps<{ stats: AiStats; global: boolean }>();

const rows = computed<
    { label: string; value: string; icon: Component; tint: string }[]
>(() => [
    {
        label: t("Jami so'rovlar"),
        value: formatNumber(props.stats.total),
        icon: Sparkles,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        label: t('Muvaffaqiyatli'),
        value: `${formatNumber(props.stats.completed)} (${props.stats.completedPct}%)`,
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: t('Xatolar'),
        value: `${formatNumber(props.stats.failed)} (${props.stats.failedPct}%)`,
        icon: CircleX,
        tint: 'bg-red-50 text-red-600',
    },
    {
        label: t('Sarflangan tokenlar'),
        value: formatNumber(props.stats.tokens),
        icon: Coins,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        label: t("O'rtacha ishlash vaqti"),
        value: t(':seconds soniya', { seconds: props.stats.avgSeconds }),
        icon: Clock3,
        tint: 'bg-amber-50 text-amber-600',
    },
]);
</script>

<template>
    <DashCard :title="global ? t('AI statistika') : t('Mening statistikam')">
        <template #actions>
            <span class="text-[11px] text-navy-400"
                >{{ formatDate(stats.from) }} – {{ formatDate(stats.to) }}</span
            >
        </template>
        <ul class="grid grid-cols-1 gap-3">
            <li
                v-for="row in rows"
                :key="row.label"
                class="flex items-center gap-3"
            >
                <span
                    :class="
                        cn(
                            'flex size-8 shrink-0 items-center justify-center rounded-lg',
                            row.tint,
                        )
                    "
                >
                    <component :is="row.icon" class="size-4" />
                </span>
                <span class="min-w-0 flex-1 text-[13px] text-navy-600">{{
                    row.label
                }}</span>
                <span
                    class="text-[13px] font-semibold text-navy-950 tabular-nums"
                    >{{ row.value }}</span
                >
            </li>
        </ul>
    </DashCard>
</template>

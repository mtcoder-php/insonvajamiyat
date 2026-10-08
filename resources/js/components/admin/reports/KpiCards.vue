<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    CircleCheck,
    CircleX,
    Clock3,
    Eye,
    FileText,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportKpi, ReportKpiKey } from '@/types';
import { t } from '@/lib/i18n';

/**
 * 6 ta asosiy ko'rsatkich. O'zgarish rangi ko'rsatkich "yaxshi" yo'nalishiga qarab:
 * rad etilganlar va taqriz vaqti kamaysa — yashil.
 */
defineProps<{ kpis: ReportKpi[]; days: number }>();

const meta: Record<
    ReportKpiKey,
    { label: string; icon: Component; tint: string; suffix?: string }
> = {
    submitted: {
        label: t('Yuborilgan maqolalar'),
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600 ring-brand-100',
    },
    accepted: {
        label: t('Qabul qilingan'),
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    },
    rejected: {
        label: t('Rad etilgan'),
        icon: CircleX,
        tint: 'bg-red-50 text-red-600 ring-red-100',
    },
    review_days: {
        label: t("O'rtacha taqriz vaqti"),
        icon: Clock3,
        tint: 'bg-amber-50 text-amber-600 ring-amber-100',
        suffix: 'kun',
    },
    authors: {
        label: t('Faol mualliflar'),
        icon: UsersRound,
        tint: 'bg-violet-50 text-violet-600 ring-violet-100',
    },
    views: {
        label: t("Maqola ko'rishlari"),
        icon: Eye,
        tint: 'bg-cyan-50 text-cyan-700 ring-cyan-100',
    },
};

function good(kpi: ReportKpi): boolean {
    const trend = kpi.trend ?? 0;

    return kpi.better === 'up' ? trend >= 0 : trend <= 0;
}

function display(kpi: ReportKpi): string {
    if (kpi.value === null) {
        return '—';
    }

    return Number.isInteger(kpi.value)
        ? formatNumber(kpi.value)
        : kpi.value.toFixed(1).replace('.', ',');
}
</script>

<template>
    <section
        class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 2xl:grid-cols-6"
        :aria-label="t('Asosiy ko\'rsatkichlar')"
    >
        <article
            v-for="kpi in kpis"
            :key="kpi.key"
            class="group rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_16px_32px_-16px_rgba(0,36,66,0.3)]"
        >
            <div class="flex items-center gap-3">
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-full ring-4 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-[-6deg]',
                            meta[kpi.key].tint,
                        )
                    "
                >
                    <component
                        :is="meta[kpi.key].icon"
                        class="size-5"
                        :stroke-width="1.8"
                    />
                </span>
                <p class="text-xs leading-4 font-semibold text-navy-700">
                    {{ meta[kpi.key].label }}
                </p>
            </div>
            <p
                class="mt-3 font-sans text-[26px] leading-none font-bold text-navy-950 tabular-nums"
            >
                {{ display(kpi) }}
                <span
                    v-if="meta[kpi.key].suffix && kpi.value !== null"
                    class="text-base font-semibold text-navy-500"
                    >{{ meta[kpi.key].suffix }}</span
                >
            </p>
            <p class="mt-2 flex items-center gap-1 text-xs">
                <span
                    v-if="kpi.trend !== null"
                    :class="
                        cn(
                            'inline-flex items-center gap-0.5 rounded-full px-1.5 py-px font-semibold',
                            good(kpi)
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-red-50 text-red-700',
                        )
                    "
                >
                    <ArrowUp v-if="kpi.trend >= 0" class="size-3" />
                    <ArrowDown v-else class="size-3" />
                    {{ Math.abs(kpi.trend) }}%
                </span>
                <span v-else class="font-semibold text-navy-400">—</span>
            </p>
            <p class="mt-1 text-[11px] leading-tight text-navy-400">
                {{ t('oldingi :days kunga nisbatan', { days }) }}
            </p>
        </article>
    </section>
</template>

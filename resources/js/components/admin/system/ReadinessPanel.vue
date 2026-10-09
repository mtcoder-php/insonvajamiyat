<script setup lang="ts">
import {
    CircleCheck,
    CircleMinus,
    CircleX,
    Rocket,
    Terminal,
    TriangleAlert,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { LaunchReadinessReport, SystemStatusRow } from '@/types';

/**
 * "Ishga tushirishga tayyorlik" — sozlamalar, fon jarayonlari (cron, worker), kontent,
 * to'lovlar, xavfsizlik va zaxira bo'yicha tekshiruvlar (php artisan app:launch-check bilan bir xil).
 */
const props = defineProps<{ report: LaunchReadinessReport }>();

const styles: Record<
    SystemStatusRow['state'],
    { icon: Component; badge: string; label: string }
> = {
    ok: {
        icon: CircleCheck,
        badge: 'bg-emerald-50 text-emerald-600',
        label: t('Joyida'),
    },
    warning: {
        icon: TriangleAlert,
        badge: 'bg-amber-50 text-amber-600',
        label: t('Tavsiya'),
    },
    error: {
        icon: CircleX,
        badge: 'bg-red-50 text-red-600',
        label: t('Xato'),
    },
    off: {
        icon: CircleMinus,
        badge: 'bg-slate-100 text-slate-500',
        label: t('Ulanmagan'),
    },
};

const total = computed(
    () =>
        props.report.counts.ok +
        props.report.counts.warning +
        props.report.counts.error,
);
const percent = computed(() =>
    total.value ? Math.round((props.report.counts.ok / total.value) * 100) : 0,
);

// Doira: r = 34, aylana uzunligi ≈ 213.6
const circumference = 2 * Math.PI * 34;
const dash = computed(() => (percent.value / 100) * circumference);

function groupState(
    checks: LaunchReadinessReport['groups'][number]['checks'],
): SystemStatusRow['state'] {
    if (checks.some((c) => c.state === 'error')) {
        return 'error';
    }

    if (checks.some((c) => c.state === 'warning')) {
        return 'warning';
    }

    return checks.every((c) => c.state === 'off') ? 'off' : 'ok';
}
</script>

<template>
    <section class="grid gap-5">
        <!-- Xulosa -->
        <div
            :class="
                cn(
                    'relative isolate flex flex-col gap-5 overflow-hidden rounded-xl border p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:flex-row sm:items-center',
                    report.ready
                        ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white'
                        : 'border-red-200 bg-gradient-to-br from-red-50 via-white to-white',
                )
            "
        >
            <div class="relative size-24 shrink-0">
                <svg viewBox="0 0 80 80" class="size-24 -rotate-90">
                    <circle
                        cx="40"
                        cy="40"
                        r="34"
                        fill="none"
                        stroke-width="8"
                        class="stroke-[#e8eef6]"
                    />
                    <circle
                        cx="40"
                        cy="40"
                        r="34"
                        fill="none"
                        stroke-width="8"
                        stroke-linecap="round"
                        :stroke-dasharray="`${dash} ${circumference}`"
                        :class="
                            report.ready
                                ? 'stroke-emerald-500'
                                : 'stroke-amber-500'
                        "
                        class="transition-[stroke-dasharray] duration-700"
                    />
                </svg>
                <span
                    class="absolute inset-0 flex items-center justify-center text-xl font-bold text-navy-950 tabular-nums"
                    >{{ percent }}%</span
                >
            </div>

            <div class="min-w-0 flex-1">
                <h2
                    class="flex items-center gap-2 font-sans text-lg font-bold text-navy-950"
                >
                    <Rocket
                        :class="
                            cn(
                                'size-5',
                                report.ready
                                    ? 'text-emerald-600'
                                    : 'text-red-600',
                            )
                        "
                    />
                    {{
                        report.ready
                            ? t('Ishga tushirishga tayyor')
                            : t(
                                  'Ishga tushirishdan oldin :count ta xatoni tuzating',
                                  { count: report.counts.error },
                              )
                    }}
                </h2>
                <p class="mt-1 text-[13px] text-navy-600">
                    {{
                        t(
                            "Sozlamalar, fon jarayonlari, kontent, to'lovlar, xavfsizlik va zaxira bo'yicha tekshiruv.",
                        )
                    }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="state in [
                            'ok',
                            'warning',
                            'error',
                            'off',
                        ] as const"
                        :key="state"
                        :class="
                            cn(
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums',
                                styles[state].badge,
                            )
                        "
                    >
                        <component :is="styles[state].icon" class="size-3.5" />
                        {{ styles[state].label }}: {{ report.counts[state] }}
                    </span>
                </div>
            </div>

            <code
                class="inline-flex shrink-0 items-center gap-2 self-start rounded-lg bg-navy-950 px-3 py-2 font-mono text-xs text-gold-300 sm:self-center"
                :title="t('Serverda ham shu tekshiruv')"
            >
                <Terminal class="size-3.5" />
                php artisan app:launch-check
            </code>
        </div>

        <!-- Guruhlar -->
        <div class="grid gap-5 xl:grid-cols-2">
            <article
                v-for="group in report.groups"
                :key="group.key"
                class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_16px_34px_-24px_rgba(0,36,66,0.45)]"
            >
                <header
                    class="flex items-center justify-between gap-3 border-b border-line bg-[#f8fafd] px-5 py-3"
                >
                    <h3 class="text-[14px] font-bold text-navy-950">
                        {{ group.label }}
                    </h3>
                    <span
                        :class="
                            cn(
                                'flex size-7 items-center justify-center rounded-lg',
                                styles[groupState(group.checks)].badge,
                            )
                        "
                        :title="styles[groupState(group.checks)].label"
                    >
                        <component
                            :is="styles[groupState(group.checks)].icon"
                            class="size-4"
                        />
                    </span>
                </header>
                <ul class="divide-y divide-line">
                    <li
                        v-for="check in group.checks"
                        :key="check.key"
                        class="flex items-start gap-3 px-5 py-3 transition-colors hover:bg-brand-50/30"
                    >
                        <span
                            :class="
                                cn(
                                    'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md',
                                    styles[check.state].badge,
                                )
                            "
                            :title="styles[check.state].label"
                        >
                            <component
                                :is="styles[check.state].icon"
                                class="size-3.5"
                            />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-x-3"
                            >
                                <p
                                    class="text-[13px] font-semibold text-navy-900"
                                >
                                    {{ check.label }}
                                </p>
                                <p
                                    class="text-xs [overflow-wrap:anywhere] text-navy-500"
                                >
                                    {{ check.value }}
                                </p>
                            </div>
                            <p
                                v-if="check.hint && check.state !== 'ok'"
                                :class="
                                    cn(
                                        'mt-1 text-xs leading-relaxed',
                                        check.state === 'error'
                                            ? 'text-red-700'
                                            : 'text-navy-500',
                                    )
                                "
                            >
                                {{ check.hint }}
                            </p>
                        </div>
                    </li>
                </ul>
            </article>
        </div>
    </section>
</template>

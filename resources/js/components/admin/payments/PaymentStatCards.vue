<script setup lang="ts">
import { ArrowDown, ArrowUp, Hourglass, Landmark, Wallet } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber, formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PaymentStats, PaymentTab } from '@/types';
import { t } from '@/lib/i18n';

/**
 * To'lovlar statistikasi: jami tushum, Click, Payme, qo'lda tasdiqlangan, to'lov kutilmoqda.
 * Karta bosilganda tegishli tab ochiladi.
 */
const props = defineProps<{ stats: PaymentStats; active: PaymentTab }>();

const emit = defineEmits<{ select: [tab: PaymentTab] }>();

type Card = {
    key: string;
    tab: PaymentTab;
    label: string;
    amount: number;
    hint: string;
    icon: Component | null;
    letter?: string;
    tint: string;
    share?: number | null;
    bar?: string;
    trend?: number | null;
};

const cards = computed<Card[]>(() => [
    {
        key: 'revenue',
        tab: 'all',
        label: t('Jami tushum'),
        amount: props.stats.revenue.amount,
        hint: `Shu oy: ${formatSum(props.stats.revenue.month)}`,
        icon: Wallet,
        tint: 'bg-emerald-50 text-emerald-600',
        trend: props.stats.revenue.trend,
    },
    {
        key: 'click',
        tab: 'click',
        label: t("Click to'lovlar"),
        amount: props.stats.click.amount,
        hint: `${formatNumber(props.stats.click.count)} ta to'lov`,
        icon: null,
        letter: 'C',
        tint: 'bg-[#1a82f7] text-white',
        share: props.stats.click.share,
        bar: 'bg-[#1a82f7]',
    },
    {
        key: 'payme',
        tab: 'payme',
        label: t("Payme to'lovlar"),
        amount: props.stats.payme.amount,
        hint: `${formatNumber(props.stats.payme.count)} ta to'lov`,
        icon: null,
        letter: 'P',
        tint: 'bg-[#0fa37f] text-white',
        share: props.stats.payme.share,
        bar: 'bg-[#0fa37f]',
    },
    {
        key: 'manual',
        tab: 'manual',
        label: t("Qo'lda tasdiqlangan"),
        amount: props.stats.manual.amount,
        hint: `${formatNumber(props.stats.manual.count)} ta to'lov`,
        icon: Landmark,
        tint: 'bg-violet-50 text-violet-600',
        share: props.stats.manual.share,
        bar: 'bg-[#8b5cf6]',
    },
    {
        key: 'awaiting',
        tab: 'awaiting',
        label: t("To'lov kutilmoqda"),
        amount: props.stats.awaiting.amount,
        hint: `${formatNumber(props.stats.awaiting.count)} ta maqola`,
        icon: Hourglass,
        tint: 'bg-amber-50 text-amber-600',
    },
]);
</script>

<template>
    <section
        class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 2xl:grid-cols-5 [&>*:last-child]:col-span-2 lg:[&>*:last-child]:col-span-1"
        :aria-label="t('To\'lovlar statistikasi')"
    >
        <button
            v-for="card in cards"
            :key="card.key"
            type="button"
            :class="
                cn(
                    'group rounded-xl border bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_32px_-16px_rgba(0,36,66,0.3)]',
                    active === card.tab
                        ? 'border-brand-300 ring-2 ring-brand-100'
                        : 'border-line',
                )
            "
            @click="emit('select', card.tab)"
        >
            <span class="flex items-center gap-3">
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-transform duration-300 group-hover:scale-110',
                            card.tint,
                        )
                    "
                >
                    <component
                        :is="card.icon"
                        v-if="card.icon"
                        class="size-5"
                    />
                    <template v-else>{{ card.letter }}</template>
                </span>
                <span class="text-[13px] font-semibold text-navy-800">
                    {{ card.label }}
                </span>
            </span>
            <p
                class="mt-3 font-sans text-xl leading-tight font-bold text-navy-950 tabular-nums"
            >
                {{ formatSum(card.amount) }}
            </p>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-navy-500">
                <span
                    v-if="card.trend != null"
                    :class="
                        cn(
                            'inline-flex items-center gap-0.5 font-semibold',
                            card.trend >= 0
                                ? 'text-emerald-600'
                                : 'text-red-600',
                        )
                    "
                >
                    <ArrowUp v-if="card.trend >= 0" class="size-3.5" />
                    <ArrowDown v-else class="size-3.5" />
                    {{ Math.abs(card.trend) }}%
                </span>
                {{ card.hint }}
            </p>
            <span
                v-if="card.bar"
                class="mt-3 block h-1.5 overflow-hidden rounded-full bg-navy-50"
                :title="t('Ulushi: :value%', { value: card.share ?? 0 })"
            >
                <span
                    :class="
                        cn(
                            'block h-full rounded-full transition-[width] duration-700',
                            card.bar,
                        )
                    "
                    :style="{ width: `${card.share ?? 0}%` }"
                />
            </span>
        </button>
    </section>
</template>

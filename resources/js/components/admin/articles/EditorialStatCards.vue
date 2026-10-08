<script setup lang="ts">
import {
    BookOpenCheck,
    FilePenLine,
    FileText,
    Hourglass,
    Sparkles,
} from '@lucide/vue';
import type { Component } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { EditorialQueue, EditorialStat } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muharrir ish joyi statistikasi; karta bosilganda shu navbat ochiladi.
 */
defineProps<{ stats: EditorialStat[]; active: EditorialQueue }>();

const emit = defineEmits<{ select: [queue: EditorialQueue] }>();

const meta: Record<
    EditorialStat['key'],
    { label: string; icon: Component; tint: string; hint: string }
> = {
    all: {
        label: t('Jami maqolalar'),
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600',
        hint: t('shu oy yuborilgan'),
    },
    new: {
        label: t('Yangi maqolalar'),
        icon: Sparkles,
        tint: 'bg-sky-50 text-sky-600',
        hint: t('shu oy'),
    },
    reviewing: {
        label: t("Ko'rib chiqilayotganlar"),
        icon: Hourglass,
        tint: 'bg-violet-50 text-violet-600',
        hint: t("shu oy o'zgargan"),
    },
    revision: {
        label: t('Tuzatish talab qilinganlar'),
        icon: FilePenLine,
        tint: 'bg-amber-50 text-amber-600',
        hint: t('shu oy'),
    },
    accepted: {
        label: t('Nashrga tayyorlar'),
        icon: BookOpenCheck,
        tint: 'bg-emerald-50 text-emerald-600',
        hint: t('shu oy qabul qilingan'),
    },
};
</script>

<template>
    <section
        class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 2xl:grid-cols-5 [&>*:last-child]:col-span-2 lg:[&>*:last-child]:col-span-1"
        :aria-label="t('Maqolalar statistikasi')"
    >
        <button
            v-for="stat in stats"
            :key="stat.key"
            type="button"
            :class="
                cn(
                    'group flex items-center gap-3.5 rounded-xl border bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_32px_-16px_rgba(0,36,66,0.3)]',
                    active === stat.key
                        ? 'border-brand-300 ring-2 ring-brand-100'
                        : 'border-line',
                )
            "
            @click="emit('select', stat.key)"
        >
            <span
                :class="
                    cn(
                        'flex size-12 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6',
                        meta[stat.key].tint,
                    )
                "
            >
                <component :is="meta[stat.key].icon" class="size-5" />
            </span>
            <span class="min-w-0">
                <span
                    class="block truncate text-[13px] font-semibold text-navy-800"
                >
                    {{ meta[stat.key].label }}
                </span>
                <span
                    class="block font-sans text-2xl leading-tight font-bold text-navy-950 tabular-nums"
                >
                    {{ formatNumber(stat.value) }}
                </span>
                <span class="block text-xs text-navy-500">
                    <span
                        v-if="stat.month > 0"
                        class="font-semibold text-emerald-600"
                        >+{{ stat.month }}</span
                    >
                    <span v-else class="text-navy-400">0</span>
                    {{ meta[stat.key].hint }}
                </span>
            </span>
        </button>
    </section>
</template>

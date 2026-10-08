<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    BookOpenCheck,
    CircleCheck,
    Clock3,
    FileText,
    FilePenLine,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { StatCardData } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Statistika kartasi: rangli ikonka, nom, qiymat va o'tgan davrga nisbatan o'zgarish.
 */
const props = defineProps<{ card: StatCardData }>();

const meta: Record<
    StatCardData['key'],
    { label: string; icon: Component; tint: string }
> = {
    total: {
        label: t('Jami maqolalar'),
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600',
    },
    reviewing: {
        label: t("Ko'rib chiqilayotganlar"),
        icon: Clock3,
        tint: 'bg-amber-50 text-amber-600',
    },
    revision: {
        label: t('Tuzatish talab qilinganlar'),
        icon: FilePenLine,
        tint: 'bg-red-50 text-red-600',
    },
    accepted: {
        label: t('Qabul qilinganlar'),
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    published: {
        label: t('Nashr etilganlar'),
        icon: BookOpenCheck,
        tint: 'bg-violet-50 text-violet-600',
    },
};

const info = computed(() => meta[props.card.key]);
const periodText = computed(() =>
    props.card.period === 'week'
        ? t("o'tgan haftaga nisbatan")
        : t("o'tgan oyga nisbatan"),
);
const trendUp = computed(() => (props.card.trend ?? 0) >= 0);
</script>

<template>
    <article
        class="group @container rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_32px_-16px_rgba(0,36,66,0.3)]"
    >
        <div class="flex flex-col gap-3 @[13rem]:flex-row @[13rem]:gap-4">
            <span
                :class="
                    cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                        info.tint,
                    )
                "
            >
                <component :is="info.icon" class="size-5" :stroke-width="1.8" />
            </span>
            <div class="min-w-0">
                <p
                    class="line-clamp-2 min-h-8 text-xs leading-4 font-semibold text-navy-900 @[13rem]:min-h-0"
                    :title="info.label"
                >
                    {{ info.label }}
                </p>
                <p
                    class="mt-1.5 font-sans text-[28px] leading-none font-bold text-navy-950 tabular-nums"
                >
                    {{ formatNumber(card.value) }}
                </p>
                <p class="mt-2 flex items-center gap-1 text-xs">
                    <template v-if="card.trend !== null">
                        <span
                            :class="
                                cn(
                                    'inline-flex items-center gap-0.5 font-semibold',
                                    trendUp
                                        ? 'text-emerald-600'
                                        : 'text-red-600',
                                )
                            "
                        >
                            <ArrowUp v-if="trendUp" class="size-3.5" />
                            <ArrowDown v-else class="size-3.5" />
                            {{ Math.abs(card.trend) }}%
                        </span>
                    </template>
                    <span v-else class="font-semibold text-navy-400">—</span>
                </p>
                <p class="mt-0.5 text-[11px] leading-tight text-navy-400">
                    {{ periodText }}
                </p>
            </div>
        </div>
    </article>
</template>

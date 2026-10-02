<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    BrainCircuit,
    ClipboardPen,
    FilePlus2,
    Wallet,
} from '@lucide/vue';
import type { Component } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatNumber, formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportQuickItem } from '@/types';

/**
 * "Tezkor ma'lumotlar": bugungi ko'rsatkichlar (kechagiga nisbatan) va kutilayotgan taqrizlar.
 */
defineProps<{ items: ReportQuickItem[] }>();

const meta: Record<
    ReportQuickItem['key'],
    { label: string; icon: Component; tint: string; sum?: boolean }
> = {
    submitted: {
        label: 'Bugungi maqolalar',
        icon: FilePlus2,
        tint: 'bg-brand-50 text-brand-600',
    },
    reviews: {
        label: 'Kutilayotgan taqrizlar',
        icon: ClipboardPen,
        tint: 'bg-amber-50 text-amber-600',
    },
    payments: {
        label: "To'lovlar (bugun)",
        icon: Wallet,
        tint: 'bg-emerald-50 text-emerald-600',
        sum: true,
    },
    ai: {
        label: "AI so'rovlari (bugun)",
        icon: BrainCircuit,
        tint: 'bg-violet-50 text-violet-600',
    },
};
</script>

<template>
    <DashCard title="Tezkor ma'lumotlar">
        <ul class="space-y-1">
            <li
                v-for="item in items"
                :key="item.key"
                class="group -mx-2 flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-surface-muted"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-105',
                            meta[item.key].tint,
                        )
                    "
                >
                    <component
                        :is="meta[item.key].icon"
                        class="size-5"
                        :stroke-width="1.8"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-navy-500">
                        {{ meta[item.key].label }}
                    </p>
                    <p class="font-bold text-navy-950 tabular-nums">
                        {{
                            meta[item.key].sum
                                ? formatSum(item.value)
                                : `${formatNumber(item.value)} ta`
                        }}
                    </p>
                    <p v-if="item.hint" class="text-[11px] text-red-600">
                        {{ item.hint }}
                    </p>
                </div>
                <span
                    v-if="item.trend !== null"
                    :class="
                        cn(
                            'inline-flex items-center gap-0.5 text-xs font-semibold',
                            item.trend >= 0
                                ? 'text-emerald-600'
                                : 'text-red-600',
                        )
                    "
                    title="Kechagiga nisbatan"
                >
                    <ArrowUp v-if="item.trend >= 0" class="size-3.5" />
                    <ArrowDown v-else class="size-3.5" />
                    {{ Math.abs(item.trend) }}%
                </span>
            </li>
        </ul>
    </DashCard>
</template>

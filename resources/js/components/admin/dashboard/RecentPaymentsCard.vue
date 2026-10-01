<script setup lang="ts">
import { Banknote } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatDateTime, formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { RecentPayment } from '@/types';

/**
 * "So'nggi to'lovlar": provayder belgisi, summa, holat va sana.
 */
defineProps<{ items: RecentPayment[] }>();

const provider: Record<
    RecentPayment['provider'],
    { label: string; mark: string; class: string }
> = {
    click: { label: 'Click', mark: 'C', class: 'bg-[#e8f3ff] text-[#0b6bd3]' },
    payme: { label: 'Payme', mark: 'P', class: 'bg-[#e6f7f3] text-[#0a7d61]' },
    manual: { label: "Qo'lda", mark: '', class: 'bg-navy-50 text-navy-700' },
};

const statusClass: Record<string, string> = {
    paid: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    failed: 'bg-red-50 text-red-700',
    cancelled: 'bg-navy-50 text-navy-600',
    refunded: 'bg-violet-50 text-violet-700',
};
</script>

<template>
    <DashCard title="So'nggi to'lovlar">
        <ul v-if="items.length" class="-mx-2 space-y-1">
            <li
                v-for="item in items"
                :key="item.id"
                class="group flex items-center gap-2.5 rounded-lg px-2 py-2.5 transition-colors hover:bg-[#f6f8fb]"
            >
                <span
                    :class="
                        cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-full text-base font-extrabold transition-transform duration-300 group-hover:scale-110',
                            provider[item.provider].class,
                        )
                    "
                    :title="provider[item.provider].label"
                >
                    <Banknote
                        v-if="item.provider === 'manual'"
                        class="size-4"
                    />
                    <template v-else>{{
                        provider[item.provider].mark
                    }}</template>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p
                            class="text-sm font-semibold whitespace-nowrap text-navy-950 tabular-nums"
                        >
                            {{ formatSum(item.amount) }}
                        </p>
                        <span
                            :class="
                                cn(
                                    'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap',
                                    statusClass[item.status] ??
                                        'bg-navy-50 text-navy-600',
                                )
                            "
                        >
                            {{ item.statusLabel }}
                        </span>
                    </div>
                    <p class="mt-0.5 truncate text-[11px] text-navy-500">
                        {{ provider[item.provider].label }} ·
                        <span class="tabular-nums">{{
                            formatDateTime(item.date)
                        }}</span>
                    </p>
                </div>
            </li>
        </ul>
        <p v-else class="py-8 text-center text-sm text-navy-500">
            Hozircha to'lovlar yo'q
        </p>
    </DashCard>
</template>

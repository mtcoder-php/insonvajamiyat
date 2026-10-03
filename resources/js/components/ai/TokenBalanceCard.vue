<script setup lang="ts">
import { Coins, Infinity as InfinityIcon } from '@lucide/vue';
import { computed } from 'vue';
import { formatDate, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiBudget } from '@/types';

/** Oylik token balansi: qolgan, sarflangan va limit */
const props = defineProps<{ budget: AiBudget }>();

const percent = computed(() =>
    props.budget.limit > 0
        ? Math.min(
              100,
              Math.round((props.budget.used / props.budget.limit) * 100),
          )
        : 0,
);
</script>

<template>
    <section
        class="relative overflow-hidden rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow hover:shadow-[0_12px_28px_-18px_rgba(0,36,66,0.35)]"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium text-navy-500">Qolgan tokenlar</p>
                <p
                    class="mt-1 font-sans text-2xl font-bold text-navy-950 tabular-nums"
                >
                    <template v-if="budget.remaining === null">
                        <InfinityIcon class="inline size-6" /> Cheklanmagan
                    </template>
                    <template v-else>{{
                        formatNumber(budget.remaining)
                    }}</template>
                </p>
            </div>
            <span
                class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
            >
                <Coins class="size-5" />
            </span>
        </div>
        <template v-if="budget.limit > 0">
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#edf2f8]">
                <div
                    :class="
                        cn(
                            'h-full rounded-full transition-[width] duration-700',
                            percent >= 90
                                ? 'bg-red-500'
                                : percent >= 70
                                  ? 'bg-amber-500'
                                  : 'bg-brand-500',
                        )
                    "
                    :style="{ width: `${percent}%` }"
                />
            </div>
            <p class="mt-2 flex justify-between text-[11px] text-navy-500">
                <span>Sarflandi: {{ formatNumber(budget.used) }}</span>
                <span>Limit: {{ formatNumber(budget.limit) }}</span>
            </p>
        </template>
        <p v-else class="mt-2 text-[11px] text-navy-500">
            Bu oy sarflandi: {{ formatNumber(budget.used) }} token
        </p>
        <p class="mt-1 text-[11px] text-navy-400">
            {{ budget.personal ? 'Shaxsiy limit · ' : '' }}Yangilanadi:
            {{ formatDate(budget.resetsAt) }}
        </p>
    </section>
</template>

<script setup lang="ts">
import { Check, Hourglass } from '@lucide/vue';
import { computed } from 'vue';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReviewDetail } from '@/types';

/**
 * Taqrizlash jarayoni: taklif → qabul → taqriz → muharrir qarori.
 */
const props = defineProps<{ review: ReviewDetail }>();

type Step = { title: string; hint: string; state: 'done' | 'current' | 'todo' };

const steps = computed<Step[]>(() => {
    const r = props.review;
    const accepted = ['accepted', 'completed'].includes(r.status);
    const completed = r.status === 'completed';

    return [
        {
            title: 'Taklif yuborildi',
            hint: formatDate(r.invitedAt),
            state: 'done',
        },
        {
            title: 'Taklif qabul qilindi',
            hint: accepted
                ? formatDate(r.respondedAt)
                : r.status === 'invited'
                  ? 'Javobingiz kutilmoqda'
                  : r.statusLabel,
            state: accepted
                ? 'done'
                : r.status === 'invited'
                  ? 'current'
                  : 'todo',
        },
        {
            title: 'Taqriz jarayoni',
            hint: completed
                ? formatDate(r.completedAt)
                : r.dueAt
                  ? `Muddat: ${formatDate(r.dueAt)}`
                  : '—',
            state: completed
                ? 'done'
                : r.status === 'accepted'
                  ? 'current'
                  : 'todo',
        },
        {
            title: 'Muharrir qarori',
            hint: r.decisionMade ? 'Qaror qabul qilindi' : 'Kutilmoqda',
            state: r.decisionMade ? 'done' : completed ? 'current' : 'todo',
        },
    ];
});

const dueChip = computed(() => {
    const d = props.review.daysLeft;

    if (d === null) {
        return null;
    }

    return d < 0
        ? `${Math.abs(d)} kun kechikdi`
        : d === 0
          ? 'Bugun tugaydi'
          : `${d} kun qoldi`;
});
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="font-sans text-[15px] font-bold text-navy-950">
                Taqrizlash jarayoni
            </h2>
            <span
                v-if="dueChip"
                :class="
                    cn(
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
                        (review.daysLeft ?? 0) < 3
                            ? 'bg-red-50 text-red-700 ring-red-200'
                            : 'bg-amber-50 text-amber-700 ring-amber-200',
                    )
                "
            >
                <Hourglass class="size-3.5" /> {{ dueChip }}
            </span>
        </div>
        <ol class="grid gap-4 sm:grid-cols-4">
            <li
                v-for="(step, i) in steps"
                :key="step.title"
                class="relative flex items-start gap-3"
            >
                <span
                    :class="
                        cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-transform hover:scale-105',
                            step.state === 'done' &&
                                'bg-emerald-500 text-white shadow-[0_6px_14px_-6px_rgba(16,185,129,0.8)]',
                            step.state === 'current' &&
                                'bg-brand-600 text-white shadow-[0_6px_14px_-6px_rgba(0,108,246,0.9)] ring-4 ring-brand-100',
                            step.state === 'todo' &&
                                'bg-navy-100 text-navy-400',
                        )
                    "
                >
                    <Check
                        v-if="step.state === 'done'"
                        class="size-4"
                        :stroke-width="3"
                    />
                    <template v-else>{{ i + 1 }}</template>
                </span>
                <span class="min-w-0">
                    <span
                        :class="
                            cn(
                                'block text-[13px] font-semibold',
                                step.state === 'todo'
                                    ? 'text-navy-400'
                                    : 'text-navy-900',
                            )
                        "
                    >
                        {{ step.title }}
                    </span>
                    <span class="mt-0.5 block text-[11px] text-navy-500">
                        {{ step.hint }}
                    </span>
                </span>
            </li>
        </ol>
    </section>
</template>

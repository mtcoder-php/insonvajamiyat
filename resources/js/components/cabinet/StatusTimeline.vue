<script setup lang="ts">
import { Check, X } from '@lucide/vue';
import { formatDate, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { TimelineStep } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Maqola holati — vertikal timeline (dizayn: "Maqolaning holati").
 * done — yashil belgi, current — ko'k halqa ("Hozirda"), pending — kulrang ("Kutilmoqda"),
 * skipped — o'tkazib yuborilgan qadam, failed — rad etilgan / qaytarib olingan.
 */
defineProps<{ steps: TimelineStep[] }>();

const caption = (step: TimelineStep): string => {
    if (step.state === 'current') {
        return t('Hozirda');
    }

    if (step.state === 'pending') {
        return t('Kutilmoqda');
    }

    if (step.state === 'skipped') {
        return t('Talab qilinmadi');
    }

    return step.date ? `${formatDate(step.date)} ${formatTime(step.date)}` : '';
};
</script>

<template>
    <ol class="relative">
        <li
            v-for="(step, i) in steps"
            :key="step.key"
            class="group/step relative flex gap-3 pb-5 last:pb-0"
        >
            <!-- Bog'lovchi chiziq -->
            <span
                v-if="i < steps.length - 1"
                :class="
                    cn(
                        'absolute top-6 bottom-0 left-[11px] w-0.5 rounded-full',
                        step.state === 'done' ? 'bg-emerald-500' : 'bg-line',
                    )
                "
                aria-hidden="true"
            />
            <span
                :class="
                    cn(
                        'relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover/step:scale-110',
                        step.state === 'done' && 'bg-emerald-500 text-white',
                        step.state === 'current' &&
                            'bg-white ring-[5px] ring-brand-600 ring-inset',
                        step.state === 'pending' && 'bg-navy-100',
                        step.state === 'skipped' &&
                            'border-2 border-dashed border-navy-200 bg-white',
                        step.state === 'failed' && 'bg-red-500 text-white',
                    )
                "
            >
                <Check
                    v-if="step.state === 'done'"
                    class="size-3.5"
                    :stroke-width="3"
                />
                <X
                    v-else-if="step.state === 'failed'"
                    class="size-3.5"
                    :stroke-width="3"
                />
                <span
                    v-if="step.state === 'current'"
                    class="absolute inset-0 animate-ping rounded-full bg-brand-500/30"
                    aria-hidden="true"
                />
            </span>
            <div class="min-w-0 flex-1 pt-0.5">
                <p
                    :class="
                        cn(
                            'text-[13px] font-semibold',
                            step.state === 'current' && 'text-brand-700',
                            step.state === 'done' && 'text-navy-900',
                            step.state === 'failed' && 'text-red-700',
                            (step.state === 'pending' ||
                                step.state === 'skipped') &&
                                'text-navy-400',
                        )
                    "
                >
                    {{ step.label }}
                </p>
                <p
                    :class="
                        cn(
                            'mt-0.5 text-[11px] tabular-nums',
                            step.state === 'current'
                                ? 'font-medium text-brand-600'
                                : 'text-navy-400',
                        )
                    "
                >
                    {{ caption(step) }}
                </p>
            </div>
            <span
                v-if="step.state === 'done'"
                class="mt-1 flex size-5 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
            >
                <Check class="size-3" :stroke-width="3" />
            </span>
        </li>
    </ol>
</template>

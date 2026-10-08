<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { cn } from '@/lib/utils';
import type { WizardStepInfo } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Yangi maqola formasining bosqichlari (dizayn: "Yangi maqola yuborish jarayoni").
 * done — to'liq to'ldirilgan, current — joriy, todo — hali to'liq emas.
 * Qoralama yaratilmaguncha (1-bosqich saqlanmaguncha) boshqa bosqichlar yopiq.
 */
const props = defineProps<{
    steps: WizardStepInfo[];
    current: number;
    /** Bosqich raqami → to'liqmi */
    done: Record<number, boolean>;
    /** Bosqichga havola (null — yopiq) */
    href: (step: number) => string | null;
}>();

const state = (step: number): 'current' | 'done' | 'todo' =>
    step === props.current ? 'current' : props.done[step] ? 'done' : 'todo';
</script>

<template>
    <nav
        class="rounded-xl border border-line bg-white px-3 py-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:px-5"
        :aria-label="t('Yuborish bosqichlari')"
    >
        <div class="-mx-1 overflow-x-auto px-1 pb-1">
            <ol class="flex min-w-[680px] items-start">
                <li
                    v-for="(step, i) in steps"
                    :key="step.key"
                    class="group/step relative flex flex-1 flex-col items-center gap-2 text-center"
                >
                    <span
                        v-if="i > 0"
                        :class="
                            cn(
                                'absolute top-[18px] right-1/2 h-0.5 w-full -translate-y-1/2 rounded-full transition-colors duration-500',
                                done[steps[i - 1].number] ||
                                    step.number <= current
                                    ? 'bg-brand-400'
                                    : 'bg-line',
                            )
                        "
                        aria-hidden="true"
                    />
                    <component
                        :is="href(step.number) ? Link : 'span'"
                        :href="href(step.number) ?? undefined"
                        preserve-scroll
                        :class="
                            cn(
                                'relative z-10 flex size-9 items-center justify-center rounded-full text-[13px] font-semibold tabular-nums transition-all duration-300',
                                href(step.number) &&
                                    'group-hover/step:-translate-y-0.5 group-hover/step:scale-110',
                                state(step.number) === 'current' &&
                                    'bg-brand-600 text-white shadow-[0_0_0_5px_rgba(0,108,246,0.15)]',
                                state(step.number) === 'done' &&
                                    'bg-emerald-500 text-white shadow-[0_6px_14px_-8px_rgba(5,150,105,0.9)]',
                                state(step.number) === 'todo' &&
                                    (href(step.number)
                                        ? 'border border-line bg-white text-navy-600 group-hover/step:border-brand-300 group-hover/step:text-brand-700'
                                        : 'border border-dashed border-navy-200 bg-navy-50/60 text-navy-300'),
                            )
                        "
                        :aria-current="
                            state(step.number) === 'current'
                                ? 'step'
                                : undefined
                        "
                        :aria-label="
                            t(':number-bosqich: :label', {
                                number: step.number,
                                label: step.label,
                            })
                        "
                    >
                        <Check
                            v-if="state(step.number) === 'done'"
                            class="size-4"
                            :stroke-width="3"
                        />
                        <template v-else>{{ step.number }}</template>
                    </component>
                    <span
                        :class="
                            cn(
                                'text-xs transition-colors',
                                state(step.number) === 'current'
                                    ? 'font-semibold text-navy-950'
                                    : state(step.number) === 'done'
                                      ? 'font-medium text-emerald-700'
                                      : href(step.number)
                                        ? 'text-navy-500 group-hover/step:text-brand-700'
                                        : 'text-navy-300',
                            )
                        "
                    >
                        {{ step.label }}
                    </span>
                </li>
            </ol>
        </div>
    </nav>
</template>

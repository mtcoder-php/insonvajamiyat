<script setup lang="ts">
import {
    BadgeCheck,
    BookOpenCheck,
    Check,
    ChevronRight,
    FileCheck2,
    FileText,
    LayoutTemplate,
    Send,
    UserCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProductionStep } from '@/types';

/**
 * Nashr jarayoni bosqichlari: Qabul → Maket → PDF → Muallif → Son → Bosh muharrir → Nashr.
 */
defineProps<{ steps: ProductionStep[] }>();

const icons: Record<string, Component> = {
    accepted: FileCheck2,
    layout: LayoutTemplate,
    pdf: FileText,
    proof: UserCheck,
    issue: BookOpenCheck,
    approval: BadgeCheck,
    publish: Send,
};
</script>

<template>
    <ol class="flex gap-1 overflow-x-auto pb-1">
        <template v-for="(step, i) in steps" :key="step.key">
            <li
                class="group flex min-w-24 flex-1 flex-col items-center gap-1.5 text-center"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                            step.state === 'done' &&
                                'bg-emerald-500 text-white shadow-[0_8px_18px_-8px_rgba(16,185,129,0.9)]',
                            step.state === 'current' &&
                                'bg-brand-600 text-white shadow-[0_8px_18px_-8px_rgba(0,108,246,0.9)] ring-4 ring-brand-100',
                            step.state === 'todo' &&
                                'bg-navy-50 text-navy-400 ring-1 ring-navy-100',
                        )
                    "
                >
                    <Check
                        v-if="step.state === 'done'"
                        class="size-4"
                        :stroke-width="3"
                    />
                    <component :is="icons[step.key]" v-else class="size-4" />
                </span>
                <span
                    :class="
                        cn(
                            'text-xs font-semibold',
                            step.state === 'current'
                                ? 'text-brand-700'
                                : step.state === 'done'
                                  ? 'text-navy-900'
                                  : 'text-navy-400',
                        )
                    "
                >
                    {{ step.label }}
                </span>
                <span class="text-[10px] text-navy-400 tabular-nums">
                    {{
                        step.date
                            ? formatDate(step.date)
                            : step.state === 'current'
                              ? 'Hozir'
                              : ''
                    }}
                </span>
            </li>
            <li
                v-if="i < steps.length - 1"
                aria-hidden="true"
                class="mt-3 shrink-0 text-navy-200"
            >
                <ChevronRight class="size-4" />
            </li>
        </template>
    </ol>
</template>

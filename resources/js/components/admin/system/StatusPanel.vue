<script setup lang="ts">
import {
    CircleCheck,
    CircleMinus,
    CircleX,
    MonitorCog,
    TriangleAlert,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { cn } from '@/lib/utils';
import type { SystemStatusRow } from '@/types';
import { t } from '@/lib/i18n';

/** Server muhiti va xizmatlar holati (faqat ko'rish) */
const props = defineProps<{ rows: SystemStatusRow[] }>();

const styles: Record<
    SystemStatusRow['state'],
    { icon: Component; class: string; label: string }
> = {
    ok: {
        icon: CircleCheck,
        class: 'bg-emerald-50 text-emerald-600',
        label: t('Joyida'),
    },
    warning: {
        icon: TriangleAlert,
        class: 'bg-amber-50 text-amber-600',
        label: t("E'tibor bering"),
    },
    error: { icon: CircleX, class: 'bg-red-50 text-red-600', label: t('Xato') },
    off: {
        icon: CircleMinus,
        class: 'bg-slate-100 text-slate-500',
        label: t("O'chirilgan"),
    },
};

const issues = computed(
    () =>
        props.rows.filter((r) => r.state === 'error' || r.state === 'warning')
            .length,
);
</script>

<template>
    <SectionCard
        :title="t('Tizim holati')"
        :description="
            issues
                ? t(':issues ta bandga e\'tibor bering', { issues: issues })
                : t('Barcha asosiy xizmatlar joyida')
        "
        :icon="MonitorCog"
    >
        <ul class="-mx-5 -my-5 divide-y divide-line">
            <li
                v-for="row in rows"
                :key="row.key"
                class="flex items-start gap-3 px-5 py-3 transition-colors hover:bg-brand-50/30"
            >
                <span
                    :class="
                        cn(
                            'flex size-8 shrink-0 items-center justify-center rounded-lg',
                            styles[row.state].class,
                        )
                    "
                    :title="styles[row.state].label"
                >
                    <component :is="styles[row.state].icon" class="size-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-x-3"
                    >
                        <p class="text-[13px] font-semibold text-navy-900">
                            {{ row.label }}
                        </p>
                        <p
                            class="font-mono text-xs [overflow-wrap:anywhere] text-navy-600"
                        >
                            {{ row.value || '—' }}
                        </p>
                    </div>
                    <p
                        v-if="row.hint"
                        :class="
                            cn(
                                'mt-0.5 text-xs [overflow-wrap:anywhere]',
                                row.state === 'error'
                                    ? 'text-red-600'
                                    : row.state === 'warning'
                                      ? 'text-amber-700'
                                      : 'text-navy-400',
                            )
                        "
                    >
                        {{ row.hint }}
                    </p>
                </div>
            </li>
        </ul>
    </SectionCard>
</template>

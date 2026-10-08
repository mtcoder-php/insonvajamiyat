<script setup lang="ts">
import {
    CircleAlert,
    CircleCheck,
    CircleDashed,
    Cpu,
    Database,
    Globe,
    Wallet,
} from '@lucide/vue';
import type { Component } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import type { SystemHealthItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Tizim holati": asosiy xizmatlar — ishlayapti / ishlamayapti / sozlanmagan.
 * Holat rang bilan birga ikonka va matn orqali ham ko'rsatiladi.
 */
defineProps<{ items: SystemHealthItem[] }>();

const icons: Record<string, Component> = {
    web: Globe,
    database: Database,
    payments: Wallet,
    ai: Cpu,
};

const states: Record<
    SystemHealthItem['state'],
    { label: string; icon: Component; class: string }
> = {
    up: {
        label: t('Ishlamoqda'),
        icon: CircleCheck,
        class: 'text-emerald-600',
    },
    down: {
        label: t('Ishlamayapti'),
        icon: CircleAlert,
        class: 'text-red-600',
    },
    not_configured: {
        label: t('Sozlanmagan'),
        icon: CircleDashed,
        class: 'text-amber-600',
    },
};
</script>

<template>
    <DashCard :title="t('Tizim holati')">
        <ul class="divide-y divide-line">
            <li
                v-for="item in items"
                :key="item.key"
                class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#f3f6fa] text-navy-600"
                >
                    <component :is="icons[item.key] ?? Globe" class="size-4" />
                </span>
                <span class="min-w-0 flex-1 truncate text-[13px] text-navy-800">
                    {{ item.label }}
                </span>
                <span
                    :class="[
                        'inline-flex items-center gap-1 text-xs font-semibold whitespace-nowrap',
                        states[item.state].class,
                    ]"
                >
                    <component :is="states[item.state].icon" class="size-3.5" />
                    {{ states[item.state].label }}
                </span>
            </li>
        </ul>
    </DashCard>
</template>

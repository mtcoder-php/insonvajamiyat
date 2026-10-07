<script setup lang="ts">
import type { Component } from 'vue';
import { cn } from '@/lib/utils';

/** Kichik ko'rsatkich kartasi: ikonka, nom, qiymat va izoh */
defineProps<{
    label: string;
    value: string;
    icon: Component;
    tint: string;
    hint?: string;
    active?: boolean;
    as?: 'div' | 'button';
}>();
</script>

<template>
    <component
        :is="as ?? 'div'"
        :type="as === 'button' ? 'button' : undefined"
        :class="
            cn(
                'group flex items-center gap-3 rounded-xl border bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-16px_rgba(0,36,66,0.35)]',
                active
                    ? 'border-brand-300 ring-2 ring-brand-100'
                    : 'border-line',
            )
        "
    >
        <span
            :class="
                cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                    tint,
                )
            "
        >
            <component :is="icon" class="size-5" />
        </span>
        <span class="min-w-0">
            <span class="block truncate text-xs font-medium text-navy-500">{{
                label
            }}</span>
            <span
                class="block text-xl leading-tight font-bold text-navy-950 tabular-nums"
                >{{ value }}</span
            >
            <span
                v-if="hint"
                class="block truncate text-[11px] text-navy-400"
                >{{ hint }}</span
            >
        </span>
    </component>
</template>

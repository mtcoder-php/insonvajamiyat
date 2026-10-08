<script setup lang="ts">
import { CircleCheck, CircleX, Hourglass } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { RefundStatusKey } from '@/types';

/** Qaytarish holati (App\Enums\RefundStatus) */
const props = defineProps<{ status: RefundStatusKey; label: string }>();

const style = computed<{ class: string; icon: Component }>(() => {
    switch (props.status) {
        case 'completed':
            return {
                class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                icon: CircleCheck,
            };
        case 'failed':
            return {
                class: 'bg-red-50 text-red-700 ring-red-200',
                icon: CircleX,
            };
        default:
            return {
                class: 'bg-amber-50 text-amber-700 ring-amber-200',
                icon: Hourglass,
            };
    }
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                style.class,
            )
        "
    >
        <component :is="style.icon" class="size-3.5" />
        {{ label }}
    </span>
</template>

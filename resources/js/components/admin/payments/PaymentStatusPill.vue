<script setup lang="ts">
import { CircleCheck, CircleX, Clock3, Undo2 } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * To'lov holati belgisi (App\Enums\PaymentStatus).
 */
const props = defineProps<{ status: string; label: string }>();

const style = computed<{ class: string; icon: Component }>(() => {
    switch (props.status) {
        case 'paid':
            return {
                class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                icon: CircleCheck,
            };
        case 'refunded':
            return {
                class: 'bg-navy-50 text-navy-600 ring-navy-200',
                icon: Undo2,
            };
        case 'cancelled':
        case 'failed':
            return {
                class: 'bg-red-50 text-red-700 ring-red-200',
                icon: CircleX,
            };
        default:
            return {
                class: 'bg-amber-50 text-amber-700 ring-amber-200',
                icon: Clock3,
            };
    }
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                style.class,
            )
        "
    >
        <component :is="style.icon" class="size-3.5" />
        {{ label }}
    </span>
</template>

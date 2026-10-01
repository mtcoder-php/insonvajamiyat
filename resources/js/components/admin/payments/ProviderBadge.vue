<script setup lang="ts">
import { Landmark } from '@lucide/vue';
import { cn } from '@/lib/utils';
import type { PaymentProviderKey } from '@/types';

/**
 * To'lov usuli: rangli belgi + nomi (Click, Payme, qo'lda — bank o'tkazmasi).
 */
defineProps<{ provider: PaymentProviderKey; label: string }>();

const tint: Record<PaymentProviderKey, string> = {
    click: 'bg-[#1a82f7] text-white',
    payme: 'bg-[#0fa37f] text-white',
    manual: 'bg-violet-100 text-violet-700',
};
</script>

<template>
    <span class="inline-flex items-center gap-2 whitespace-nowrap">
        <span
            :class="
                cn(
                    'flex size-6 shrink-0 items-center justify-center rounded-md text-[11px] font-bold',
                    tint[provider],
                )
            "
            aria-hidden="true"
        >
            <Landmark v-if="provider === 'manual'" class="size-3.5" />
            <template v-else>{{ provider === 'click' ? 'C' : 'P' }}</template>
        </span>
        <span class="text-[13px] text-navy-800">{{ label }}</span>
    </span>
</template>

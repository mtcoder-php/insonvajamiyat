<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Forma maydoni: yorliq (majburiy belgisi bilan), maydon, izoh va xato.
 */
const props = defineProps<{
    label: string;
    for?: string;
    required?: boolean;
    hint?: string;
    error?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('grid content-start gap-1.5', props.class)">
        <label :for="props.for" class="text-[13px] font-semibold text-navy-800">
            {{ label }}
            <span v-if="required" class="text-red-500" aria-hidden="true"
                >*</span
            >
        </label>
        <slot />
        <p v-if="error" class="text-xs font-medium text-red-600">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-navy-400">{{ hint }}</p>
    </div>
</template>

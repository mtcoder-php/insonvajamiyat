<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';

/**
 * Oddiy (native) select — yorug' dizaynda, o'ngda strelka bilan.
 * Variantlar <option> sifatida slot orqali beriladi.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps<{ class?: HTMLAttributes['class'] }>();

const model = defineModel<string | number | null>();
</script>

<template>
    <div :class="cn('relative', props.class)">
        <select
            v-model="model"
            v-bind="$attrs"
            :class="cn(inputClass, 'cursor-pointer appearance-none pr-9')"
        >
            <slot />
        </select>
        <ChevronDown
            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-navy-400"
            aria-hidden="true"
        />
    </div>
</template>

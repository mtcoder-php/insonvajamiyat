<script setup lang="ts">
import { Check } from '@lucide/vue';
import { cn } from '@/lib/utils';
import type { PeopleOption } from '@/types';

/** Ilmiy yo'nalishlarni tanlash (chiplar, bir nechtasi) */
defineProps<{ options: PeopleOption[] }>();

const model = defineModel<number[]>({ required: true });

function toggle(id: number): void {
    model.value = model.value.includes(id)
        ? model.value.filter((x) => x !== id)
        : [...model.value, id];
}
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-pressed="model.includes(option.value)"
            :class="
                cn(
                    'inline-flex h-8 items-center gap-1.5 rounded-lg border px-2.5 text-xs font-semibold transition-all',
                    model.includes(option.value)
                        ? 'border-brand-400 bg-brand-50 text-brand-700 shadow-sm'
                        : 'border-line bg-white text-navy-600 hover:border-brand-200 hover:text-brand-700',
                )
            "
            @click="toggle(option.value)"
        >
            <Check
                v-if="model.includes(option.value)"
                class="size-3.5"
                :stroke-width="3"
            />
            {{ option.label }}
        </button>
    </div>
</template>

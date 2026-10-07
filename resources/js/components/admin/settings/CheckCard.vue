<script setup lang="ts">
import type { Component } from 'vue';
import { cn } from '@/lib/utils';

/** Belgilash katakchasi kartochka ko'rinishida: nom, izoh va ixtiyoriy ikonka */
defineProps<{ label: string; hint?: string; icon?: Component }>();

const model = defineModel<boolean>({ required: true });
</script>

<template>
    <label
        :class="
            cn(
                'group flex cursor-pointer items-start gap-3 rounded-xl border px-3.5 py-3 transition-all duration-200',
                model
                    ? 'border-brand-300 bg-brand-50/60 ring-1 ring-brand-100'
                    : 'border-line bg-white hover:border-brand-200 hover:bg-[#fafcff]',
            )
        "
    >
        <input
            v-model="model"
            type="checkbox"
            class="mt-0.5 size-4 shrink-0 accent-brand-600"
        />
        <span class="min-w-0">
            <span
                class="flex items-center gap-1.5 text-[13px] font-semibold text-navy-900"
            >
                <component
                    :is="icon"
                    v-if="icon"
                    :class="
                        cn(
                            'size-3.5 transition-colors',
                            model ? 'text-brand-600' : 'text-navy-400',
                        )
                    "
                />
                {{ label }}
            </span>
            <span v-if="hint" class="mt-0.5 block text-xs text-navy-500">{{
                hint
            }}</span>
        </span>
    </label>
</template>

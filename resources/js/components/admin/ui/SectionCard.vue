<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Forma / profil bo'limi kartasi: ikonka, sarlavha, izoh.
 */
const props = defineProps<{
    title: string;
    description?: string;
    icon?: Component;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <section
        :class="
            cn(
                'rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]',
                props.class,
            )
        "
    >
        <header class="flex items-center gap-3 border-b border-line px-5 py-4">
            <span
                v-if="icon"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
            >
                <component :is="icon" class="size-[18px]" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ title }}
                </h2>
                <p v-if="description" class="mt-0.5 text-xs text-navy-500">
                    {{ description }}
                </p>
            </div>
            <slot name="actions" />
        </header>
        <div class="p-5">
            <slot />
        </div>
    </section>
</template>

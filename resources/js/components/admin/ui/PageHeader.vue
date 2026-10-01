<script setup lang="ts">
/**
 * Admin sahifasi sarlavhasi: nom, izoh va o'ngda amallar.
 */
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <slot name="before" />
            <h1
                class="font-sans text-2xl font-bold tracking-tight text-navy-950"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-navy-500">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { t } from '@/lib/i18n';

/**
 * Web qism sahifalarining sarlavha bloki (vaqtinchalik, funksional).
 * Dizayn bosqichida rasmli "hero" bilan almashtiriladi.
 */
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section class="border-b bg-muted/40">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
            <p v-if="description" class="mt-2 max-w-3xl text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </section>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <slot>
            <p class="text-muted-foreground">
                {{ t("Bu bo'lim ishlab chiqilmoqda.") }}
            </p>
        </slot>
    </div>
</template>

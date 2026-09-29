<script setup lang="ts">
import type { Component } from 'vue';

/**
 * Input chap tomoniga ikonka qo'yish uchun o'ram.
 * Ichidagi input'ga `pl-10` klassi berilishi kerak.
 *
 *   <InputIcon :icon="Mail"><Input class="pl-10" ... /></InputIcon>
 */
defineProps<{
    icon: Component;
}>();
</script>

<template>
    <div class="relative">
        <component
            :is="icon"
            class="pointer-events-none absolute top-1/2 left-3.5 z-10 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <slot />
    </div>
</template>

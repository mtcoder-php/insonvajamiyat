<script setup lang="ts">
import type { AppArea } from '@/types';

/**
 * Sidebar sarlavhasidagi logotip (to'q ko'k sidebar uchun oq variant).
 * Sidebar yig'ilganda (icon rejimi) faqat ikonka ko'rinadi.
 */
defineProps<{
    area?: AppArea;
}>();
</script>

<template>
    <img
        src="/images/logo-mark-light.webp"
        alt=""
        aria-hidden="true"
        class="size-9 shrink-0 object-contain"
        width="256"
        height="256"
    />
    <div class="grid flex-1 text-left leading-tight">
        <span
            class="truncate font-serif text-sm font-bold tracking-wide text-white uppercase"
        >
            Inson va Jamiyat
        </span>
        <span class="truncate text-xs text-sidebar-foreground/70">
            {{ area === 'admin' ? 'Admin panel' : 'Muallif kabineti' }}
        </span>
    </div>
</template>

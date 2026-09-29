<script setup lang="ts">
import type { JournalSocialNetwork } from '@/types';

/**
 * Ijtimoiy tarmoq ikonkalari (Lucide'da brend ikonkalari yo'q).
 * Rang `currentColor` dan olinadi.
 */
defineProps<{
    network: JournalSocialNetwork;
}>();
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        class="size-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
    >
        <path
            v-if="network === 'telegram'"
            fill="currentColor"
            d="M21.94 4.3 18.7 19.6c-.24 1.08-.88 1.35-1.78.84l-4.93-3.63-2.38 2.29c-.26.26-.48.48-.99.48l.35-5.02 9.14-8.26c.4-.35-.09-.55-.62-.2L6.2 13.2l-4.87-1.52c-1.06-.33-1.08-1.06.22-1.57L20.6 2.77c.88-.32 1.65.2 1.34 1.53z"
        />
        <path
            v-else-if="network === 'facebook'"
            fill="currentColor"
            d="M14 8.5V6.9c0-.77.51-.95.87-.95H17V2.1L14.06 2C10.8 2 10 4.44 10 6v2.5H8v4h2V22h4v-9.5h3.1l.4-4H14z"
        />
        <g
            v-else-if="network === 'instagram'"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <rect x="3" y="3" width="18" height="18" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.5" cy="6.5" r="0.6" fill="currentColor" />
        </g>
        <path
            v-else-if="network === 'youtube'"
            fill="currentColor"
            d="M22.5 7.2a2.8 2.8 0 0 0-2-2c-1.8-.5-8.5-.5-8.5-.5s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2C1 9 1 12 1 12s0 3 .5 4.8a2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2c.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.75 15.1V8.9L15.5 12z"
        />
        <path
            v-else
            fill="currentColor"
            d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3zm6.5 0h3.8v1.6h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.1c0-1.22-.02-2.79-1.7-2.79-1.7 0-1.96 1.33-1.96 2.7V21h-4z"
        />
    </svg>
</template>

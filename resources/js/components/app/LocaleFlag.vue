<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import type { LocaleCode } from '@/types';

/**
 * Til bayrog'i (doira shaklida): uz — O'zbekiston, ru — Rossiya, en — Buyuk Britaniya.
 */
const props = defineProps<{
    code: LocaleCode;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-block size-5 shrink-0 overflow-hidden rounded-full ring-1 ring-black/10',
                props.class,
            )
        "
        aria-hidden="true"
    >
        <svg v-if="code === 'uz'" viewBox="0 0 30 30" class="size-full">
            <rect width="30" height="10" fill="#1eb3e6" />
            <rect y="10" width="30" height="10" fill="#fff" />
            <rect y="20" width="30" height="10" fill="#1eb53a" />
            <rect y="9.4" width="30" height="0.8" fill="#ce1126" />
            <rect y="19.8" width="30" height="0.8" fill="#ce1126" />
            <circle cx="8" cy="5" r="3.2" fill="#fff" />
            <circle cx="9.2" cy="5" r="2.8" fill="#1eb3e6" />
            <g fill="#fff">
                <circle cx="13.5" cy="3" r="0.6" />
                <circle cx="15.5" cy="3" r="0.6" />
                <circle cx="17.5" cy="3" r="0.6" />
                <circle cx="13.5" cy="5" r="0.6" />
                <circle cx="15.5" cy="5" r="0.6" />
                <circle cx="17.5" cy="5" r="0.6" />
                <circle cx="15.5" cy="7" r="0.6" />
                <circle cx="17.5" cy="7" r="0.6" />
            </g>
        </svg>
        <svg v-else-if="code === 'ru'" viewBox="0 0 30 30" class="size-full">
            <rect width="30" height="10" fill="#fff" />
            <rect y="10" width="30" height="10" fill="#0039a6" />
            <rect y="20" width="30" height="10" fill="#d52b1e" />
        </svg>
        <svg v-else viewBox="0 0 30 30" class="size-full">
            <rect width="30" height="30" fill="#012169" />
            <path d="M0 0L30 30M30 0L0 30" stroke="#fff" stroke-width="6" />
            <path d="M0 0L30 30M30 0L0 30" stroke="#c8102e" stroke-width="2" />
            <path d="M15 0V30M0 15H30" stroke="#fff" stroke-width="10" />
            <path d="M15 0V30M0 15H30" stroke="#c8102e" stroke-width="6" />
        </svg>
    </span>
</template>

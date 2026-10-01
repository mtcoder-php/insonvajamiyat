<script setup lang="ts">
import { UserRound } from '@lucide/vue';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Foydalanuvchi rasmi yoki ism-familiya bosh harflari (rangli doira).
 */
const props = withDefaults(
    defineProps<{
        name: string;
        url?: string | null;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
        class?: HTMLAttributes['class'];
    }>(),
    { url: null, size: 'md' },
);

const sizes = {
    xs: 'size-7 text-[10px]',
    sm: 'size-9 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-16 text-xl',
    xl: 'size-28 text-3xl',
};

const tints = [
    'bg-brand-100 text-brand-700',
    'bg-amber-100 text-amber-700',
    'bg-emerald-100 text-emerald-700',
    'bg-violet-100 text-violet-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
];

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join(''),
);

// Bir xil ism doim bir xil rangda
const tint = computed(() => {
    let hash = 0;

    for (const char of props.name) {
        hash = (hash * 31 + char.charCodeAt(0)) | 0;
    }

    return tints[Math.abs(hash) % tints.length];
});
</script>

<template>
    <img
        v-if="url"
        :src="url"
        :alt="name"
        :class="
            cn(
                'shrink-0 rounded-full object-cover ring-2 ring-white',
                sizes[size],
                props.class,
            )
        "
    />
    <span
        v-else
        :class="
            cn(
                'flex shrink-0 items-center justify-center rounded-full font-bold ring-2 ring-white select-none',
                sizes[size],
                tint,
                props.class,
            )
        "
        aria-hidden="true"
    >
        <template v-if="initials">{{ initials }}</template>
        <UserRound v-else class="size-1/2" />
    </span>
</template>

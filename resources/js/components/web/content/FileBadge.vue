<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Fayl turi belgisi (DOCX — ko'k, PDF — qizil, XLSX — yashil, ZIP — sariq):
 * "Yuklab olinadigan fayllar" ro'yxati va admin dialogi uchun.
 */
const props = withDefaults(
    defineProps<{ extension: string; size?: 'sm' | 'md' }>(),
    { size: 'md' },
);

const tone = computed(() => {
    const ext = props.extension.toLowerCase();

    if (['doc', 'docx', 'dotx', 'rtf', 'odt'].includes(ext)) {
        return 'bg-[#e8f1ff] text-[#1d5fd1] ring-[#c9ddff]';
    }

    if (ext === 'pdf') {
        return 'bg-red-50 text-red-600 ring-red-200';
    }

    if (['xls', 'xlsx'].includes(ext)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
});
</script>

<template>
    <span
        :class="
            cn(
                'relative inline-flex shrink-0 items-end justify-center rounded-lg pb-1.5 font-sans font-bold tracking-wide uppercase ring-1 ring-inset',
                size === 'md' ? 'h-12 w-10 text-[10px]' : 'h-10 w-8 text-[9px]',
                tone,
            )
        "
        aria-hidden="true"
    >
        <span
            class="absolute top-0 right-0 size-2.5 rounded-bl-md bg-white/80 ring-1 ring-current/20"
        />
        {{ extension || 'file' }}
    </span>
</template>

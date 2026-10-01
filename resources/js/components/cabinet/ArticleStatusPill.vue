<script setup lang="ts">
import {
    BookOpenCheck,
    CircleCheck,
    CircleX,
    Clock3,
    FilePenLine,
    PenLine,
    Send,
    Undo2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { cn } from '@/lib/utils';
import type { ArticleStatusGroup } from '@/types';

/**
 * Maqola holati belgisi (ArticleStatus::group() bo'yicha rang va ikonka).
 */
defineProps<{
    group: ArticleStatusGroup;
    label: string;
}>();

const styles: Record<ArticleStatusGroup, { class: string; icon: Component }> = {
    draft: {
        class: 'bg-navy-50 text-navy-600 ring-navy-200',
        icon: PenLine,
    },
    new: { class: 'bg-brand-50 text-brand-700 ring-brand-200', icon: Send },
    reviewing: {
        class: 'bg-sky-50 text-sky-700 ring-sky-200',
        icon: Clock3,
    },
    revision: {
        class: 'bg-amber-50 text-amber-700 ring-amber-200',
        icon: FilePenLine,
    },
    accepted: {
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        icon: CircleCheck,
    },
    published: {
        class: 'bg-violet-50 text-violet-700 ring-violet-200',
        icon: BookOpenCheck,
    },
    rejected: {
        class: 'bg-red-50 text-red-700 ring-red-200',
        icon: CircleX,
    },
    withdrawn: {
        class: 'bg-navy-50 text-navy-500 ring-navy-200',
        icon: Undo2,
    },
};
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                styles[group].class,
            )
        "
    >
        <component :is="styles[group].icon" class="size-3.5" />
        {{ label }}
    </span>
</template>

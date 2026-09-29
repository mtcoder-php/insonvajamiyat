<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { cn } from '@/lib/utils';

/**
 * Maqola kartochkasi rasmi. Rasm yuklanmagan bo'lsa — yo'nalish
 * ikonkasi tushirilgan navy-girih zaxira fon.
 */
const props = defineProps<{
    src: string | null;
    alt: string;
    subjectSlug?: string | null;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('relative overflow-hidden bg-navy-900', props.class)">
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            loading="lazy"
            class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div
            v-else
            class="flex size-full items-center justify-center bg-navy-gradient"
            aria-hidden="true"
        >
            <div class="absolute inset-0 bg-girih opacity-[0.08]" />
            <div
                class="absolute -right-6 -bottom-8 size-32 rounded-full bg-brand-600/20 blur-2xl"
            />
            <SubjectIcon
                :slug="subjectSlug"
                class="relative size-12 text-gold-300/80"
                :stroke-width="1.25"
            />
        </div>
    </div>
</template>

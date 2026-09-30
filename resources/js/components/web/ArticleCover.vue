<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { cn } from '@/lib/utils';

/**
 * Maqola kartochkasi rasmi. Muqova yuklanmagan bo'lsa — iliq qog'oz
 * tusidagi fon, naqsh va yo'nalish ikonkasi (muqova admin panelda yuklanadi).
 */
const props = defineProps<{
    src: string | null;
    alt: string;
    subjectSlug?: string | null;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('relative overflow-hidden bg-[#efe9dd]', props.class)">
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            loading="lazy"
            class="size-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
        />
        <div
            v-else
            class="flex size-full items-center justify-center bg-gradient-to-br from-[#f4efe4] via-[#ece4d4] to-[#e2d6c0]"
            aria-hidden="true"
        >
            <div class="absolute inset-0 bg-girih-gold opacity-40" />
            <span
                class="relative flex size-16 items-center justify-center rounded-full border border-gold-400/40 bg-white/55 text-navy-800 shadow-sm backdrop-blur-sm transition-transform duration-500 group-hover:scale-110"
            >
                <SubjectIcon
                    :slug="subjectSlug"
                    class="size-7"
                    :stroke-width="1.5"
                />
            </span>
        </div>
    </div>
</template>

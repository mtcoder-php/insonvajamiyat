<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Bo'lim sarlavhasi: serif sarlavha + o'ngda "Barcha ..." havolasi.
 */
const props = defineProps<{
    title: string;
    href?: InertiaLinkProps['href'];
    linkText?: string;
    as?: 'h2' | 'h3';
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('mb-4 flex items-end justify-between gap-4', props.class)">
        <component
            :is="as ?? 'h2'"
            class="font-serif text-xl font-semibold text-navy-950 sm:text-2xl"
        >
            {{ title }}
        </component>
        <Link
            v-if="href"
            :href="href"
            class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-600"
        >
            {{ linkText ?? "Barchasini ko'rish" }}
            <ArrowRight
                class="size-4 transition-transform group-hover:translate-x-0.5"
            />
        </Link>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Dashboard kartasi: oq fon, ingichka chegara, sarlavha va "Barchasi →".
 */
const props = defineProps<{
    title?: string;
    href?: InertiaLinkProps['href'];
    linkText?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <section
        :class="
            cn(
                'rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-16px_rgba(0,36,66,0.25)]',
                props.class,
            )
        "
    >
        <header
            v-if="title || $slots.actions"
            class="mb-4 flex items-center justify-between gap-3"
        >
            <h2 class="font-sans text-[15px] font-bold text-navy-950">
                {{ title }}
            </h2>
            <slot name="actions">
                <Link
                    v-if="href"
                    :href="href"
                    class="group inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:text-brand-600"
                >
                    {{ linkText ?? 'Barchasi' }}
                    <ArrowRight
                        class="size-3.5 transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </slot>
        </header>
        <slot />
    </section>
</template>

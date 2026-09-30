<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Bo'lim sarlavhasi: kichik "eyebrow" yozuv, serif sarlavha, izoh
 * va o'ngda "Barchasini ko'rish" havolasi.
 */
const props = defineProps<{
    title: string;
    eyebrow?: string;
    description?: string;
    href?: InertiaLinkProps['href'];
    linkText?: string;
    align?: 'left' | 'center';
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'mb-8 flex flex-wrap items-end justify-between gap-x-8 gap-y-3',
                align === 'center' && 'flex-col items-center text-center',
                props.class,
            )
        "
    >
        <div :class="cn('max-w-2xl', align === 'center' && 'mx-auto')">
            <p
                v-if="eyebrow"
                :class="
                    cn(
                        'mb-3 flex items-center gap-3 text-xs font-semibold tracking-[0.18em] text-gold-600 uppercase',
                        align === 'center' && 'justify-center',
                    )
                "
            >
                <span class="h-px w-8 bg-gold-500" aria-hidden="true" />
                {{ eyebrow }}
            </p>
            <h2
                class="font-serif text-2xl leading-tight font-semibold text-navy-950 sm:text-[2rem]"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mt-3 text-[15px] leading-relaxed text-navy-600"
            >
                {{ description }}
            </p>
        </div>
        <Link
            v-if="href"
            :href="href"
            class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-600"
        >
            {{ linkText ?? "Barchasini ko'rish" }}
            <ArrowRight
                class="size-4 transition-transform group-hover:translate-x-0.5"
            />
        </Link>
    </div>
</template>

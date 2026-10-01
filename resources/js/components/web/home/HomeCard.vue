<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Bosh sahifa kartasi (home.png): iliq oq fon, ingichka chegara,
 * serif sarlavha va o'ngda "Barchasi →" havolasi.
 *   tone="warm"  — asosiy (#f8f7f4)
 *   tone="navy"  — to'q ko'k (Jurnal haqida, obuna)
 *   tone="cream" — oltin tusli (Biz bilan bog'laning)
 */
const props = withDefaults(
    defineProps<{
        title?: string;
        href?: InertiaLinkProps['href'];
        linkText?: string;
        tone?: 'warm' | 'navy' | 'cream';
        as?: 'section' | 'aside' | 'div';
        /** Sarlavha o'lchami: lg — asosiy bloklar uchun (masalan, "So'nggi son") */
        size?: 'md' | 'lg';
        class?: HTMLAttributes['class'];
    }>(),
    {
        title: undefined,
        href: undefined,
        linkText: "Barchasini ko'rish",
        tone: 'warm',
        as: 'section',
        size: 'md',
        class: undefined,
    },
);

const tones = {
    warm: 'border-[#ebe8e1] bg-[#f8f7f4]',
    navy: 'border-navy-800 bg-navy-900 text-white',
    cream: 'border-[#efe3cc] bg-gradient-to-br from-[#faf5eb] to-[#f4ead8]',
};

const titleTones = {
    warm: 'text-navy-900',
    navy: 'text-white',
    cream: 'text-gold-700',
};
</script>

<template>
    <component
        :is="as"
        :class="
            cn(
                'relative overflow-hidden rounded-xl border p-5 shadow-[0_1px_2px_rgba(0,30,60,0.04)] sm:p-6',
                tones[tone],
                props.class,
            )
        "
    >
        <div
            v-if="title || href"
            class="relative mb-4 flex items-baseline justify-between gap-4"
        >
            <h2
                v-if="title"
                :class="
                    cn(
                        'font-serif leading-tight font-bold',
                        props.size === 'lg'
                            ? 'text-2xl sm:text-[28px]'
                            : 'text-lg sm:text-xl',
                        titleTones[tone],
                    )
                "
            >
                {{ title }}
            </h2>
            <Link
                v-if="href"
                :href="href"
                :class="
                    cn(
                        'group inline-flex shrink-0 items-center gap-1 text-xs font-medium transition-colors',
                        tone === 'navy'
                            ? 'text-white/75 hover:text-white'
                            : 'text-navy-700 hover:text-brand-700',
                    )
                "
            >
                {{ linkText }}
                <ArrowRight
                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                />
            </Link>
        </div>
        <slot />
    </component>
</template>

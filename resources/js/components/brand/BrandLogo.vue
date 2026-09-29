<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Jurnal logotipi: ikonka (rasm) + nom (HTML matn, serif).
 * Nom matn sifatida chiqadi — har qanday o'lchamda tiniq va qidiruv tizimlari o'qiydi.
 *
 *   tone="dark"  — och fonda (to'q ko'k matn, rangli ikonka)
 *   tone="light" — to'q ko'k fonda (oq matn, oq-oltin ikonka)
 */
type Size = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        variant?: 'full' | 'mark';
        tone?: 'dark' | 'light';
        size?: Size;
        tagline?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        variant: 'full',
        tone: 'dark',
        size: 'md',
        tagline: false,
        class: undefined,
    },
);

const sizes: Record<Size, { mark: string; title: string; subtitle: string }> = {
    sm: { mark: 'size-9', title: 'text-sm', subtitle: 'text-xs' },
    md: { mark: 'size-12', title: 'text-lg', subtitle: 'text-sm' },
    lg: { mark: 'size-16', title: 'text-2xl', subtitle: 'text-base' },
};

const markSrc = computed(() =>
    props.tone === 'light'
        ? '/images/logo-mark-light.webp'
        : '/images/logo-mark.webp',
);

const isLight = computed(() => props.tone === 'light');
</script>

<template>
    <span :class="cn('inline-flex items-center gap-3', props.class)">
        <img
            :src="markSrc"
            :alt="variant === 'mark' ? 'Inson va Jamiyat' : ''"
            :aria-hidden="variant === 'full' ? 'true' : undefined"
            :class="cn('shrink-0 object-contain', sizes[size].mark)"
            width="256"
            height="256"
        />
        <span v-if="variant === 'full'" class="flex min-w-0 flex-col">
            <span
                :class="
                    cn(
                        'truncate font-serif leading-tight font-bold tracking-wide uppercase',
                        sizes[size].title,
                        isLight ? 'text-white' : 'text-navy-950',
                    )
                "
            >
                Inson va Jamiyat
            </span>
            <span
                :class="
                    cn(
                        'truncate font-serif leading-tight italic',
                        sizes[size].subtitle,
                        isLight ? 'text-white/80' : 'text-navy-700',
                    )
                "
            >
                Scientific Journal
            </span>
            <span
                v-if="tagline"
                :class="
                    cn(
                        'mt-1 truncate font-serif text-xs italic',
                        isLight ? 'text-gold-300' : 'text-gold-600',
                    )
                "
            >
                Ilm, tafakkur va taraqqiyot yo'lida
            </span>
        </span>
    </span>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Jurnal soni muqovasi. Muqova yuklanmagan bo'lsa — jurnal uslubidagi
 * generatsiya qilingan muqova (logotip, nom, son raqami).
 * O'lchamlar cqw (konteyner kengligi) bilan — har qanday kenglikda proporsional.
 */
const props = defineProps<{
    src: string | null;
    number: number;
    year: number;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                '@container relative aspect-[3/4] overflow-hidden rounded-md bg-navy-950 shadow-float ring-1 ring-black/10',
                props.class,
            )
        "
    >
        <img
            v-if="src"
            :src="src"
            :alt="`Jurnal muqovasi: №${number}/${year}`"
            loading="lazy"
            class="size-full object-cover"
        />
        <div
            v-else
            class="flex size-full flex-col bg-navy-gradient p-[8cqw] text-white"
            role="img"
            :aria-label="`Jurnal muqovasi: №${number}/${year}`"
        >
            <div class="absolute inset-0 bg-girih opacity-[0.07]" />

            <div class="relative flex items-center gap-[3cqw]">
                <img
                    src="/images/logo-mark-light.webp"
                    alt=""
                    class="w-[15cqw] shrink-0 object-contain"
                />
                <div class="min-w-0 leading-none">
                    <p
                        class="font-serif text-[7cqw] font-semibold tracking-wide uppercase"
                    >
                        Inson va Jamiyat
                    </p>
                    <p
                        class="mt-[1.5cqw] font-serif text-[5cqw] text-white/70 italic"
                    >
                        Scientific Journal
                    </p>
                </div>
            </div>
            <div class="relative mt-[5cqw] gold-rule w-1/2" />

            <div
                class="relative flex min-h-0 flex-1 items-center justify-center"
            >
                <div
                    class="flex size-[60cqw] items-center justify-center rounded-full border border-gold-400/40 bg-white/[0.03]"
                >
                    <div
                        class="flex size-[44cqw] items-center justify-center rounded-full border border-gold-400/25"
                    >
                        <img
                            src="/images/logo-mark-light.webp"
                            alt=""
                            class="w-[24cqw] object-contain opacity-90"
                        />
                    </div>
                </div>
            </div>

            <div
                class="relative flex items-end justify-between text-[5cqw] text-white/70"
            >
                <span>ISSN</span>
                <span class="font-serif text-[9cqw] font-semibold text-white">
                    №{{ number }}/{{ year }}
                </span>
            </div>
        </div>
    </div>
</template>

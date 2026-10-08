<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { home } from '@/routes';
import { t } from '@/lib/i18n';

/**
 * Rasmli sarlavha (katalog / arxiv dizaynlari): chapda matn, o'ngda rasm va iqtibos,
 * pastda sarlavhaga chiqib turadigan qidiruv paneli (slot "search").
 */
defineProps<{
    title: string;
    description?: string;
    image: string | null;
    crumbs?: { title: string; href?: InertiaLinkProps['href'] }[];
}>();
</script>

<template>
    <section class="relative isolate overflow-hidden bg-[#f9f8f6]">
        <img
            v-if="image"
            :src="image"
            alt=""
            aria-hidden="true"
            class="absolute inset-y-0 right-0 -z-20 hidden h-full w-[62%] object-cover md:block"
        />
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-r from-[#f9f8f6] from-38% via-[#f9f8f6]/85 via-55% to-[#001e3c]/10"
            aria-hidden="true"
        />
        <div
            class="absolute inset-0 -z-10 bg-girih-gold opacity-[0.04]"
            aria-hidden="true"
        />

        <div
            class="mx-auto w-full max-w-[1700px] px-4 pt-7 pb-16 sm:px-6 lg:w-[90%] lg:px-0 lg:pt-8 lg:pb-20"
        >
            <nav
                class="flex flex-wrap items-center gap-1 text-xs text-navy-500"
                :aria-label="t('Non-yo\'l')"
            >
                <Link
                    :href="home()"
                    class="transition-colors hover:text-brand-700"
                    >{{ t('Bosh sahifa') }}</Link
                >
                <template v-for="crumb in crumbs ?? []" :key="crumb.title">
                    <ChevronRight class="size-3.5 text-navy-300" />
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="transition-colors hover:text-brand-700"
                        >{{ crumb.title }}</Link
                    >
                    <span v-else class="text-navy-700">{{ crumb.title }}</span>
                </template>
            </nav>
            <div class="flex items-end justify-between gap-8">
                <div class="max-w-2xl">
                    <h1
                        class="mt-4 font-serif text-3xl leading-tight font-bold text-navy-950 sm:text-[2.6rem]"
                    >
                        {{ title }}
                    </h1>
                    <div class="mt-4 gold-rule w-20" />
                    <p
                        v-if="description"
                        class="mt-4 text-[15px] leading-relaxed text-navy-700"
                    >
                        {{ description }}
                    </p>
                </div>
                <blockquote
                    v-if="image"
                    class="hidden max-w-xs text-right font-serif text-xl leading-snug text-white italic drop-shadow-[0_2px_8px_rgba(0,30,60,0.7)] xl:block"
                >
                    “{{
                        t(
                            'Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.',
                        )
                    }}”
                </blockquote>
            </div>
        </div>
    </section>

    <div
        v-if="$slots.search"
        class="relative z-10 mx-auto -mt-10 w-full max-w-[1700px] px-4 sm:px-6 lg:w-[90%] lg:px-0"
    >
        <div
            class="rounded-2xl border border-line bg-white p-3 shadow-[0_24px_50px_-28px_rgba(0,36,66,0.55)]"
        >
            <slot name="search" />
        </div>
    </div>
</template>

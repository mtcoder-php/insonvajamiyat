<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, House } from '@lucide/vue';
import type { Component } from 'vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/cabinet';
import type { BreadcrumbItem } from '@/types';

/**
 * Kabinet sahifasi banneri (dizayn: "Muallif kabineti", "To'lovlar").
 *   variant="dark"  — bosh sahifa: to'q ko'k fon, o'ngda Registon manzarasi
 *   variant="light" — ichki sahifalar: oq fon, yo'l ko'rsatkich (breadcrumbs) va ikonka
 * Fon rasmi: public/images/admin/banner.png
 */
const {
    variant = 'light',
    breadcrumbs = [],
    quote = true,
} = defineProps<{
    title: string;
    description?: string;
    icon?: Component;
    variant?: 'light' | 'dark';
    breadcrumbs?: BreadcrumbItem[];
    quote?: boolean;
}>();

const bannerUrl = '/images/admin/banner.png';
</script>

<template>
    <section
        :class="
            cn(
                'group relative isolate overflow-hidden rounded-xl shadow-[0_12px_32px_-20px_rgba(0,30,60,0.55)]',
                variant === 'dark'
                    ? 'bg-navy-950 text-white'
                    : 'border border-line bg-white text-navy-950',
            )
        "
    >
        <div
            class="absolute inset-y-0 right-0 -z-10 w-full bg-cover bg-[position:right_center] bg-no-repeat transition-transform duration-[1500ms] ease-out group-hover:scale-[1.03]"
            :style="{ backgroundImage: `url('${bannerUrl}')` }"
            aria-hidden="true"
        />
        <div
            :class="
                cn(
                    'absolute inset-0 -z-10',
                    variant === 'dark'
                        ? 'bg-gradient-to-r from-navy-950 via-navy-950/80 to-navy-950/35'
                        : 'bg-gradient-to-r from-white via-white/90 to-navy-950/40',
                )
            "
            aria-hidden="true"
        />

        <div
            class="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7 sm:py-6"
        >
            <div class="min-w-0">
                <nav
                    v-if="breadcrumbs.length"
                    class="mb-2 flex flex-wrap items-center gap-1.5 text-xs"
                    aria-label="Yo'l ko'rsatkich"
                >
                    <Link
                        :href="dashboard()"
                        class="flex items-center gap-1.5 text-navy-500 transition-colors hover:text-brand-700"
                    >
                        <House class="size-3.5" />
                        Asosiy sahifa
                    </Link>
                    <template v-for="(crumb, i) in breadcrumbs" :key="i">
                        <ChevronRight class="size-3 text-navy-300" />
                        <Link
                            v-if="i < breadcrumbs.length - 1"
                            :href="crumb.href"
                            class="text-navy-500 transition-colors hover:text-brand-700"
                        >
                            {{ crumb.title }}
                        </Link>
                        <span v-else class="font-medium text-brand-700">
                            {{ crumb.title }}
                        </span>
                    </template>
                </nav>

                <div class="flex items-center gap-3">
                    <span
                        v-if="icon"
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition-transform duration-300 group-hover:scale-105"
                    >
                        <component :is="icon" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <h1
                            :class="
                                cn(
                                    'font-serif text-2xl leading-tight font-bold sm:text-[1.75rem]',
                                    variant === 'dark'
                                        ? 'text-white'
                                        : 'text-navy-950',
                                )
                            "
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            :class="
                                cn(
                                    'mt-1 max-w-2xl text-sm',
                                    variant === 'dark'
                                        ? 'text-white/80'
                                        : 'text-navy-600',
                                )
                            "
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>

            <blockquote
                v-if="quote"
                class="hidden max-w-72 shrink-0 text-right font-serif text-lg leading-snug text-white italic drop-shadow-[0_2px_6px_rgba(0,20,40,0.6)] md:block"
            >
                “Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.”
            </blockquote>
        </div>
    </section>
</template>

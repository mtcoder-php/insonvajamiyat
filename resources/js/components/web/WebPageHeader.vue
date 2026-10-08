<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { home } from '@/routes';
import { t } from '@/lib/i18n';

/**
 * Ichki sahifalar sarlavhasi: non-yo'l (breadcrumbs), serif sarlavha,
 * oltin chiziq va izoh. Yorug' fon (#f9f8f6) + yengil naqsh.
 */
defineProps<{
    title: string;
    description?: string;
    crumbs?: { title: string; href?: InertiaLinkProps['href'] }[];
}>();
</script>

<template>
    <section
        class="relative isolate overflow-hidden border-b border-[#ece8df] bg-[#f9f8f6]"
    >
        <div
            class="absolute inset-0 -z-10 bg-girih-gold opacity-[0.05]"
            aria-hidden="true"
        />
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-9 sm:px-6 lg:w-[90%] lg:px-0 lg:py-11"
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
                    >
                        {{ crumb.title }}
                    </Link>
                    <span v-else class="text-navy-700">{{ crumb.title }}</span>
                </template>
            </nav>
            <h1
                class="mt-3 max-w-4xl font-serif text-3xl leading-tight font-bold text-balance text-navy-950 sm:text-4xl"
            >
                {{ title }}
            </h1>
            <div class="mt-4 gold-rule w-20" />
            <p
                v-if="description"
                class="mt-4 max-w-3xl text-[15px] leading-relaxed text-navy-600"
            >
                {{ description }}
            </p>
            <slot />
        </div>
    </section>
</template>

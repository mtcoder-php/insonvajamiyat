<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRight,
    CalendarDays,
    Megaphone,
    Newspaper,
    Pin,
} from '@lucide/vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import WebPageHeader from '@/components/web/WebPageHeader.vue';
import { formatDateLong } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index } from '@/routes/news';
import type { Paginated, PostItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Yangiliklar va e'lonlar ro'yxati (/news), tur bo'yicha filtr.
 */
defineProps<{
    posts: Paginated<PostItem>;
    type: 'news' | 'announcement' | null;
}>();

const tabs = computed(
    () =>
        [
            { value: null, label: t('Barchasi') },
            { value: 'news', label: t('Yangiliklar') },
            { value: 'announcement', label: t("E'lonlar") },
        ] as const,
);
</script>

<template>
    <Head :title="t('Yangiliklar')" />

    <WebPageHeader
        :title="t('Yangiliklar va e\'lonlar')"
        :description="
            t(
                'Jurnal hayoti, tahririyat qarorlari, konferensiyalar va mualliflar uchun muhim e\'lonlar.',
            )
        "
        :crumbs="[{ title: t('Yangiliklar') }]"
    >
        <nav class="mt-6 flex flex-wrap gap-2" :aria-label="t('Turi')">
            <Link
                v-for="tab in tabs"
                :key="tab.value ?? 'all'"
                :href="index({ query: tab.value ? { type: tab.value } : {} })"
                preserve-scroll
                :class="
                    cn(
                        'rounded-full px-4 py-1.5 text-sm font-medium transition-all',
                        type === tab.value
                            ? 'bg-navy-900 text-white shadow-md shadow-navy-900/20'
                            : 'bg-white text-navy-700 ring-1 ring-[#e6e1d6] hover:-translate-y-px hover:text-brand-700 hover:ring-brand-200',
                    )
                "
            >
                {{ tab.label }}
            </Link>
        </nav>
    </WebPageHeader>

    <div class="bg-white">
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-12"
        >
            <div
                v-if="posts.data.length"
                class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="post in posts.data"
                    :key="post.id"
                    :href="post.url"
                    class="group flex flex-col rounded-xl border border-[#ebe8e1] bg-[#f8f7f4] p-6 transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-[0_18px_36px_-18px_rgba(0,30,60,0.35)]"
                >
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span
                            :class="
                                cn(
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-semibold',
                                    post.type === 'announcement'
                                        ? 'bg-gold-100 text-gold-700'
                                        : 'bg-brand-50 text-brand-700',
                                )
                            "
                        >
                            <Megaphone
                                v-if="post.type === 'announcement'"
                                class="size-3.5"
                            />
                            <Newspaper v-else class="size-3.5" />
                            {{
                                post.type === 'announcement'
                                    ? t("E'lon")
                                    : t('Yangilik')
                            }}
                        </span>
                        <span
                            v-if="post.isPinned"
                            class="inline-flex items-center gap-1 font-semibold text-red-600"
                        >
                            <Pin class="size-3.5" />
                            {{ t('Muhim') }}
                        </span>
                        <span
                            v-if="post.publishedAt"
                            class="ml-auto inline-flex items-center gap-1 text-navy-500"
                        >
                            <CalendarDays class="size-3.5" />
                            {{ formatDateLong(post.publishedAt) }}
                        </span>
                    </div>
                    <h2
                        class="mt-4 font-serif text-xl leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                    >
                        {{ post.title }}
                    </h2>
                    <p
                        v-if="post.excerpt"
                        class="mt-2 line-clamp-3 text-sm leading-relaxed text-navy-600"
                    >
                        {{ post.excerpt }}
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-brand-700"
                    >
                        {{ t('Batafsil') }}
                        <ArrowRight
                            class="size-4 transition-transform group-hover:translate-x-1"
                        />
                    </span>
                </Link>
            </div>

            <div
                v-else
                class="flex flex-col items-center gap-2 py-16 text-center text-navy-500"
            >
                <Newspaper class="size-10 text-navy-300" />
                {{ t("Hozircha bu bo'limda xabarlar yo'q.") }}
            </div>

            <div class="mt-10">
                <Pagination :meta="posts.meta" />
            </div>
        </div>
    </div>
</template>

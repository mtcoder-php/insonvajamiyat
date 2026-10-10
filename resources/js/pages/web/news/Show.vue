<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Megaphone, Newspaper } from '@lucide/vue';
import { computed } from 'vue';
import WebHero from '@/components/web/WebHero.vue';
import { formatDate, formatDateLong } from '@/lib/format';
import { index } from '@/routes/news';
import type { PostDetail, PostItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Yangilik / e'lon sahifasi: to'liq matn va boshqa xabarlar.
 */
const props = defineProps<{
    /** Sarlavha fon rasmi (config journal.heroes) */
    hero: string | null;
    post: PostDetail;
    others: PostItem[];
}>();

// Matn xatboshilarga bo'linadi (bo'sh qator bilan ajratilgan)
const paragraphs = computed(() =>
    (props.post.body ?? props.post.excerpt ?? '')
        .split(/\n{2,}/)
        .map((p) => p.trim())
        .filter(Boolean),
);
</script>

<template>
    <Head :title="post.title" />

    <WebHero
        :image="hero"
        :title="post.title"
        :crumbs="[
            { title: t('Yangiliklar'), href: index() },
            {
                title:
                    post.type === 'announcement' ? t('E\'lon') : t('Yangilik'),
            },
        ]"
    >
        <div
            class="mt-5 flex flex-wrap items-center gap-3 text-sm text-navy-600"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 font-medium ring-1 ring-[#e6e1d6]"
            >
                <Megaphone
                    v-if="post.type === 'announcement'"
                    class="size-4 text-gold-600"
                />
                <Newspaper v-else class="size-4 text-brand-600" />
                {{ post.type === 'announcement' ? t("E'lon") : t('Yangilik') }}
            </span>
            <span
                v-if="post.publishedAt"
                class="inline-flex items-center gap-1.5"
            >
                <CalendarDays class="size-4 text-navy-400" />
                {{ formatDateLong(post.publishedAt) }}
            </span>
        </div>
    </WebHero>

    <div class="bg-white">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-10 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-0 lg:py-12"
        >
            <article class="min-w-0">
                <img
                    v-if="post.imageUrl"
                    :src="post.imageUrl"
                    :alt="post.title"
                    class="mb-8 aspect-[16/8] w-full rounded-xl object-cover shadow-md"
                />
                <p
                    v-if="post.excerpt && post.body"
                    class="font-serif text-xl leading-relaxed text-navy-800"
                >
                    {{ post.excerpt }}
                </p>
                <div
                    class="mt-6 max-w-3xl space-y-5 font-serif text-[17px] leading-[1.8] text-navy-800"
                >
                    <p
                        v-for="(paragraph, i) in paragraphs.slice(
                            post.excerpt && post.body ? 1 : 0,
                        )"
                        :key="i"
                    >
                        {{ paragraph }}
                    </p>
                </div>

                <Link
                    :href="index()"
                    class="group mt-10 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-600"
                >
                    <ArrowLeft
                        class="size-4 transition-transform group-hover:-translate-x-1"
                    />
                    {{ t('Barcha yangiliklar') }}
                </Link>
            </article>

            <aside
                v-if="others.length"
                class="lg:sticky lg:top-6 lg:self-start"
            >
                <div
                    class="rounded-xl border border-[#ebe8e1] bg-[#f8f7f4] p-5"
                >
                    <h2 class="font-serif text-lg font-bold text-navy-900">
                        {{ t('Boshqa xabarlar') }}
                    </h2>
                    <ul class="mt-3 divide-y divide-[#ece8df]">
                        <li v-for="item in others" :key="item.id">
                            <Link :href="item.url" class="group block py-3">
                                <time
                                    v-if="item.publishedAt"
                                    class="text-xs text-navy-400 tabular-nums"
                                >
                                    {{ formatDate(item.publishedAt) }}
                                </time>
                                <p
                                    class="mt-0.5 text-sm leading-snug text-navy-800 transition-colors group-hover:text-brand-700"
                                >
                                    {{ item.title }}
                                </p>
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</template>

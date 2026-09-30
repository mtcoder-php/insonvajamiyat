<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Download, Eye } from '@lucide/vue';
import { formatDate, formatNumber } from '@/lib/format';
import type { ArticleCard } from '@/types';

/**
 * Maqola — ilmiy jurnallardagidek matnli ro'yxat elementi:
 * yo'nalish, sarlavha, mualliflar, sana, DOI, ko'rsatkichlar.
 * Muqova rasmi yuklangan bo'lsa, o'ngda kichik rasm chiqadi.
 */
defineProps<{ article: ArticleCard }>();
</script>

<template>
    <article class="group flex gap-6 py-6 first:pt-0 last:pb-0">
        <div class="min-w-0 flex-1">
            <p
                v-if="article.subject"
                class="text-[11px] font-semibold tracking-[0.14em] text-brand-700 uppercase"
            >
                {{ article.subject.name }}
            </p>
            <h3
                class="mt-1.5 font-serif text-lg leading-snug font-semibold text-navy-950"
            >
                <Link
                    :href="article.url"
                    class="decoration-brand-300 decoration-1 underline-offset-4 transition-colors group-hover:text-brand-700 hover:underline"
                >
                    {{ article.title }}
                </Link>
            </h3>
            <p v-if="article.authors" class="mt-1.5 text-sm text-navy-600">
                {{ article.authors }}
            </p>
            <div
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-navy-400"
            >
                <span
                    v-if="article.publishedAt"
                    class="inline-flex items-center gap-1.5"
                >
                    <CalendarDays class="size-3.5" />
                    {{ formatDate(article.publishedAt) }}
                </span>
                <a
                    v-if="article.doi"
                    :href="`https://doi.org/${article.doi}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-navy-500 hover:text-brand-700"
                >
                    DOI: {{ article.doi }}
                </a>
                <span
                    class="inline-flex items-center gap-1.5"
                    title="Ko'rishlar"
                >
                    <Eye class="size-3.5" />
                    {{ formatNumber(article.views) }}
                </span>
                <span
                    class="inline-flex items-center gap-1.5"
                    title="Yuklab olishlar"
                >
                    <Download class="size-3.5" />
                    {{ formatNumber(article.downloads) }}
                </span>
            </div>
        </div>
        <Link
            v-if="article.coverUrl"
            :href="article.url"
            class="hidden w-36 shrink-0 overflow-hidden rounded-lg sm:block"
            tabindex="-1"
            aria-hidden="true"
        >
            <img
                :src="article.coverUrl"
                alt=""
                loading="lazy"
                class="aspect-[4/3] size-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
        </Link>
    </article>
</template>

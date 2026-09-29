<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    Download,
    Eye,
    Fingerprint,
    UserRound,
} from '@lucide/vue';
import ArticleCover from '@/components/web/ArticleCover.vue';
import { formatDate, formatNumber } from '@/lib/format';
import type { ArticleCard } from '@/types';

/**
 * Maqola kartochkasi.
 *   modern  — home_2.png: rasm ustida yo'nalish yorlig'i, ko'rishlar/yuklashlar
 *   classic — home.png:   rasm ostida yo'nalish, DOI
 */
withDefaults(
    defineProps<{
        article: ArticleCard;
        variant?: 'modern' | 'classic';
    }>(),
    { variant: 'modern' },
);
</script>

<template>
    <article
        class="group flex surface-card-interactive flex-col overflow-hidden"
    >
        <Link
            :href="article.url"
            class="relative block"
            tabindex="-1"
            aria-hidden="true"
        >
            <ArticleCover
                :src="article.coverUrl"
                :alt="article.title"
                :subject-slug="article.subject?.slug"
                :class="
                    variant === 'modern' ? 'aspect-[16/10]' : 'aspect-[16/9]'
                "
            />
            <span
                v-if="variant === 'modern' && article.subject"
                class="absolute top-3 left-3 rounded bg-navy-950/85 px-2 py-1 text-[10px] font-semibold tracking-wider text-white uppercase backdrop-blur"
            >
                {{ article.subject.name }}
            </span>
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <span
                v-if="variant === 'classic' && article.subject"
                class="mb-2 inline-flex w-fit items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-[10px] font-semibold tracking-wider text-brand-700 uppercase"
            >
                {{ article.subject.name }}
            </span>

            <h3
                class="font-serif text-[15px] leading-snug font-semibold text-navy-950"
            >
                <Link
                    :href="article.url"
                    class="line-clamp-3 transition-colors hover:text-brand-700"
                >
                    {{ article.title }}
                </Link>
            </h3>

            <p
                v-if="article.authors"
                class="mt-2 flex items-center gap-1.5 text-xs text-navy-500"
            >
                <UserRound class="size-3.5 shrink-0 text-brand-600" />
                <span class="truncate">{{ article.authors }}</span>
            </p>

            <div class="min-h-4 flex-1" />

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-line pt-3 text-[11px] text-navy-400"
            >
                <span
                    v-if="article.publishedAt"
                    class="inline-flex items-center gap-1"
                >
                    <CalendarDays class="size-3.5" />
                    {{ formatDate(article.publishedAt) }}
                </span>
                <template v-if="variant === 'modern'">
                    <span
                        class="inline-flex items-center gap-1"
                        title="Ko'rishlar"
                    >
                        <Eye class="size-3.5" />
                        {{ formatNumber(article.views) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1"
                        title="Yuklab olishlar"
                    >
                        <Download class="size-3.5" />
                        {{ formatNumber(article.downloads) }}
                    </span>
                </template>
                <span
                    v-else-if="article.doi"
                    class="inline-flex min-w-0 items-center gap-1"
                    title="DOI"
                >
                    <Fingerprint class="size-3.5 shrink-0" />
                    <span class="truncate">{{ article.doi }}</span>
                </span>
            </div>
        </div>
    </article>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    Download,
    Eye,
    Fingerprint,
    FileText,
} from '@lucide/vue';
import ArticleCover from '@/components/web/ArticleCover.vue';
import { formatDate, formatFileSize, formatNumber } from '@/lib/format';
import { subjectTone } from '@/lib/subjects';
import { cn } from '@/lib/utils';
import type { CatalogArticle } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Katalog kartochkasi: "list" — gorizontal (dizayndagi asosiy ko'rinish), "grid" — vertikal.
 */
withDefaults(
    defineProps<{ article: CatalogArticle; layout?: 'list' | 'grid' }>(),
    {
        layout: 'list',
    },
);
</script>

<template>
    <article
        :class="
            cn(
                'group relative flex overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_22px_44px_-28px_rgba(0,36,66,0.55)]',
                layout === 'list' ? 'flex-col sm:flex-row' : 'flex-col',
            )
        "
    >
        <Link
            :href="article.url"
            :class="
                cn(
                    'relative block shrink-0 overflow-hidden',
                    layout === 'list' ? 'sm:w-48' : '',
                )
            "
            tabindex="-1"
            aria-hidden="true"
        >
            <!-- Sarlavha havolasi yonida takrorlanmasin: dekorativ rasm -->
            <ArticleCover
                :src="article.coverUrl"
                alt=""
                :subject-slug="article.subject?.slug"
                :class="
                    cn(
                        'transition-transform duration-500 group-hover:scale-[1.04]',
                        layout === 'list'
                            ? 'aspect-[16/10] sm:aspect-auto sm:h-full'
                            : 'aspect-[16/10]',
                    )
                "
            />
            <span
                v-if="article.subject"
                :class="
                    cn(
                        'absolute top-3 left-3 rounded-md px-2 py-0.5 text-[11px] font-semibold shadow-sm',
                        subjectTone(article.subject.slug).badge,
                    )
                "
                >{{ article.subject.name }}</span
            >
        </Link>

        <div
            :class="
                cn(
                    'flex min-w-0 flex-1 gap-4 p-4',
                    layout === 'list' ? 'flex-col lg:flex-row' : 'flex-col',
                )
            "
        >
            <div class="min-w-0 flex-1">
                <Link :href="article.url">
                    <h3
                        class="line-clamp-3 font-serif text-[17px] leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                    >
                        {{ article.title }}
                    </h3>
                </Link>
                <p class="mt-1.5 text-[13px] font-medium text-brand-700">
                    {{ article.authors }}
                </p>
                <p
                    v-if="article.organization"
                    class="mt-0.5 truncate text-xs text-navy-500"
                >
                    {{ article.organization }}
                </p>
                <div
                    v-if="article.keywords.length"
                    class="mt-3 flex flex-wrap gap-1.5"
                >
                    <Link
                        v-for="word in article.keywords"
                        :key="word"
                        :href="`?keyword=${encodeURIComponent(word)}`"
                        class="rounded-full bg-brand-50/70 px-2.5 py-0.5 text-[11px] font-medium text-brand-800 transition-colors hover:bg-brand-100"
                        >{{ word }}</Link
                    >
                </div>
            </div>

            <div
                :class="
                    cn(
                        'flex shrink-0 flex-col justify-between gap-3 text-xs text-navy-600',
                        layout === 'list' &&
                            'lg:w-56 lg:border-l lg:border-line lg:pl-4',
                    )
                "
            >
                <ul class="grid gap-1.5">
                    <li v-if="article.issue" class="flex items-center gap-1.5">
                        <BookOpen class="size-3.5 text-navy-400" />
                        <Link
                            :href="article.issue.url"
                            class="hover:text-brand-700 hover:underline"
                            >{{
                                t('Jurnal :label', {
                                    label: article.issue.label,
                                })
                            }}</Link
                        >
                    </li>
                    <li
                        v-if="article.publishedAt"
                        class="flex items-center gap-1.5"
                    >
                        <CalendarDays class="size-3.5 text-navy-400" />
                        {{ formatDate(article.publishedAt) }}
                    </li>
                    <li v-if="article.doi" class="flex items-center gap-1.5">
                        <Fingerprint class="size-3.5 shrink-0 text-navy-400" />
                        <span class="truncate">DOI: {{ article.doi }}</span>
                    </li>
                </ul>
                <div class="flex items-center justify-between gap-2">
                    <span v-if="article.pdf" class="flex items-center gap-1.5">
                        <a
                            :href="article.pdf.viewUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 font-medium text-navy-600 transition-colors hover:bg-red-50 hover:text-red-700"
                        >
                            <FileText class="size-3.5 text-red-500" />
                            PDF<template v-if="article.pdf.size">
                                ({{
                                    formatFileSize(article.pdf.size)
                                }})</template
                            >
                        </a>
                        <a
                            :href="article.pdf.downloadUrl"
                            class="inline-flex items-center gap-1 rounded-md border border-line px-2 py-1 font-semibold text-brand-700 transition-all hover:-translate-y-px hover:border-brand-300"
                        >
                            <Download class="size-3.5" />
                            {{ t('Yuklab olish') }}
                        </a>
                    </span>
                    <span v-else />
                    <span
                        class="inline-flex items-center gap-3 text-navy-500 tabular-nums"
                    >
                        <span
                            class="inline-flex items-center gap-1"
                            :title="t('Ko\'rishlar')"
                        >
                            <Eye class="size-3.5" aria-hidden="true" />
                            <span class="sr-only">{{ t("Ko'rishlar") }}:</span>
                            {{ formatNumber(article.views) }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1"
                            :title="
                                t(':count marta yuklab olingan', {
                                    count: formatNumber(article.downloads),
                                })
                            "
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span class="sr-only"
                                >{{ t('Yuklab olishlar') }}:</span
                            >
                            {{ formatNumber(article.downloads) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </article>
</template>

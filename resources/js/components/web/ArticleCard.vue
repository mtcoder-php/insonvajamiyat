<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Fingerprint } from '@lucide/vue';
import ArticleCover from '@/components/web/ArticleCover.vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { formatDate } from '@/lib/format';
import { subjectTone } from '@/lib/subjects';
import { cn } from '@/lib/utils';
import type { ArticleCard } from '@/types';

/**
 * Maqola kartochkasi (home.png): rasm, rangli yo'nalish yorlig'i,
 * sarlavha, mualliflar, sana va DOI.
 */
defineProps<{ article: ArticleCard }>();
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-lg border border-[#ebe8e1] bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 ease-out hover:-translate-y-1 hover:border-[#e2dccf] hover:shadow-[0_16px_36px_-16px_rgba(0,36,66,0.35)]"
    >
        <Link
            :href="article.url"
            tabindex="-1"
            aria-hidden="true"
            class="block"
        >
            <ArticleCover
                :src="article.coverUrl"
                :alt="article.title"
                :subject-slug="article.subject?.slug"
                class="aspect-[16/9]"
            />
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <span
                v-if="article.subject"
                :class="
                    cn(
                        'inline-flex w-fit items-center gap-1.5 rounded-full py-0.5 pr-2.5 pl-0.5 text-[10px] font-semibold tracking-wider uppercase',
                        subjectTone(article.subject.slug).badge,
                    )
                "
            >
                <span
                    :class="
                        cn(
                            'flex size-4 items-center justify-center rounded-full text-white',
                            subjectTone(article.subject.slug).dot,
                        )
                    "
                >
                    <SubjectIcon
                        :slug="article.subject.slug"
                        class="size-2.5"
                        :stroke-width="2.5"
                    />
                </span>
                {{ article.subject.name }}
            </span>

            <h3
                class="mt-2.5 font-serif text-[15px] leading-snug font-normal text-navy-900"
            >
                <Link
                    :href="article.url"
                    class="line-clamp-3 transition-colors group-hover:text-brand-700"
                >
                    {{ article.title }}
                </Link>
            </h3>

            <p
                v-if="article.authors"
                class="mt-2 truncate font-serif text-[13px] text-navy-600"
            >
                {{ article.authors }}
            </p>

            <div class="min-h-3 flex-1" />

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-[#efece5] pt-3 text-[11px] text-navy-500"
            >
                <span
                    v-if="article.publishedAt"
                    class="inline-flex items-center gap-1"
                >
                    <CalendarDays class="size-3.5" />
                    {{ formatDate(article.publishedAt) }}
                </span>
                <a
                    v-if="article.doi"
                    :href="`https://doi.org/${article.doi}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-w-0 items-center gap-1 transition-colors hover:text-brand-700"
                    title="DOI"
                >
                    <Fingerprint class="size-3.5 shrink-0" />
                    <span class="truncate">{{ article.doi }}</span>
                </a>
            </div>
        </div>
    </article>
</template>

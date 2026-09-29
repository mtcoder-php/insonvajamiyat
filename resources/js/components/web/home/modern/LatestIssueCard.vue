<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    FileDown,
    Layers,
} from '@lucide/vue';
import IssueCover from '@/components/web/IssueCover.vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { formatDate, formatFileSize } from '@/lib/format';
import { index as issuesIndex } from '@/routes/issues';
import type { LatestIssue } from '@/types';

/**
 * "Jurnalning so'nggi soni" kartasi (home_2.png).
 */
defineProps<{ issue: LatestIssue | null }>();
</script>

<template>
    <section class="flex flex-col">
        <SectionHeading title="Jurnalning so'nggi soni" />

        <div
            v-if="issue"
            class="flex flex-1 flex-col gap-6 surface-card p-5 sm:flex-row"
        >
            <Link
                :href="issue.url"
                class="mx-auto w-40 shrink-0 transition-transform hover:-translate-y-1 sm:mx-0 sm:w-44"
            >
                <IssueCover
                    :src="issue.coverUrl"
                    :number="issue.number"
                    :year="issue.year"
                />
            </Link>

            <div class="flex min-w-0 flex-1 flex-col">
                <span
                    class="w-fit rounded-md bg-gold-100 px-2.5 py-1 text-xs font-semibold text-gold-700"
                >
                    {{ issue.label }}
                </span>
                <h3
                    class="mt-3 font-serif text-xl leading-snug font-semibold text-navy-950"
                >
                    <Link :href="issue.url" class="hover:text-brand-700">
                        {{ issue.title || `"Inson va Jamiyat" ilmiy jurnali` }}
                    </Link>
                </h3>

                <ul
                    v-if="issue.subjects.length"
                    class="mt-3 flex flex-wrap gap-1.5"
                >
                    <li
                        v-for="subject in issue.subjects"
                        :key="subject"
                        class="rounded-full border border-brand-200 bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-700"
                    >
                        {{ subject }}
                    </li>
                </ul>

                <dl
                    class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-navy-500"
                >
                    <div
                        v-if="issue.publishedAt"
                        class="flex items-center gap-1.5"
                    >
                        <CalendarDays class="size-3.5 text-brand-600" />
                        <dt>Nashr etilgan:</dt>
                        <dd class="font-medium text-navy-800">
                            {{ formatDate(issue.publishedAt) }}
                        </dd>
                    </div>
                    <div
                        v-if="issue.pagesTotal"
                        class="flex items-center gap-1.5"
                    >
                        <BookOpen class="size-3.5 text-brand-600" />
                        <dt>Sahifalar:</dt>
                        <dd class="font-medium text-navy-800">
                            {{ issue.pagesTotal }}
                        </dd>
                    </div>
                    <div
                        v-if="issue.articlesCount"
                        class="flex items-center gap-1.5"
                    >
                        <Layers class="size-3.5 text-brand-600" />
                        <dt>Maqolalar:</dt>
                        <dd class="font-medium text-navy-800">
                            {{ issue.articlesCount }}
                        </dd>
                    </div>
                    <div v-if="issue.pdfSize" class="flex items-center gap-1.5">
                        <FileDown class="size-3.5 text-brand-600" />
                        <dt>PDF:</dt>
                        <dd class="font-medium text-navy-800">
                            {{ formatFileSize(issue.pdfSize) }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="issue.description"
                    class="mt-4 line-clamp-3 text-sm leading-relaxed text-navy-600"
                >
                    {{ issue.description }}
                </p>

                <div
                    class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-5"
                >
                    <Link
                        :href="issue.url"
                        class="inline-flex h-10 items-center gap-2 rounded-full bg-navy-900 px-5 text-sm font-semibold text-white transition-colors hover:bg-navy-800"
                    >
                        Sonni ko'rish
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        :href="issuesIndex()"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-600"
                    >
                        Barcha sonlar
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center gap-2 surface-card p-10 text-center text-sm text-navy-500"
        >
            <BookOpen class="size-8 text-navy-300" />
            Hozircha chop etilgan son yo'q.
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, FileDown, FileText } from '@lucide/vue';
import IssueCover from '@/components/web/IssueCover.vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { formatDateLong } from '@/lib/format';
import { index as issuesIndex } from '@/routes/issues';
import type { LatestIssue } from '@/types';

/**
 * "So'nggi son" paneli (home.png): muqova, son nomi, PDF havolalari.
 */
defineProps<{ issue: LatestIssue | null }>();
</script>

<template>
    <section class="surface-card p-5 sm:p-6">
        <SectionHeading
            title="So'nggi son"
            :href="issuesIndex()"
            link-text="Barcha sonlar"
        />

        <div v-if="issue" class="flex flex-col gap-6 sm:flex-row">
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
                <p class="font-serif text-2xl font-semibold text-navy-950">
                    {{ issue.label }}
                </p>
                <h3 class="mt-1 font-serif text-lg leading-snug text-navy-800">
                    <Link :href="issue.url" class="hover:text-brand-700">
                        {{ issue.title || `"Inson va Jamiyat" ilmiy jurnali` }}
                    </Link>
                </h3>
                <p v-if="issue.publishedAt" class="mt-1 text-xs text-navy-500">
                    Nashr etilgan: {{ formatDateLong(issue.publishedAt) }}
                </p>

                <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs">
                    <li v-if="issue.tocUrl">
                        <a
                            :href="issue.tocUrl"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 font-medium text-navy-700 hover:text-brand-700"
                        >
                            <FileText class="size-4 text-brand-600" />
                            Mundarija (PDF)
                        </a>
                    </li>
                    <li v-if="issue.pdfUrl">
                        <a
                            :href="issue.pdfUrl"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 font-medium text-navy-700 hover:text-brand-700"
                        >
                            <FileDown class="size-4 text-brand-600" />
                            To'liq son (PDF)
                        </a>
                    </li>
                    <li
                        v-if="issue.articlesCount"
                        class="inline-flex items-center gap-1.5 text-navy-600"
                    >
                        <BookOpen class="size-4 text-brand-600" />
                        {{ issue.articlesCount }} ta maqola
                    </li>
                </ul>

                <p
                    v-if="issue.description"
                    class="mt-4 line-clamp-3 text-sm leading-relaxed text-navy-600"
                >
                    {{ issue.description }}
                </p>

                <Link
                    :href="issue.url"
                    class="mt-5 inline-flex h-10 w-fit items-center gap-2 rounded-full bg-navy-900 px-5 text-sm font-semibold text-white transition-colors hover:bg-navy-800"
                >
                    Sonni ko'rish
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>

        <p v-else class="py-8 text-center text-sm text-navy-500">
            Hozircha chop etilgan son yo'q.
        </p>
    </section>
</template>

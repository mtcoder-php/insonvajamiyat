<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    FileDown,
    FileText,
    Fingerprint,
} from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import IssueCover from '@/components/web/IssueCover.vue';
import { index as issuesIndex } from '@/routes/issues';
import type { LatestIssue } from '@/types';

/**
 * "So'nggi son" (home.png): muqova, son raqami, nomi, PDF/DOI havolalari.
 */
defineProps<{ issue: LatestIssue | null }>();
</script>

<template>
    <HomeCard
        title="So'nggi son"
        :href="issuesIndex()"
        link-text="Barcha sonlar"
    >
        <div v-if="issue" class="flex flex-col gap-6 sm:flex-row">
            <Link
                :href="issue.url"
                class="group mx-auto block w-40 shrink-0 sm:mx-0 sm:w-44"
                :aria-label="`${issue.label} sonini ochish`"
            >
                <IssueCover
                    :src="issue.coverUrl"
                    :number="issue.number"
                    :year="issue.year"
                    class="transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_18px_36px_-12px_rgba(0,30,60,0.45)]"
                />
            </Link>

            <div class="flex min-w-0 flex-1 flex-col">
                <p class="font-serif text-2xl font-bold text-navy-950">
                    {{ issue.label }}
                </p>
                <h3
                    class="mt-1.5 font-serif text-lg leading-snug font-normal text-navy-800"
                >
                    <Link
                        :href="issue.url"
                        class="transition-colors hover:text-brand-700"
                    >
                        {{ issue.title || '"Inson va Jamiyat" ilmiy jurnali' }}
                    </Link>
                </h3>

                <ul
                    v-if="issue.tocUrl || issue.pdfUrl || issue.doi"
                    class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-navy-700"
                >
                    <li v-if="issue.tocUrl">
                        <a
                            :href="issue.tocUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 transition-colors hover:text-brand-700"
                        >
                            <FileText class="size-4 text-navy-800" />
                            Mundarija (PDF)
                        </a>
                    </li>
                    <li v-if="issue.pdfUrl">
                        <a
                            :href="issue.pdfUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 transition-colors hover:text-brand-700"
                        >
                            <FileDown class="size-4 text-navy-800" />
                            To'liq son (PDF)
                        </a>
                    </li>
                    <li v-if="issue.doi">
                        <a
                            :href="`https://doi.org/${issue.doi}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 transition-colors hover:text-brand-700"
                        >
                            <Fingerprint class="size-4 text-navy-800" />
                            DOI: {{ issue.doi }}
                        </a>
                    </li>
                </ul>

                <p
                    v-if="issue.description"
                    class="mt-4 line-clamp-3 font-serif text-[15px] leading-relaxed text-navy-700"
                >
                    {{ issue.description }}
                </p>

                <div class="mt-auto pt-5">
                    <Link
                        :href="issue.url"
                        class="group inline-flex h-10 items-center gap-2 rounded-full bg-navy-900 px-6 text-sm font-semibold text-white shadow-md shadow-navy-900/20 transition-all hover:-translate-y-0.5 hover:bg-navy-800 hover:shadow-lg"
                    >
                        Sonni ko'rish
                        <ArrowRight
                            class="size-4 transition-transform group-hover:translate-x-0.5"
                        />
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex flex-col items-center gap-2 py-10 text-center text-sm text-navy-500"
        >
            <BookOpen class="size-8 text-navy-300" />
            Hozircha chop etilgan son yo'q.
        </div>
    </HomeCard>
</template>

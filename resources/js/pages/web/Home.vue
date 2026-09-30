<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import ArticleListItem from '@/components/web/ArticleListItem.vue';
import AnnouncementsCard from '@/components/web/home/AnnouncementsCard.vue';
import HomeHero from '@/components/web/home/HomeHero.vue';
import HomeStats from '@/components/web/home/HomeStats.vue';
import IndexingPartners from '@/components/web/home/IndexingPartners.vue';
import JournalFactsCard from '@/components/web/home/JournalFactsCard.vue';
import NewsAndEvents from '@/components/web/home/NewsAndEvents.vue';
import PublicationProcess from '@/components/web/home/PublicationProcess.vue';
import SubjectsGrid from '@/components/web/home/SubjectsGrid.vue';
import SubmitCard from '@/components/web/home/SubmitCard.vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { HomePageProps } from '@/types';

/**
 * Bosh sahifa. Ma'lumotlar: App\Http\Controllers\Web\HomeController.
 */
defineOptions({
    layout: { header: 'light' },
});

defineProps<HomePageProps>();
</script>

<template>
    <Head title="Bosh sahifa" />

    <HomeHero :issue="latestIssue" />
    <HomeStats :stats="stats" />

    <section class="bg-white py-16 lg:py-20">
        <div
            class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8"
        >
            <div class="min-w-0">
                <SectionHeading
                    eyebrow="Yangi nashrlar"
                    title="So'nggi maqolalar"
                    :href="articlesIndex()"
                    link-text="Barcha maqolalar"
                />
                <div
                    v-if="latestArticles.length"
                    class="divide-y divide-line border-t border-line pt-6"
                >
                    <ArticleListItem
                        v-for="article in latestArticles"
                        :key="article.id"
                        :article="article"
                    />
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-line py-16 text-center"
                >
                    <FileText class="size-8 text-navy-300" />
                    <p class="text-sm text-navy-500">
                        Hozircha nashr etilgan maqolalar yo'q.
                    </p>
                </div>
            </div>

            <aside class="space-y-6 lg:pt-2">
                <SubmitCard />
                <JournalFactsCard />
                <AnnouncementsCard :items="announcements" />
            </aside>
        </div>
    </section>

    <SubjectsGrid :subjects="subjects" />
    <PublicationProcess />
    <NewsAndEvents :news="news" :events="events" />
    <IndexingPartners :indexing="indexing" :partners="partners" />
</template>

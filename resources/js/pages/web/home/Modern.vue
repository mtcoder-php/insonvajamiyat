<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ArticleCard from '@/components/web/ArticleCard.vue';
import AnnouncementsCard from '@/components/web/home/modern/AnnouncementsCard.vue';
import EventsCard from '@/components/web/home/modern/EventsCard.vue';
import HeroSection from '@/components/web/home/modern/HeroSection.vue';
import JournalStatsCard from '@/components/web/home/modern/JournalStatsCard.vue';
import LatestIssueCard from '@/components/web/home/modern/LatestIssueCard.vue';
import PartnersSection from '@/components/web/home/modern/PartnersSection.vue';
import SideCards from '@/components/web/home/modern/SideCards.vue';
import StatsBar from '@/components/web/home/modern/StatsBar.vue';
import SubjectsSection from '@/components/web/home/modern/SubjectsSection.vue';
import WhyUsSection from '@/components/web/home/modern/WhyUsSection.vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { HomePageProps } from '@/types';

/**
 * Bosh sahifa — zamonaviy variant (dizayn: home_2.png).
 * Ma'lumotlar: App\Http\Controllers\Web\HomeController.
 */
defineOptions({
    layout: { header: 'light' },
});

defineProps<HomePageProps>();
</script>

<template>
    <Head title="Bosh sahifa" />

    <HeroSection :indexing="indexing" />
    <StatsBar :stats="stats" />

    <div class="mx-auto max-w-7xl space-y-12 px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1.55fr_1fr_0.85fr]">
            <LatestIssueCard :issue="latestIssue" />
            <AnnouncementsCard :items="announcements" />
            <SideCards />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <section>
                <SectionHeading
                    title="So'nggi maqolalar"
                    :href="articlesIndex()"
                    link-text="Barcha maqolalar"
                />
                <div
                    v-if="latestArticles.length"
                    class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <ArticleCard
                        v-for="article in latestArticles.slice(0, 4)"
                        :key="article.id"
                        :article="article"
                    />
                </div>
                <p
                    v-else
                    class="surface-card p-10 text-center text-sm text-navy-500"
                >
                    Hozircha nashr etilgan maqolalar yo'q.
                </p>
            </section>
            <JournalStatsCard :stats="stats" :monthly="monthlyArticles" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <SubjectsSection v-if="subjects.length" :subjects="subjects" />
            <EventsCard :events="events" />
        </div>
    </div>

    <WhyUsSection />
    <PartnersSection :partners="partners" />
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ArticleCard from '@/components/web/ArticleCard.vue';
import FeaturesStrip from '@/components/web/home/classic/FeaturesStrip.vue';
import HeroSlider from '@/components/web/home/classic/HeroSlider.vue';
import LatestIssuePanel from '@/components/web/home/classic/LatestIssuePanel.vue';
import NewsPanel from '@/components/web/home/classic/NewsPanel.vue';
import SidebarPanels from '@/components/web/home/classic/SidebarPanels.vue';
import SubjectsStrip from '@/components/web/home/classic/SubjectsStrip.vue';
import EventsCard from '@/components/web/home/modern/EventsCard.vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { HomePageProps } from '@/types';

/**
 * Bosh sahifa — klassik variant (dizayn: home.png).
 * Ma'lumotlar: App\Http\Controllers\Web\HomeController.
 */
defineOptions({
    layout: { header: 'classic' },
});

defineProps<HomePageProps>();
</script>

<template>
    <Head title="Bosh sahifa" />

    <HeroSlider :banners="banners" />
    <SubjectsStrip :subjects="subjects" />

    <div
        class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_20rem] lg:px-8"
    >
        <div class="min-w-0 space-y-10">
            <LatestIssuePanel :issue="latestIssue" />

            <section>
                <SectionHeading
                    title="So'nggi maqolalar"
                    :href="articlesIndex()"
                    link-text="Barcha maqolalar"
                />
                <div
                    v-if="latestArticles.length"
                    class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <ArticleCard
                        v-for="article in latestArticles"
                        :key="article.id"
                        :article="article"
                        variant="classic"
                    />
                </div>
                <p
                    v-else
                    class="surface-card p-10 text-center text-sm text-navy-500"
                >
                    Hozircha nashr etilgan maqolalar yo'q.
                </p>
            </section>

            <FeaturesStrip />

            <div class="grid gap-6 md:grid-cols-2">
                <NewsPanel :items="news" />
                <EventsCard :events="events" title="Tadbirlar" />
            </div>
        </div>

        <aside class="min-w-0">
            <SidebarPanels />
        </aside>
    </div>
</template>

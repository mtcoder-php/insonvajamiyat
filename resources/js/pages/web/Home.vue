<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AboutCard from '@/components/web/home/AboutCard.vue';
import BooksCard from '@/components/web/home/BooksCard.vue';
import ContactCard from '@/components/web/home/ContactCard.vue';
import EventsCard from '@/components/web/home/EventsCard.vue';
import FeaturesStrip from '@/components/web/home/FeaturesStrip.vue';
import HeroSlider from '@/components/web/home/HeroSlider.vue';
import JournalFactsCard from '@/components/web/home/JournalFactsCard.vue';
import LatestArticles from '@/components/web/home/LatestArticles.vue';
import LatestIssueCard from '@/components/web/home/LatestIssueCard.vue';
import NewsCard from '@/components/web/home/NewsCard.vue';
import NewsletterCard from '@/components/web/home/NewsletterCard.vue';
import PartnersStrip from '@/components/web/home/PartnersStrip.vue';
import QuickLinksCard from '@/components/web/home/QuickLinksCard.vue';
import SubjectsStrip from '@/components/web/home/SubjectsStrip.vue';
import type { HomePageProps } from '@/types';

/**
 * Bosh sahifa (home.png). Ma'lumotlar: App\Http\Controllers\Web\HomeController.
 *
 *   Slayder → yo'nalishlar qatori →
 *   asosiy qism (so'nggi son, maqolalar, imkoniyatlar, yangiliklar, tadbirlar)
 *   + o'ng ustun (jurnal haqida, ma'lumotlar, obuna, havolalar, aloqa, kitoblar)
 *   → hamkorlar va indekslash bazalari
 */
defineOptions({
    layout: { header: 'light' },
});

defineProps<HomePageProps>();
</script>

<template>
    <Head title="Bosh sahifa" />

    <HeroSlider :slides="heroSlides" />
    <SubjectsStrip :subjects="subjects" />

    <div class="bg-white">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-6 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-0 lg:py-12 xl:grid-cols-[minmax(0,1fr)_22rem] 2xl:grid-cols-[minmax(0,1fr)_24rem]"
        >
            <!-- Oxirgi qator (Yangiliklar + Tadbirlar) o'ng ustun balandligigacha cho'ziladi -->
            <div class="flex min-w-0 flex-col gap-6">
                <LatestIssueCard :issue="latestIssue" />
                <LatestArticles :articles="latestArticles" />
                <FeaturesStrip />
                <div class="grid flex-1 gap-6 md:grid-cols-2">
                    <NewsCard :items="news" class="h-full" />
                    <EventsCard :events="events" class="h-full" />
                </div>
            </div>

            <aside
                class="min-w-0 space-y-5"
                aria-label="Qo'shimcha ma'lumotlar"
            >
                <AboutCard />
                <JournalFactsCard />
                <NewsletterCard />
                <QuickLinksCard />
                <ContactCard />
                <BooksCard :books="books" />
            </aside>
        </div>
    </div>

    <PartnersStrip :partners="partners" />
</template>

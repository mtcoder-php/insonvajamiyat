<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileStack, Route, TriangleAlert } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import AiAssistantCard from '@/components/cabinet/AiAssistantCard.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import AuthorArticlesTable from '@/components/cabinet/AuthorArticlesTable.vue';
import AuthorStatCard from '@/components/cabinet/AuthorStatCard.vue';
import AuthorStatsChart from '@/components/cabinet/AuthorStatsChart.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import MessagesCard from '@/components/cabinet/MessagesCard.vue';
import QuickActionsCard from '@/components/cabinet/QuickActionsCard.vue';
import StatusTimeline from '@/components/cabinet/StatusTimeline.vue';
import SubmissionStepper from '@/components/cabinet/SubmissionStepper.vue';
import { dashboard } from '@/routes/cabinet';
import { create, index as articlesIndex } from '@/routes/cabinet/articles';
import { edit as profileEdit } from '@/routes/profile';
import type { AuthorDashboardProps } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muallif kabineti — bosh sahifa (dizayn: "Muallif kabineti"):
 * banner, statistika, "Mening maqolalarim", jarayon, xabarlar, grafik;
 * o'ngda maqola holati (timeline), tezkor amallar va AI yordamchi.
 */
defineProps<AuthorDashboardProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Muallif kabineti', href: dashboard() }],
    },
});
</script>

<template>
    <Head :title="t('Muallif kabineti')" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            variant="dark"
            :title="t('Muallif kabineti')"
            :description="
                t(
                    'Maqolalaringizni boshqaring, nashr jarayonini kuzating va ilmiy faoliyatingizni rivojlantiring.',
                )
            "
        />

        <Link
            v-if="!profileCompleted"
            :href="profileEdit()"
            class="group flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 transition-all hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-16px_rgba(180,110,0,0.6)]"
        >
            <TriangleAlert class="size-5 shrink-0 text-amber-600" />
            <span class="flex-1">
                <strong class="font-semibold">{{
                    t("Profilingizni to'ldiring.")
                }}</strong>
                {{
                    t(
                        "Maqola yuborishdan oldin tashkilot, lavozim, ilmiy daraja va ORCID ma'lumotlarini kiriting.",
                    )
                }}
            </span>
            <ArrowRight
                class="size-4 shrink-0 transition-transform group-hover:translate-x-0.5"
            />
        </Link>

        <!--
            2xl: chapda asosiy kontent, o'ngda maqola holati / tezkor amallar / AI.
            Torroq ekranda o'ng ustun statistikadan keyin (gorizontal qator) chiqadi.
        -->
        <div
            class="grid grid-cols-1 gap-5 2xl:grid-cols-[minmax(0,1fr)_18rem] 2xl:grid-rows-[auto_auto_auto_1fr]"
        >
            <section
                class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 2xl:grid-cols-5 [&>*:last-child]:col-span-2 md:[&>*:last-child]:col-span-1"
                :aria-label="t('Maqolalar statistikasi')"
            >
                <AuthorStatCard
                    v-for="card in cards"
                    :key="card.key"
                    :card="card"
                />
            </section>

            <!-- O'ng ustun -->
            <aside
                class="grid content-start items-start gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:col-start-2 2xl:row-span-4 2xl:row-start-1 2xl:grid-cols-1"
                :aria-label="t('Maqola holati va tezkor amallar')"
            >
                <DashCard>
                    <header class="mb-4">
                        <h2
                            class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                        >
                            <Route class="size-[18px] text-brand-600" />
                            {{ t('Maqolaning holati') }}
                        </h2>
                    </header>

                    <template v-if="focus">
                        <Link
                            :href="focus.article.url"
                            class="group mb-4 block rounded-lg border border-line bg-[#f8fafd] p-3 transition-all hover:border-brand-200 hover:bg-brand-50/50"
                        >
                            <p
                                class="line-clamp-2 text-[13px] font-semibold text-navy-900 group-hover:text-brand-700"
                            >
                                {{ focus.article.title }}
                            </p>
                            <ArticleStatusPill
                                class="mt-2"
                                :group="focus.article.statusGroup"
                                :label="focus.article.statusLabel"
                            />
                        </Link>
                        <StatusTimeline :steps="focus.steps" />
                    </template>
                    <p v-else class="py-6 text-center text-sm text-navy-500">
                        {{ t("Jarayondagi maqola yo'q") }}
                    </p>
                </DashCard>

                <QuickActionsCard :links="links" />
                <AiAssistantCard />
            </aside>

            <DashCard>
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2
                        class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                    >
                        <FileStack class="size-[18px] text-brand-600" />
                        {{ t('Mening maqolalarim') }}
                    </h2>
                    <Link
                        :href="articlesIndex()"
                        class="group inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:text-brand-600"
                    >
                        {{ t('Barchasi') }}
                        <ArrowRight
                            class="size-3.5 transition-transform group-hover:translate-x-0.5"
                        />
                    </Link>
                </header>
                <AuthorArticlesTable :items="articles" class="-mx-1">
                    <template #empty>
                        <p class="text-sm text-navy-600">
                            {{ t('Siz hali maqola yubormagansiz.') }}
                        </p>
                        <Link
                            :href="create()"
                            class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700"
                        >
                            {{ t('Birinchi maqolani yuborish') }}
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </template>
                </AuthorArticlesTable>
            </DashCard>

            <SubmissionStepper />

            <div
                class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]"
            >
                <MessagesCard :items="messages" />
                <AuthorStatsChart :data="chart" />
            </div>
        </div>
    </div>
</template>

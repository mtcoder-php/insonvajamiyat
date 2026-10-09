<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Download,
    Eye,
    FileText,
    Fingerprint,
    ListOrdered,
    UsersRound,
} from '@lucide/vue';
import IssueCover from '@/components/web/IssueCover.vue';
import WebHero from '@/components/web/WebHero.vue';
import { formatDate, formatFileSize, formatNumber } from '@/lib/format';
import { subjectTone } from '@/lib/subjects';
import { cn } from '@/lib/utils';
import { index } from '@/routes/issues';
import type { IssuePageProps } from '@/types';
import { t, tc } from '@/lib/i18n';

/**
 * Jurnal soni sahifasi: muqova, ma'lumotlar, to'liq PDF va ruknlar bo'yicha mundarija.
 */
defineProps<IssuePageProps>();
</script>

<template>
    <Head :title="t(':label — jurnal soni', { label: issue.label })" />

    <WebHero
        :title="
            t(':year-yil, :number-son', {
                year: issue.year,
                number: issue.number,
            })
        "
        :description="issue.title ?? undefined"
        :image="hero"
        :crumbs="[
            { title: t('Jurnal sonlari'), href: index() },
            { title: issue.label },
        ]"
    >
        <template #lead>
            <IssueCover
                :src="issue.coverUrl"
                :number="issue.number"
                :year="issue.year"
                priority
                class="mt-5 w-40 shrink-0 shadow-[0_30px_60px_-30px_rgba(0,30,60,0.9)] transition-transform duration-500 hover:-translate-y-1 hover:rotate-[-1deg] md:w-48"
            />
        </template>

        <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-navy-700">
            <div class="flex items-center gap-1.5">
                <CalendarDays class="size-4 text-gold-500" />
                <dt class="sr-only">{{ t('Chop etilgan') }}</dt>
                <dd>{{ formatDate(issue.publishedAt) }}</dd>
            </div>
            <div v-if="issue.volume" class="flex items-center gap-1.5">
                <dt class="text-navy-500">{{ t('Jild:') }}</dt>
                <dd>{{ issue.volume }}</dd>
            </div>
            <div class="flex items-center gap-1.5">
                <FileText class="size-4 text-gold-500" />
                <dd>
                    {{ tc(':count maqola', issue.articlesCount ?? 0) }}
                </dd>
            </div>
            <div class="flex items-center gap-1.5">
                <UsersRound class="size-4 text-gold-500" />
                <dd>{{ tc(':count muallif', issue.authorsCount) }}</dd>
            </div>
            <div v-if="issue.pagesTotal" class="flex items-center gap-1.5">
                <ListOrdered class="size-4 text-gold-500" />
                <dd>{{ tc(':count bet', issue.pagesTotal) }}</dd>
            </div>
            <div v-if="issue.doi" class="flex items-center gap-1.5">
                <Fingerprint class="size-4 text-gold-500" />
                <dd>
                    <a
                        :href="`https://doi.org/${issue.doi}`"
                        target="_blank"
                        rel="noopener"
                        class="text-brand-700 hover:underline"
                        >{{ issue.doi }}</a
                    >
                </dd>
            </div>
        </dl>
        <div class="mt-6 flex flex-wrap gap-3">
            <a
                v-if="issue.pdfUrl"
                :href="issue.pdfUrl"
                download
                class="group inline-flex h-11 items-center gap-2 rounded-xl bg-navy-950 px-5 text-sm font-semibold text-white shadow-[0_12px_28px_-14px_rgba(0,30,60,0.9)] transition-all hover:-translate-y-px hover:bg-navy-800"
            >
                <Download
                    class="size-4 text-gold-300 transition-transform duration-300 group-hover:translate-y-0.5"
                />
                {{ t('Butun sonni yuklab olish') }}
                <span v-if="issue.pdfSize" class="font-normal text-white/60"
                    >({{ formatFileSize(issue.pdfSize) }})</span
                >
            </a>
            <a
                v-if="issue.tocUrl"
                :href="issue.tocUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-navy-800 ring-1 ring-line transition-all hover:-translate-y-px hover:text-brand-700 hover:ring-brand-200"
            >
                <ListOrdered class="size-4" />
                {{ t('Mundarija (PDF)') }}
            </a>
        </div>
    </WebHero>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-6 px-4 py-8 sm:px-6 lg:w-[90%] lg:px-0"
        >
            <p
                v-if="issue.description"
                class="max-w-4xl text-[15px] leading-relaxed text-navy-700"
            >
                {{ issue.description }}
            </p>

            <section
                class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-7"
            >
                <h2 class="font-serif text-xl font-bold text-navy-950">
                    {{ t('Mundarija') }}
                </h2>
                <div class="mt-2 gold-rule w-16" />

                <template v-for="(section, s) in sections" :key="s">
                    <h3
                        v-if="section.title"
                        class="mt-6 text-xs font-bold tracking-[0.14em] text-brand-700 uppercase"
                    >
                        {{ section.title }}
                    </h3>
                    <ol class="mt-3 grid gap-2">
                        <li
                            v-for="article in section.articles"
                            :key="article.id"
                        >
                            <div
                                class="group flex flex-col gap-3 rounded-xl border border-transparent px-3 py-3 transition-all hover:border-brand-100 hover:bg-brand-50/40 sm:flex-row sm:items-start"
                            >
                                <div class="min-w-0 flex-1">
                                    <span
                                        v-if="article.subject"
                                        :class="
                                            cn(
                                                'mb-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                subjectTone(
                                                    article.subject.slug,
                                                ).badge,
                                            )
                                        "
                                        >{{ article.subject.name }}</span
                                    >
                                    <Link
                                        :href="article.url"
                                        class="block font-serif text-[17px] leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                                        >{{ article.title }}</Link
                                    >
                                    <p
                                        class="mt-1 text-[13px] font-medium text-brand-700"
                                    >
                                        {{ article.authors }}
                                    </p>
                                    <p
                                        class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-navy-500"
                                    >
                                        <span
                                            v-if="article.doi"
                                            class="inline-flex items-center gap-1"
                                            ><Fingerprint class="size-3.5" />
                                            {{ article.doi }}</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-1 tabular-nums"
                                            ><Eye class="size-3.5" />
                                            {{
                                                formatNumber(article.views)
                                            }}</span
                                        >
                                    </p>
                                </div>
                                <div
                                    class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end"
                                >
                                    <span
                                        v-if="article.pages"
                                        class="rounded-lg bg-[#f2f5fa] px-2.5 py-1 text-xs font-semibold text-navy-800 tabular-nums"
                                        >{{ article.pages }}</span
                                    >
                                    <a
                                        v-if="article.pdfUrl"
                                        :href="article.pdfUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1 rounded-lg border border-line px-2.5 py-1 text-xs font-semibold text-navy-700 transition-colors hover:border-red-200 hover:text-red-700"
                                    >
                                        <FileText
                                            class="size-3.5 text-red-500"
                                        />
                                        PDF
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ol>
                </template>

                <p v-if="!sections.length" class="mt-4 text-sm text-navy-500">
                    {{ t("Bu sonda hozircha maqola e'lon qilinmagan.") }}
                </p>
            </section>

            <nav
                v-if="neighbours.prev || neighbours.next"
                class="flex flex-wrap justify-between gap-3"
                :aria-label="t('Boshqa sonlar')"
            >
                <Link
                    v-if="neighbours.prev"
                    :href="neighbours.prev.url"
                    class="group inline-flex items-center gap-2 rounded-xl border border-line bg-white px-4 py-3 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                >
                    <ArrowLeft
                        class="size-4 transition-transform group-hover:-translate-x-0.5"
                    />
                    {{ neighbours.prev.label }}
                </Link>
                <span v-else />
                <Link
                    v-if="neighbours.next"
                    :href="neighbours.next.url"
                    class="group inline-flex items-center gap-2 rounded-xl border border-line bg-white px-4 py-3 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                >
                    {{ neighbours.next.label }}
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </nav>
        </div>
    </div>
</template>

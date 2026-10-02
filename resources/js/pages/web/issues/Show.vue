<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    ChevronRight,
    Download,
    Eye,
    FileText,
    Fingerprint,
    ListOrdered,
    UsersRound,
} from '@lucide/vue';
import IssueCover from '@/components/web/IssueCover.vue';
import { formatDate, formatFileSize, formatNumber } from '@/lib/format';
import { subjectTone } from '@/lib/subjects';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index } from '@/routes/issues';
import type { IssuePageProps } from '@/types';

/**
 * Jurnal soni sahifasi: muqova, ma'lumotlar, to'liq PDF va ruknlar bo'yicha mundarija.
 */
defineProps<IssuePageProps>();
</script>

<template>
    <Head :title="`${issue.label} — jurnal soni`">
        <meta
            v-if="issue.description"
            head-key="description"
            name="description"
            :content="issue.description.slice(0, 300)"
        />
    </Head>

    <section
        class="relative isolate overflow-hidden bg-navy-gradient text-white"
    >
        <div
            class="absolute inset-0 -z-10 bg-girih opacity-[0.06]"
            aria-hidden="true"
        />
        <div
            class="mx-auto flex w-full max-w-[1700px] flex-col gap-8 px-4 py-9 sm:px-6 md:flex-row md:items-end lg:w-[90%] lg:px-0 lg:py-12"
        >
            <IssueCover
                :src="issue.coverUrl"
                :number="issue.number"
                :year="issue.year"
                class="w-44 shrink-0 shadow-[0_30px_60px_-30px_rgba(0,0,0,0.9)] md:w-52"
            />
            <div class="min-w-0 flex-1">
                <nav
                    class="flex flex-wrap items-center gap-1 text-xs text-white/60"
                    aria-label="Non-yo'l"
                >
                    <Link :href="home()" class="hover:text-white"
                        >Bosh sahifa</Link
                    >
                    <ChevronRight class="size-3.5" />
                    <Link :href="index()" class="hover:text-white"
                        >Jurnal sonlari</Link
                    >
                    <ChevronRight class="size-3.5" />
                    <span class="text-white/85">{{ issue.label }}</span>
                </nav>
                <h1
                    class="mt-3 font-serif text-3xl font-bold text-white sm:text-4xl"
                >
                    {{ issue.year }}-yil, {{ issue.number }}-son
                </h1>
                <p v-if="issue.title" class="mt-2 text-lg text-white/85">
                    {{ issue.title }}
                </p>
                <div class="mt-3 gold-rule w-20" />
                <dl
                    class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/80"
                >
                    <div class="flex items-center gap-1.5">
                        <CalendarDays class="size-4 text-gold-400" />
                        <dt class="sr-only">Chop etilgan</dt>
                        <dd>{{ formatDate(issue.publishedAt) }}</dd>
                    </div>
                    <div v-if="issue.volume" class="flex items-center gap-1.5">
                        <dt class="text-white/60">Jild:</dt>
                        <dd>{{ issue.volume }}</dd>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <FileText class="size-4 text-gold-400" />
                        <dd>{{ issue.articlesCount ?? 0 }} maqola</dd>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <UsersRound class="size-4 text-gold-400" />
                        <dd>{{ issue.authorsCount }} muallif</dd>
                    </div>
                    <div
                        v-if="issue.pagesTotal"
                        class="flex items-center gap-1.5"
                    >
                        <ListOrdered class="size-4 text-gold-400" />
                        <dd>{{ issue.pagesTotal }} bet</dd>
                    </div>
                    <div v-if="issue.doi" class="flex items-center gap-1.5">
                        <Fingerprint class="size-4 text-gold-400" />
                        <dd>
                            <a
                                :href="`https://doi.org/${issue.doi}`"
                                target="_blank"
                                rel="noopener"
                                class="hover:underline"
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
                        class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-navy-950 shadow-[0_12px_28px_-14px_rgba(0,0,0,0.8)] transition-all hover:-translate-y-px hover:bg-gold-100"
                    >
                        <Download class="size-4" /> Butun sonni yuklab olish
                        <span
                            v-if="issue.pdfSize"
                            class="font-normal text-navy-500"
                            >({{ formatFileSize(issue.pdfSize) }})</span
                        >
                    </a>
                    <a
                        v-if="issue.tocUrl"
                        :href="issue.tocUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-white/30 px-5 text-sm font-semibold transition-all hover:-translate-y-px hover:bg-white/10"
                    >
                        <ListOrdered class="size-4" /> Mundarija (PDF)
                    </a>
                </div>
            </div>
        </div>
    </section>

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
                    Mundarija
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
                    Bu sonda hozircha maqola e'lon qilinmagan.
                </p>
            </section>

            <nav
                v-if="neighbours.prev || neighbours.next"
                class="flex flex-wrap justify-between gap-3"
                aria-label="Boshqa sonlar"
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

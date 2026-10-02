<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    ChevronDown,
    Download,
    Eye,
    FileText,
    Layers,
    ListOrdered,
    Search,
    UsersRound,
} from '@lucide/vue';
import { ref } from 'vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import ArticleCover from '@/components/web/ArticleCover.vue';
import IssueCover from '@/components/web/IssueCover.vue';
import WebHero from '@/components/web/WebHero.vue';
import { formatDate, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { about } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index } from '@/routes/issues';
import type { IssueArchiveProps } from '@/types';

/**
 * Jurnal sonlari arxivi (web jurnal sonlari.png).
 */
const props = defineProps<IssueArchiveProps>();

const query = ref('');
const openYears = ref<number[]>(props.tree.length ? [props.tree[0].year] : []);

function toggleYear(year: number): void {
    openYears.value = openYears.value.includes(year)
        ? openYears.value.filter((y) => y !== year)
        : [...openYears.value, year];
}

function setYear(year: number): void {
    router.get(
        index.url(),
        {
            year,
            sort:
                props.filters.sort !== 'newest'
                    ? props.filters.sort
                    : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['filters', 'yearIssues'],
        },
    );
}

const sort = ref(props.filters.sort);

function setSort(): void {
    router.get(
        index.url(),
        {
            year: props.filters.year,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['filters', 'yearIssues'],
        },
    );
}

// Qidiruv katalogda bajariladi (son, maqola nomi yoki muallif)
function search(): void {
    router.get(
        articlesIndex.url(),
        query.value.trim() ? { q: query.value.trim() } : {},
    );
}

function openIssue(event: Event): void {
    const url = (event.target as HTMLSelectElement).value;

    if (url) {
        router.visit(url);
    }
}
</script>

<template>
    <Head title="Jurnal sonlari arxivi">
        <meta
            head-key="description"
            name="description"
            content="Inson va Jamiyat ilmiy jurnalining barcha sonlari: maqolalar ro'yxati va elektron versiyalar."
        />
    </Head>

    <WebHero
        title="Jurnal sonlari arxivi"
        description="«Inson va Jamiyat» ilmiy jurnalining nashr etilgan barcha sonlari, maqolalar ro'yxati va elektron versiyalarini shu yerda topishingiz mumkin."
        :image="hero"
        :crumbs="[{ title: 'Jurnal sonlari arxivi' }]"
    >
        <template #search>
            <div class="flex flex-col gap-2 lg:flex-row">
                <form class="flex flex-1 gap-2" @submit.prevent="search">
                    <label class="relative flex-1">
                        <span class="sr-only">Qidirish</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Maqola nomi yoki muallifni qidiring..."
                            class="h-12 w-full rounded-xl border border-line bg-white pr-4 pl-12 text-[15px] text-navy-900 outline-none placeholder:text-navy-400 focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                        />
                    </label>
                    <button
                        type="submit"
                        class="h-12 rounded-xl bg-navy-900 px-6 text-sm font-semibold text-white transition-all hover:-translate-y-px hover:bg-brand-700"
                    >
                        Qidirish
                    </button>
                </form>
                <div class="grid grid-cols-2 gap-2 lg:w-96">
                    <SelectInput
                        :model-value="filters.year"
                        aria-label="Yil"
                        class="[&_select]:h-12"
                        @update:model-value="(v) => v && setYear(Number(v))"
                    >
                        <option v-for="y in tree" :key="y.year" :value="y.year">
                            {{ y.year }}-yil
                        </option>
                    </SelectInput>
                    <div class="relative">
                        <select
                            aria-label="Son"
                            class="h-12 w-full cursor-pointer appearance-none rounded-lg border border-line bg-white px-3 pr-9 text-sm text-navy-900 outline-none hover:border-navy-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                            @change="openIssue"
                        >
                            <option value="">Sonni tanlang</option>
                            <optgroup
                                v-for="y in tree"
                                :key="y.year"
                                :label="`${y.year}`"
                            >
                                <option
                                    v-for="issue in y.issues"
                                    :key="issue.slug"
                                    :value="issue.url"
                                >
                                    {{ issue.label }}
                                </option>
                            </optgroup>
                        </select>
                        <ChevronDown
                            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-navy-400"
                        />
                    </div>
                </div>
            </div>
        </template>
    </WebHero>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-6 px-4 py-8 sm:px-6 lg:w-[90%] lg:grid-cols-[16rem_minmax(0,1fr)] lg:px-0 2xl:grid-cols-[16rem_minmax(0,1fr)_20rem]"
        >
            <!-- Chap: yillar va yo'nalishlar -->
            <aside class="grid min-w-0 content-start gap-6">
                <section
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        Yillar bo'yicha arxiv
                    </h2>
                    <ul class="grid gap-1">
                        <li v-for="y in tree" :key="y.year">
                            <button
                                type="button"
                                :aria-expanded="openYears.includes(y.year)"
                                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm font-semibold text-navy-900 transition-colors hover:bg-brand-50/60"
                                @click="toggleYear(y.year)"
                            >
                                <ChevronDown
                                    :class="
                                        cn(
                                            'size-4 text-navy-400 transition-transform',
                                            !openYears.includes(y.year) &&
                                                '-rotate-90',
                                        )
                                    "
                                />
                                <span class="flex-1">{{ y.year }}</span>
                                <span
                                    class="rounded-full bg-brand-50 px-2 text-[10px] font-semibold text-brand-700"
                                    >{{ y.count }} son</span
                                >
                            </button>
                            <ul
                                v-show="openYears.includes(y.year)"
                                class="mt-0.5 mb-1 grid gap-0.5 pl-8"
                            >
                                <li v-for="issue in y.issues" :key="issue.slug">
                                    <Link
                                        :href="issue.url"
                                        class="flex items-center justify-between gap-2 rounded-md px-2 py-1 text-[13px] text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                    >
                                        {{ issue.label }}
                                        <span
                                            class="text-[10px] text-navy-400 tabular-nums"
                                            >{{
                                                formatDate(issue.publishedAt)
                                            }}</span
                                        >
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </section>

                <section
                    v-if="subjects.length"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        Yo'nalishlar
                    </h2>
                    <ul class="grid gap-0.5">
                        <li v-for="subject in subjects" :key="subject.slug">
                            <Link
                                :href="`${articlesIndex.url()}?subjects[]=${subject.slug}`"
                                class="flex items-center justify-between rounded-lg px-2 py-1.5 text-[13px] text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            >
                                {{ subject.name }}
                                <span
                                    class="rounded-md bg-[#f2f5fa] px-1.5 text-[11px] font-semibold text-navy-600 tabular-nums"
                                    >{{ subject.count }}</span
                                >
                            </Link>
                        </li>
                    </ul>
                    <Link
                        :href="articlesIndex()"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                    >
                        Barcha maqolalar <ArrowRight class="size-3.5" />
                    </Link>
                </section>
            </aside>

            <!-- Markaz -->
            <div class="grid min-w-0 content-start gap-8">
                <section v-if="latest.length">
                    <h2 class="mb-4 font-serif text-xl font-bold text-navy-950">
                        So'nggi nashrlar
                    </h2>
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="issue in latest"
                            :key="issue.slug"
                            class="group flex flex-col rounded-2xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_24px_48px_-28px_rgba(0,36,66,0.55)]"
                        >
                            <Link :href="issue.url" class="relative block">
                                <IssueCover
                                    :src="issue.coverUrl"
                                    :number="issue.number"
                                    :year="issue.year"
                                    class="mx-auto w-full max-w-48 transition-transform duration-500 group-hover:scale-[1.03]"
                                />
                            </Link>
                            <Link
                                :href="issue.url"
                                class="mt-3 font-serif text-lg font-bold text-navy-950 group-hover:text-brand-700"
                                >{{ issue.label }}</Link
                            >
                            <p
                                class="mt-0.5 flex items-center gap-1.5 text-xs text-navy-500"
                            >
                                <CalendarDays class="size-3.5" />
                                {{ formatDate(issue.publishedAt) }}
                            </p>
                            <p
                                class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs whitespace-nowrap text-navy-600"
                            >
                                <span class="inline-flex items-center gap-1"
                                    ><FileText class="size-3.5 text-navy-400" />
                                    {{ issue.articlesCount ?? 0 }} maqola</span
                                >
                                <span class="inline-flex items-center gap-1"
                                    ><UsersRound
                                        class="size-3.5 text-navy-400"
                                    />
                                    {{ issue.authorsCount }} muallif</span
                                >
                            </p>
                            <div class="mt-auto grid gap-2 pt-4">
                                <a
                                    v-if="issue.pdfUrl"
                                    :href="issue.pdfUrl"
                                    download
                                    class="flex h-9 items-center justify-center gap-1.5 rounded-lg bg-navy-900 text-xs font-semibold text-white transition-all hover:-translate-y-px hover:bg-brand-700"
                                >
                                    <Download class="size-3.5" /> Butun sonni
                                    yuklab olish (PDF)
                                </a>
                                <Link
                                    :href="issue.url"
                                    class="flex h-9 items-center justify-center gap-1.5 rounded-lg border border-line text-xs font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                                >
                                    <ListOrdered class="size-3.5" /> Mundarija
                                    va maqolalar
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>

                <section>
                    <div
                        class="mb-4 flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="mr-2 font-serif text-xl font-bold text-navy-950"
                            >
                                Yilma-yil arxiv
                            </h2>
                            <button
                                v-for="y in tree"
                                :key="y.year"
                                type="button"
                                :class="
                                    cn(
                                        'rounded-full px-4 py-1.5 text-sm font-semibold transition-all',
                                        filters.year === y.year
                                            ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                                            : 'bg-white text-navy-600 ring-1 ring-line hover:text-brand-700 hover:ring-brand-200',
                                    )
                                "
                                @click="setYear(y.year)"
                            >
                                {{ y.year }}
                            </button>
                        </div>
                        <SelectInput
                            v-model="sort"
                            class="w-48"
                            aria-label="Saralash"
                            @change="setSort"
                        >
                            <option value="newest">Eng so'nggi avval</option>
                            <option value="oldest">Raqam bo'yicha</option>
                        </SelectInput>
                    </div>

                    <div
                        v-if="yearIssues.length"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <article
                            v-for="issue in yearIssues"
                            :key="issue.slug"
                            class="group flex gap-4 rounded-2xl border border-line bg-white p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_18px_40px_-26px_rgba(0,36,66,0.5)]"
                        >
                            <Link :href="issue.url" class="shrink-0">
                                <IssueCover
                                    :src="issue.coverUrl"
                                    :number="issue.number"
                                    :year="issue.year"
                                    class="w-24"
                                />
                            </Link>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <Link
                                    :href="issue.url"
                                    class="font-serif text-lg font-bold text-navy-950 group-hover:text-brand-700"
                                    >{{ issue.label }}</Link
                                >
                                <p
                                    v-if="issue.title"
                                    class="line-clamp-2 text-xs text-navy-600"
                                >
                                    {{ issue.title }}
                                </p>
                                <p class="mt-1 text-xs text-navy-500">
                                    {{ formatDate(issue.publishedAt) }}
                                </p>
                                <p
                                    class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-navy-600"
                                >
                                    <span class="inline-flex items-center gap-1"
                                        ><FileText
                                            class="size-3.5 text-navy-400"
                                        />
                                        {{ issue.articlesCount ?? 0 }}
                                        maqola</span
                                    >
                                    <span class="inline-flex items-center gap-1"
                                        ><UsersRound
                                            class="size-3.5 text-navy-400"
                                        />
                                        {{ issue.authorsCount }} muallif</span
                                    >
                                </p>
                                <div class="mt-auto flex flex-wrap gap-2 pt-3">
                                    <a
                                        v-if="issue.pdfUrl"
                                        :href="issue.pdfUrl"
                                        download
                                        class="inline-flex items-center gap-1 rounded-lg border border-line px-2.5 py-1 text-xs font-semibold text-navy-700 transition-colors hover:border-red-200 hover:text-red-700"
                                    >
                                        <Download class="size-3.5" /> PDF
                                    </a>
                                    <Link
                                        :href="issue.url"
                                        class="inline-flex items-center gap-1 rounded-lg border border-line px-2.5 py-1 text-xs font-semibold text-brand-700 transition-colors hover:border-brand-300 hover:bg-brand-50"
                                    >
                                        <BookOpen class="size-3.5" /> Maqolalar
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                    <p
                        v-else
                        class="rounded-2xl border border-dashed border-navy-200 bg-white py-10 text-center text-sm text-navy-500"
                    >
                        Bu yilda chop etilgan son yo'q.
                    </p>
                </section>
            </div>

            <!-- O'ng panel -->
            <aside
                class="grid min-w-0 content-start gap-6 lg:col-span-2 lg:grid-cols-2 2xl:col-span-1 2xl:grid-cols-1"
            >
                <section
                    class="flex gap-4 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 to-white p-5"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-white"
                    >
                        <Layers class="size-5" />
                    </span>
                    <div>
                        <p class="font-serif text-base font-bold text-navy-950">
                            To'liq arxiv
                        </p>
                        <p class="mt-1 text-xs text-navy-600">
                            Barcha nashr etilgan maqolalarni qidiruv va filtrlar
                            bilan ko'ring.
                        </p>
                        <Link
                            :href="articlesIndex()"
                            class="mt-3 inline-flex items-center gap-1 rounded-lg bg-navy-900 px-3 py-1.5 text-xs font-semibold text-white transition-all hover:-translate-y-px hover:bg-brand-700"
                        >
                            Katalogga o'tish <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>

                <section
                    class="relative overflow-hidden rounded-2xl bg-navy-gradient p-5 text-white"
                >
                    <div
                        class="absolute inset-0 bg-girih opacity-[0.07]"
                        aria-hidden="true"
                    />
                    <div class="relative">
                        <p class="font-serif text-lg font-bold">
                            Jurnal haqida
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-white/80">
                            {{ $page.props.journal.description }}
                        </p>
                        <Link
                            :href="about()"
                            class="mt-4 inline-flex items-center gap-1 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-semibold transition-colors hover:bg-white/10"
                        >
                            Batafsil <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>

                <section
                    v-if="latestArticles.length"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-4 font-serif text-base font-bold text-navy-950"
                    >
                        So'nggi maqolalar
                    </h2>
                    <ul class="grid gap-4">
                        <li v-for="article in latestArticles" :key="article.id">
                            <Link :href="article.url" class="group flex gap-3">
                                <ArticleCover
                                    :src="article.coverUrl"
                                    :alt="article.title"
                                    :subject-slug="article.subject?.slug"
                                    class="aspect-square w-16 shrink-0 rounded-lg"
                                />
                                <span class="min-w-0">
                                    <span
                                        class="line-clamp-2 text-[13px] font-semibold text-navy-900 group-hover:text-brand-700"
                                        >{{ article.title }}</span
                                    >
                                    <span
                                        class="mt-1 flex items-center gap-3 text-[11px] text-navy-500"
                                    >
                                        <span class="truncate">{{
                                            article.authors
                                        }}</span>
                                        <span
                                            class="inline-flex items-center gap-0.5 tabular-nums"
                                            ><Eye class="size-3" />
                                            {{
                                                formatNumber(article.views)
                                            }}</span
                                        >
                                    </span>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

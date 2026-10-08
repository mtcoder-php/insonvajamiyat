<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookCopy,
    BookOpen,
    FilePenLine,
    FileSearch,
    LayoutGrid,
    List,
    Megaphone,
    Search,
    Shapes,
    SlidersHorizontal,
    UsersRound,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import IssueCover from '@/components/web/IssueCover.vue';
import WebHero from '@/components/web/WebHero.vue';
import CatalogArticleItem from '@/components/web/catalog/CatalogArticleItem.vue';
import CatalogFilters from '@/components/web/catalog/CatalogFilters.vue';
import { formatDate, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index } from '@/routes/articles';
import { create } from '@/routes/cabinet/articles';
import type {
    CatalogFilters as Filters,
    CatalogProps,
    CatalogSort,
} from '@/types';
import { t, tc } from '@/lib/i18n';

/**
 * Maqolalar katalogi (web maqola katalogi.png).
 */
const props = defineProps<CatalogProps>();

const query = ref(props.filters.q ?? '');
const showFilters = ref(false);
const layout = ref<'list' | 'grid'>('list');
const LAYOUT_KEY = 'catalog-layout';

onMounted(() => {
    try {
        const saved = window.localStorage.getItem(LAYOUT_KEY);
        layout.value = saved === 'grid' ? 'grid' : 'list';
    } catch {
        layout.value = 'list';
    }
});

function setLayout(value: 'list' | 'grid'): void {
    layout.value = value;

    try {
        window.localStorage.setItem(LAYOUT_KEY, value);
    } catch {
        // brauzer xotirasi yopiq — tanlov faqat shu sahifada saqlanadi
    }
}

function visit(next: Partial<Filters>): void {
    const f = { ...props.filters, ...next };

    router.get(
        index.url(),
        {
            q: f.q || undefined,
            subjects: f.subjects.length ? f.subjects : undefined,
            year: f.year ?? undefined,
            issue: f.issue ?? undefined,
            author: f.author || undefined,
            keyword: f.keyword || undefined,
            sort: f.sort !== 'newest' ? f.sort : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['filters', 'articles'],
        },
    );
}

function reset(): void {
    query.value = '';
    router.get(index.url(), {}, { preserveScroll: true });
}

const sorts = computed<{ value: CatalogSort; label: string }[]>(() => [
    { value: 'newest', label: t('Eng yangi avval') },
    { value: 'oldest', label: t('Eng eski avval') },
    { value: 'popular', label: t("Ko'p o'qilgan") },
    { value: 'downloads', label: t("Ko'p yuklab olingan") },
    { value: 'title', label: t('Sarlavha (A–Z)') },
]);

const sort = computed({
    get: () => props.filters.sort,
    set: (value: CatalogSort) => visit({ sort: value }),
});

// Faol filtrlar (chip ko'rinishida, bittalab olib tashlash mumkin)
const chips = computed(() => {
    const f = props.filters;
    const list: { key: string; label: string; clear: Partial<Filters> }[] = [];

    if (f.q) {
        list.push({ key: 'q', label: `«${f.q}»`, clear: { q: null } });
    }

    for (const slug of f.subjects) {
        const name =
            props.facets.subjects.find((s) => s.slug === slug)?.name ?? slug;
        list.push({
            key: `s-${slug}`,
            label: name,
            clear: { subjects: f.subjects.filter((s) => s !== slug) },
        });
    }

    if (f.year) {
        list.push({
            key: 'year',
            label: t(':year-yil', { year: f.year }),
            clear: { year: null },
        });
    }

    if (f.issue) {
        const label =
            props.facets.issues.find((i) => i.slug === f.issue)?.label ??
            f.issue;
        list.push({ key: 'issue', label, clear: { issue: null } });
    }

    if (f.author) {
        list.push({
            key: 'author',
            label: t('Muallif: :name', { name: f.author }),
            clear: { author: null },
        });
    }

    if (f.keyword) {
        list.push({
            key: 'keyword',
            label: `#${f.keyword}`,
            clear: { keyword: null },
        });
    }

    return list;
});

const stats = computed(() => [
    {
        icon: BookCopy,
        value: props.sidebar.stats.articles,
        label: t('Jami maqolalar'),
    },
    {
        icon: BookOpen,
        value: props.sidebar.stats.issues,
        label: t('Nashr etilgan sonlar'),
    },
    {
        icon: UsersRound,
        value: props.sidebar.stats.authors,
        label: t('Mualliflar'),
    },
    {
        icon: Shapes,
        value: props.sidebar.stats.subjects,
        label: t("Yo'nalishlar"),
    },
]);
</script>

<template>
    <Head :title="t('Maqolalar katalogi')" />

    <WebHero
        :title="t('Maqolalar katalogi')"
        :description="
            t(
                'Ilm-fan, ta\'lim, madaniyat va jamiyat sohalariga oid ilmiy maqolalar bazasi. Maqolalarni mavzu, muallif, yil va boshqa mezonlar bo\'yicha qidirishingiz mumkin.',
            )
        "
        :image="hero"
        :crumbs="[{ title: t('Maqolalar katalogi') }]"
    >
        <template #search>
            <form
                class="flex flex-col gap-2 sm:flex-row"
                @submit.prevent="visit({ q: query.trim() || null })"
            >
                <label class="relative flex-1">
                    <span class="sr-only">{{ t('Qidirish') }}</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="
                            t(
                                'Maqola nomi, muallif, kalit so\'z yoki DOI bo\'yicha qidirish...',
                            )
                        "
                        class="h-12 w-full rounded-xl border border-line bg-white pr-4 pl-12 text-[15px] text-navy-900 outline-none placeholder:text-navy-400 focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    />
                </label>
                <button
                    type="submit"
                    class="h-12 rounded-xl bg-brand-600 px-8 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                >
                    {{ t('Qidirish') }}
                </button>
            </form>
        </template>
    </WebHero>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-6 px-4 py-8 sm:px-6 lg:w-[90%] lg:grid-cols-[17rem_minmax(0,1fr)] lg:px-0 2xl:grid-cols-[17rem_minmax(0,1fr)_21rem]"
        >
            <!-- Filtrlar (mobil — tugma bilan ochiladi) -->
            <button
                type="button"
                :aria-expanded="showFilters"
                class="flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-white text-sm font-semibold text-navy-800 transition-colors hover:border-brand-300 lg:hidden"
                @click="showFilters = !showFilters"
            >
                <SlidersHorizontal class="size-4" />
                {{ showFilters ? t('Filtrlarni yopish') : t('Filtrlar') }}
                <span
                    v-if="chips.length"
                    class="rounded-full bg-brand-600 px-1.5 text-[10px] text-white"
                    >{{ chips.length }}</span
                >
            </button>
            <aside :class="cn('min-w-0 lg:block', !showFilters && 'hidden')">
                <CatalogFilters
                    :filters="filters"
                    :facets="facets"
                    @apply="
                        (f) => {
                            showFilters = false;
                            visit(f);
                        }
                    "
                    @reset="reset"
                />
            </aside>

            <!-- Natijalar -->
            <section class="min-w-0">
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-sm text-navy-600">
                        {{ t('Jami topilgan maqolalar:') }}
                        <b class="text-navy-950 tabular-nums">{{
                            formatNumber(articles.meta.total)
                        }}</b>
                    </p>
                    <div class="flex items-center gap-2">
                        <SelectInput
                            v-model="sort"
                            class="w-52"
                            :aria-label="t('Saralash')"
                        >
                            <option
                                v-for="s in sorts"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.label }}
                            </option>
                        </SelectInput>
                        <div
                            class="flex rounded-lg border border-line bg-white p-0.5"
                        >
                            <button
                                type="button"
                                :aria-label="t('Ro\'yxat ko\'rinishi')"
                                :aria-pressed="layout === 'list'"
                                :class="
                                    cn(
                                        'rounded-md p-2 transition-colors',
                                        layout === 'list'
                                            ? 'bg-brand-600 text-white'
                                            : 'text-navy-500 hover:text-navy-900',
                                    )
                                "
                                @click="setLayout('list')"
                            >
                                <List class="size-4" />
                            </button>
                            <button
                                type="button"
                                :aria-label="t('Katak ko\'rinishi')"
                                :aria-pressed="layout === 'grid'"
                                :class="
                                    cn(
                                        'rounded-md p-2 transition-colors',
                                        layout === 'grid'
                                            ? 'bg-brand-600 text-white'
                                            : 'text-navy-500 hover:text-navy-900',
                                    )
                                "
                                @click="setLayout('grid')"
                            >
                                <LayoutGrid class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="chips.length" class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="chip in chips"
                        :key="chip.key"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-700"
                        @click="visit(chip.clear)"
                    >
                        {{ chip.label }} <X class="size-3.5" />
                    </button>
                </div>

                <div
                    v-if="articles.data.length"
                    :class="
                        layout === 'list'
                            ? 'grid gap-4'
                            : 'grid gap-5 sm:grid-cols-2 xl:grid-cols-3'
                    "
                >
                    <CatalogArticleItem
                        v-for="article in articles.data"
                        :key="article.id"
                        :article="article"
                        :layout="layout"
                    />
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-navy-200 bg-white py-16 text-center"
                >
                    <FileSearch class="size-10 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">
                        {{ t('Hech narsa topilmadi') }}
                    </p>
                    <p class="text-xs text-navy-500">
                        {{
                            t(
                                "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring.",
                            )
                        }}
                    </p>
                    <button
                        type="button"
                        class="text-xs font-semibold text-brand-700 hover:underline"
                        @click="reset"
                    >
                        {{ t('Filtrlarni tozalash') }}
                    </button>
                </div>

                <div v-if="articles.meta.last_page > 1" class="mt-6">
                    <Pagination
                        :meta="articles.meta"
                        :preserve-scroll="false"
                    />
                </div>
            </section>

            <!-- Yon panel -->
            <aside
                class="grid min-w-0 content-start gap-6 lg:col-span-2 lg:grid-cols-2 2xl:col-span-1 2xl:grid-cols-1"
            >
                <section
                    v-if="sidebar.keywords.length"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        {{ t("Mashhur kalit so'zlar") }}
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="word in sidebar.keywords"
                            :key="word"
                            type="button"
                            :class="
                                cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-all hover:-translate-y-px',
                                    filters.keyword === word
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-brand-100 bg-brand-50/60 text-brand-800 hover:border-brand-300',
                                )
                            "
                            :aria-pressed="filters.keyword === word"
                            @click="
                                visit({
                                    keyword:
                                        filters.keyword === word ? null : word,
                                })
                            "
                        >
                            {{ word }}
                        </button>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        {{ t('Jurnal statistikasi') }}
                    </h2>
                    <dl class="grid grid-cols-2 gap-3">
                        <div
                            v-for="stat in stats"
                            :key="stat.icon.name"
                            class="flex items-center gap-2.5 rounded-xl bg-[#f5f8fc] p-3 transition-colors hover:bg-brand-50"
                        >
                            <component
                                :is="stat.icon"
                                class="size-5 shrink-0 text-brand-600"
                            />
                            <div class="flex min-w-0 flex-col-reverse">
                                <dt class="truncate text-[11px] text-navy-500">
                                    {{ stat.label }}
                                </dt>
                                <dd
                                    class="text-lg leading-tight font-bold text-navy-950 tabular-nums"
                                >
                                    {{ formatNumber(stat.value) }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="sidebar.latestIssue"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        {{ t("So'nggi nashr") }}
                    </h2>
                    <Link
                        :href="sidebar.latestIssue.url"
                        class="group flex gap-4"
                    >
                        <IssueCover
                            :src="sidebar.latestIssue.coverUrl"
                            :number="sidebar.latestIssue.number"
                            :year="sidebar.latestIssue.year"
                            class="w-24 shrink-0 transition-transform duration-300 group-hover:-translate-y-1"
                        />
                        <span>
                            <span
                                class="block font-serif text-lg font-bold text-navy-950 group-hover:text-brand-700"
                                >{{ sidebar.latestIssue.label }}</span
                            >
                            <span class="mt-1 block text-xs text-navy-500">{{
                                formatDate(sidebar.latestIssue.publishedAt)
                            }}</span>
                            <span class="mt-1 block text-xs text-navy-500">{{
                                tc(
                                    ':count maqola',
                                    sidebar.latestIssue.articlesCount ?? 0,
                                )
                            }}</span>
                            <span
                                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-700"
                                >{{ t('Sonni ochish') }}
                                <ArrowRight class="size-3.5"
                            /></span>
                        </span>
                    </Link>
                </section>

                <section
                    v-if="sidebar.announcements.length"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 flex items-center gap-2 font-serif text-base font-bold text-navy-950"
                    >
                        <Megaphone class="size-4 text-brand-600" />
                        {{ t("E'lonlar") }}
                    </h2>
                    <ul class="grid gap-2">
                        <li
                            v-for="item in sidebar.announcements"
                            :key="item.url"
                        >
                            <Link
                                :href="item.url"
                                class="group grid grid-cols-[4.5rem_1fr] gap-2 rounded-lg px-1 py-1 text-[13px] transition-colors hover:bg-brand-50/60"
                            >
                                <span
                                    class="text-[11px] text-navy-400 tabular-nums"
                                    >{{ formatDate(item.date) }}</span
                                >
                                <span
                                    class="line-clamp-2 text-navy-800 group-hover:text-brand-700"
                                    >{{ item.title }}</span
                                >
                            </Link>
                        </li>
                    </ul>
                </section>

                <section
                    class="relative overflow-hidden rounded-2xl bg-navy-gradient p-5 text-white"
                >
                    <div
                        class="absolute inset-0 bg-girih opacity-[0.07]"
                        aria-hidden="true"
                    />
                    <div class="relative flex gap-4">
                        <span
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/10"
                        >
                            <FilePenLine class="size-6" />
                        </span>
                        <div>
                            <p class="font-serif text-lg font-bold">
                                {{ t('Maqola yuborish') }}
                            </p>
                            <p class="mt-1 text-xs text-white/75">
                                {{
                                    t(
                                        "Ilmiy maqolangizni biz bilan baham ko'ring.",
                                    )
                                }}
                            </p>
                            <Link
                                :href="create()"
                                class="mt-3 inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-navy-900 transition-all hover:-translate-y-px hover:bg-gold-100"
                            >
                                {{ t('Maqola yuborish') }}
                                <ArrowRight class="size-3.5" />
                            </Link>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

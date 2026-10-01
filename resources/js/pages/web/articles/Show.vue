<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    CalendarCheck,
    CalendarDays,
    CalendarPlus,
    ChevronRight,
    Download,
    ExternalLink,
    Eye,
    FileDown,
    FileText,
    Fingerprint,
    Hash,
    Languages,
    Link2,
    Mail,
    ScrollText,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ArticleCover from '@/components/web/ArticleCover.vue';
import CitationBox from '@/components/web/article/CitationBox.vue';
import { formatDate, formatFileSize, formatNumber } from '@/lib/format';
import { subjectTone } from '@/lib/subjects';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import type { ArticlePageProps } from '@/types';

/**
 * Nashr etilgan maqola sahifasi (web maqola view page.png).
 */
const props = defineProps<ArticlePageProps>();

const page = usePage();
const journal = computed(() => page.props.journal);

const meta = computed(() =>
    [
        { icon: BookOpen, label: 'Jurnal', value: journal.value.name },
        { icon: Hash, label: 'Son', value: props.article.issue?.label },
        { icon: FileText, label: 'Sahifa', value: props.article.pages },
        { icon: ScrollText, label: 'UDK', value: props.article.udc },
        { icon: Fingerprint, label: 'DOI', value: props.article.doi },
    ].filter((m) => m.value),
);

const about = computed(() =>
    [
        {
            icon: CalendarPlus,
            label: 'Yuborilgan',
            value: formatDate(props.article.submittedAt),
        },
        {
            icon: CalendarCheck,
            label: 'Qabul qilingan',
            value: formatDate(props.article.acceptedAt),
        },
        {
            icon: CalendarDays,
            label: 'Nashr etilgan',
            value: formatDate(props.article.publishedAt),
        },
        { icon: FileText, label: 'Maqola turi', value: props.article.type },
        { icon: Languages, label: 'Til', value: props.article.language },
        { icon: Hash, label: 'Sahifalar', value: props.article.pages },
    ].filter((m) => m.value),
);

const linkCopied = ref(false);

async function copyLink(): Promise<void> {
    try {
        await navigator.clipboard.writeText(window.location.href);
        linkCopied.value = true;
        window.setTimeout(() => (linkCopied.value = false), 2000);
    } catch {
        linkCopied.value = false;
    }
}

const references = computed(() =>
    (props.article.references ?? '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean),
);
</script>

<template>
    <Head :title="article.title">
        <meta
            v-if="article.abstract"
            head-key="description"
            name="description"
            :content="article.abstract.slice(0, 300)"
        />
        <meta name="citation_title" :content="article.title" />
        <meta
            v-for="author in article.authors"
            :key="author.name"
            name="citation_author"
            :content="author.name"
        />
        <meta
            v-if="article.publishedAt"
            name="citation_publication_date"
            :content="article.publishedAt.slice(0, 10)"
        />
        <meta name="citation_journal_title" :content="journal.name" />
        <meta v-if="article.doi" name="citation_doi" :content="article.doi" />
        <meta
            v-if="article.pdf"
            name="citation_pdf_url"
            :content="article.pdf.viewUrl"
        />
    </Head>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-6 px-4 py-6 sm:px-6 lg:w-[90%] lg:px-0 lg:py-8 xl:grid-cols-[minmax(0,1fr)_21rem]"
        >
            <div class="grid min-w-0 content-start gap-6">
                <!-- Sarlavha kartasi -->
                <section
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-7"
                >
                    <nav
                        class="mb-4 flex flex-wrap items-center gap-1 text-xs text-navy-500"
                        aria-label="Non-yo'l"
                    >
                        <Link
                            :href="home()"
                            class="transition-colors hover:text-brand-700"
                            >Bosh sahifa</Link
                        >
                        <ChevronRight class="size-3.5 text-navy-300" />
                        <Link
                            :href="articlesIndex()"
                            class="transition-colors hover:text-brand-700"
                            >Maqolalar katalogi</Link
                        >
                        <ChevronRight class="size-3.5 text-navy-300" />
                        <span class="text-navy-800">Maqola</span>
                    </nav>

                    <div
                        class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-if="article.subject"
                                    :class="
                                        cn(
                                            'rounded-full px-3 py-1 text-xs font-semibold',
                                            subjectTone(article.subject.slug)
                                                .badge,
                                        )
                                    "
                                    >{{ article.subject.name }}</span
                                >
                                <span
                                    class="rounded-full border border-line px-3 py-1 text-xs font-medium text-navy-600"
                                    >{{ article.language }}</span
                                >
                            </div>
                            <h1
                                class="mt-3 font-serif text-2xl leading-tight font-bold text-navy-950 md:text-[2rem]"
                            >
                                {{ article.title }}
                            </h1>
                        </div>
                        <div
                            class="flex shrink-0 items-center gap-5 text-navy-700"
                        >
                            <span class="flex items-center gap-2">
                                <Eye class="size-5 text-navy-400" />
                                <span class="leading-tight">
                                    <b class="block text-sm tabular-nums">{{
                                        formatNumber(article.views)
                                    }}</b>
                                    <span class="text-[11px] text-navy-500"
                                        >Ko'rishlar</span
                                    >
                                </span>
                            </span>
                            <span class="flex items-center gap-2">
                                <FileDown class="size-5 text-navy-400" />
                                <span class="leading-tight">
                                    <b class="block text-sm tabular-nums">{{
                                        formatNumber(article.downloads)
                                    }}</b>
                                    <span class="text-[11px] text-navy-500"
                                        >Yuklab olishlar</span
                                    >
                                </span>
                            </span>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-navy-600 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                @click="copyLink"
                            >
                                <Link2 class="size-4" />
                                {{ linkCopied ? 'Nusxalandi' : 'Havola' }}
                            </button>
                        </div>
                    </div>

                    <!-- Mualliflar -->
                    <ul class="mt-5 flex flex-wrap gap-x-8 gap-y-4">
                        <li
                            v-for="author in article.authors"
                            :key="author.name"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex size-10 items-center justify-center rounded-full bg-navy-50 text-navy-500 ring-2 ring-white"
                            >
                                <UserRound class="size-5" />
                            </span>
                            <span>
                                <span
                                    class="flex items-center gap-1 text-sm font-semibold text-navy-900"
                                >
                                    {{ author.name
                                    }}<sup
                                        v-if="author.affiliation"
                                        class="text-brand-700"
                                        >{{ author.affiliation }}</sup
                                    >
                                    <Mail
                                        v-if="author.isCorresponding"
                                        class="size-3.5 text-gold-500"
                                        aria-label="Aloqa uchun mas'ul muallif"
                                    />
                                </span>
                                <a
                                    v-if="author.orcid"
                                    :href="`https://orcid.org/${author.orcid}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 text-xs text-navy-500 transition-colors hover:text-[#a6ce39]"
                                >
                                    <span
                                        class="flex size-3.5 items-center justify-center rounded-full bg-[#a6ce39] text-[7px] font-bold text-white"
                                        >iD</span
                                    >
                                    {{ author.orcid }}
                                </a>
                                <span
                                    v-else-if="author.degree"
                                    class="block text-xs text-navy-500"
                                    >{{ author.degree }}</span
                                >
                            </span>
                        </li>
                    </ul>
                    <ol
                        v-if="article.affiliations.length"
                        class="mt-4 grid gap-0.5 text-xs text-navy-600"
                    >
                        <li
                            v-for="(organization, i) in article.affiliations"
                            :key="organization"
                        >
                            <sup class="text-brand-700">{{ i + 1 }}</sup>
                            {{ organization }}
                        </li>
                    </ol>

                    <dl
                        class="mt-5 flex flex-wrap gap-x-7 gap-y-2 border-t border-line pt-4 text-[13px]"
                    >
                        <div
                            v-for="item in meta"
                            :key="item.label"
                            class="flex items-center gap-1.5"
                        >
                            <component
                                :is="item.icon"
                                class="size-4 text-navy-400"
                            />
                            <dt class="text-navy-500">{{ item.label }}:</dt>
                            <dd class="font-semibold text-navy-800">
                                <a
                                    v-if="
                                        item.label === 'DOI' && article.doiUrl
                                    "
                                    :href="article.doiUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="hover:text-brand-700 hover:underline"
                                    >{{ item.value }}</a
                                >
                                <Link
                                    v-else-if="
                                        item.label === 'Son' && article.issue
                                    "
                                    :href="article.issue.url"
                                    class="hover:text-brand-700 hover:underline"
                                    >{{ item.value }}</Link
                                >
                                <template v-else>{{ item.value }}</template>
                            </dd>
                        </div>
                    </dl>
                </section>

                <div
                    class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]"
                >
                    <div class="grid min-w-0 gap-6">
                        <!-- Annotatsiya -->
                        <section
                            class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-7"
                        >
                            <div
                                :class="
                                    cn(
                                        'grid gap-6',
                                        article.coverUrl &&
                                            'md:grid-cols-[16rem_minmax(0,1fr)]',
                                    )
                                "
                            >
                                <ArticleCover
                                    v-if="article.coverUrl"
                                    :src="article.coverUrl"
                                    :alt="article.title"
                                    :subject-slug="article.subject?.slug"
                                    class="aspect-[4/3] rounded-xl"
                                />
                                <div>
                                    <h2
                                        class="font-serif text-lg font-bold text-navy-950"
                                    >
                                        Annotatsiya
                                    </h2>
                                    <p
                                        class="mt-2 text-[15px] leading-relaxed whitespace-pre-line text-navy-700"
                                    >
                                        {{
                                            article.abstract ??
                                            'Annotatsiya kiritilmagan.'
                                        }}
                                    </p>
                                    <template v-if="article.keywords.length">
                                        <h3
                                            class="mt-5 font-serif text-base font-bold text-navy-950"
                                        >
                                            Kalit so'zlar
                                        </h3>
                                        <div
                                            class="mt-2 flex flex-wrap gap-1.5"
                                        >
                                            <Link
                                                v-for="word in article.keywords"
                                                :key="word"
                                                :href="articlesIndex()"
                                                class="rounded-md border border-brand-100 bg-brand-50/60 px-2.5 py-1 text-xs font-medium text-brand-800 transition-colors hover:border-brand-300 hover:bg-brand-100"
                                                >{{ word }}</Link
                                            >
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </section>

                        <!-- Adabiyotlar -->
                        <section
                            v-if="references.length"
                            class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-7"
                        >
                            <h2
                                class="font-serif text-lg font-bold text-navy-950"
                            >
                                Foydalanilgan adabiyotlar
                            </h2>
                            <ol
                                class="mt-3 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed text-navy-700 marker:text-navy-400"
                            >
                                <li
                                    v-for="(item, i) in references"
                                    :key="i"
                                    class="break-words"
                                >
                                    {{ item.replace(/^\d+[.)]\s*/, '') }}
                                </li>
                            </ol>
                        </section>

                        <!-- Oldingi / keyingi -->
                        <nav
                            v-if="
                                article.neighbours.prev ||
                                article.neighbours.next
                            "
                            class="grid gap-3 sm:grid-cols-2"
                            aria-label="Sondagi boshqa maqolalar"
                        >
                            <Link
                                v-if="article.neighbours.prev"
                                :href="article.neighbours.prev.url"
                                class="group rounded-xl border border-line bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_32px_-22px_rgba(0,36,66,0.5)]"
                            >
                                <span
                                    class="flex items-center gap-1 text-xs font-semibold text-brand-700"
                                >
                                    <ArrowLeft
                                        class="size-3.5 transition-transform group-hover:-translate-x-0.5"
                                    />
                                    Oldingi maqola
                                </span>
                                <span
                                    class="mt-1 line-clamp-2 block text-sm font-semibold text-navy-900"
                                    >{{ article.neighbours.prev.title }}</span
                                >
                            </Link>
                            <span v-else class="hidden sm:block" />
                            <Link
                                v-if="article.neighbours.next"
                                :href="article.neighbours.next.url"
                                class="group rounded-xl border border-line bg-white p-4 text-right transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_32px_-22px_rgba(0,36,66,0.5)]"
                            >
                                <span
                                    class="flex items-center justify-end gap-1 text-xs font-semibold text-brand-700"
                                >
                                    Keyingi maqola
                                    <ArrowRight
                                        class="size-3.5 transition-transform group-hover:translate-x-0.5"
                                    />
                                </span>
                                <span
                                    class="mt-1 line-clamp-2 block text-sm font-semibold text-navy-900"
                                    >{{ article.neighbours.next.title }}</span
                                >
                            </Link>
                        </nav>
                    </div>

                    <!-- PDF va iqtibos -->
                    <aside
                        class="order-first grid min-w-0 content-start gap-6 lg:sticky lg:top-24 lg:order-none"
                    >
                        <section
                            class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                        >
                            <template v-if="article.pdf">
                                <a
                                    :href="article.pdf.downloadUrl"
                                    class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                                >
                                    <Download class="size-4" />
                                    PDF yuklab olish
                                    <span
                                        v-if="article.pdf.size"
                                        class="font-normal opacity-80"
                                        >({{
                                            formatFileSize(article.pdf.size)
                                        }})</span
                                    >
                                </a>
                                <a
                                    :href="article.pdf.viewUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-2 flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-brand-200 text-sm font-semibold text-brand-700 transition-all hover:-translate-y-px hover:bg-brand-50"
                                >
                                    <ExternalLink class="size-4" /> Onlayn
                                    ko'rish
                                </a>
                            </template>
                            <p
                                v-else
                                class="rounded-xl bg-[#f5f8fc] px-4 py-3 text-center text-xs text-navy-500"
                            >
                                PDF versiya tez orada joylanadi.
                            </p>
                            <div class="mt-5 border-t border-line pt-5">
                                <CitationBox :citations="article.citations" />
                            </div>
                        </section>

                        <section
                            class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                        >
                            <h2
                                class="mb-3 font-serif text-base font-bold text-navy-950"
                            >
                                Maqola haqida
                            </h2>
                            <dl class="grid gap-3">
                                <div
                                    v-for="item in about"
                                    :key="item.label"
                                    class="flex items-center gap-3"
                                >
                                    <span
                                        class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4"
                                        />
                                    </span>
                                    <span>
                                        <dt class="text-[11px] text-navy-500">
                                            {{ item.label }}
                                        </dt>
                                        <dd
                                            class="text-[13px] font-semibold text-navy-900"
                                        >
                                            {{ item.value }}
                                        </dd>
                                    </span>
                                </div>
                            </dl>
                        </section>
                    </aside>
                </div>
            </div>

            <!-- O'ng ustun -->
            <aside class="grid min-w-0 content-start gap-6">
                <section
                    v-if="article.related.length"
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <header class="mb-4 flex items-center justify-between">
                        <h2
                            class="font-serif text-base font-bold text-navy-950"
                        >
                            Tegishli maqolalar
                        </h2>
                        <Link
                            :href="articlesIndex()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                        >
                            Barchasi <ArrowRight class="size-3.5" />
                        </Link>
                    </header>
                    <ul class="grid gap-4">
                        <li v-for="item in article.related" :key="item.id">
                            <Link :href="item.url" class="group flex gap-3">
                                <ArticleCover
                                    :src="item.coverUrl"
                                    :alt="item.title"
                                    :subject-slug="item.subject?.slug"
                                    class="aspect-square w-20 shrink-0 rounded-lg transition-transform duration-300 group-hover:scale-[1.04]"
                                />
                                <span class="min-w-0">
                                    <span
                                        v-if="item.subject"
                                        :class="
                                            cn(
                                                'inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                subjectTone(item.subject.slug)
                                                    .badge,
                                            )
                                        "
                                        >{{ item.subject.name }}</span
                                    >
                                    <span
                                        class="mt-1 line-clamp-2 block text-[13px] font-semibold text-navy-900 group-hover:text-brand-700"
                                        >{{ item.title }}</span
                                    >
                                    <span
                                        class="mt-0.5 block truncate text-[11px] text-navy-500"
                                        >{{ item.authors }}</span
                                    >
                                </span>
                            </Link>
                        </li>
                    </ul>
                </section>

                <section
                    class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <div
                        class="bg-gradient-to-br from-navy-900 to-brand-800 px-5 py-4 text-white"
                    >
                        <p class="font-serif text-lg font-bold">
                            {{ journal.name }}
                        </p>
                        <p class="text-xs italic opacity-80">
                            {{ journal.subtitle }}
                        </p>
                    </div>
                    <div class="p-5">
                        <p class="text-[13px] leading-relaxed text-navy-600">
                            {{ journal.description }}
                        </p>
                        <dl class="mt-3 grid gap-1 text-xs text-navy-600">
                            <div v-if="journal.issn">
                                <dt class="inline text-navy-400">ISSN:</dt>
                                {{ journal.issn }}
                            </div>
                            <div v-if="journal.eissn">
                                <dt class="inline text-navy-400">e-ISSN:</dt>
                                {{ journal.eissn }}
                            </div>
                        </dl>
                        <Link
                            :href="links.about"
                            class="mt-4 inline-flex items-center gap-1 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-brand-700 transition-all hover:-translate-y-px hover:border-brand-300"
                        >
                            Batafsil <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-serif text-base font-bold text-navy-950"
                    >
                        Foydali havolalar
                    </h2>
                    <ul class="grid gap-1">
                        <li>
                            <Link
                                :href="links.guidelines"
                                class="flex items-center gap-3 rounded-lg px-2 py-2 text-[13px] text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            >
                                <FileText class="size-4 text-navy-400" />
                                Maqola tayyorlash bo'yicha yo'riqnoma
                            </Link>
                        </li>
                        <li v-if="links.template">
                            <a
                                :href="links.template"
                                class="flex items-center gap-3 rounded-lg px-2 py-2 text-[13px] text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            >
                                <Download class="size-4 text-navy-400" />
                                Word shabloni (yuklab olish)
                            </a>
                        </li>
                        <li v-if="article.issue">
                            <Link
                                :href="article.issue.url"
                                class="flex items-center gap-3 rounded-lg px-2 py-2 text-[13px] text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            >
                                <BookOpen class="size-4 text-navy-400" />
                                {{ article.issue.label }} soni
                            </Link>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    BookOpenText,
    Building2,
    CalendarDays,
    Download,
    ExternalLink,
    Eye,
    FileText,
    Hash,
    Languages,
    Layers,
    Star,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import BookCard from '@/components/web/library/BookCard.vue';
import WebHero from '@/components/web/WebHero.vue';
import { formatFileSize, formatNumber } from '@/lib/format';
import { t } from '@/lib/i18n';
import { index } from '@/routes/library';
import type { LibraryShowProps } from '@/types';

/**
 * Kitob sahifasi (/library/{slug}): muqova, ma'lumotlar, annotatsiya, PDF ni o'qish /
 * yuklab olish yoki tashqi havola, shu yo'nalishdagi boshqa kitoblar.
 */
const props = defineProps<LibraryShowProps>();

const languageNames = computed<Record<string, string>>(() => ({
    uz: t("O'zbekcha"),
    ru: t('Ruscha'),
    en: t('Inglizcha'),
}));

const details = computed(() =>
    [
        { icon: UserRound, label: t('Muallif'), value: props.book.author },
        { icon: Building2, label: t('Nashriyot'), value: props.book.publisher },
        {
            icon: CalendarDays,
            label: t('Nashr yili'),
            value: props.book.year ? String(props.book.year) : null,
        },
        {
            icon: BookOpenText,
            label: t('Betlar soni'),
            value: props.book.pages ? String(props.book.pages) : null,
        },
        { icon: Hash, label: 'ISBN', value: props.book.isbn },
        {
            icon: Languages,
            label: t('Til'),
            value: languageNames.value[props.book.language],
        },
        {
            icon: Layers,
            label: t("Yo'nalish"),
            value: props.book.subject?.name ?? null,
        },
    ].filter((item) => item.value),
);

const subjectHref = computed(() =>
    props.book.subject
        ? index({ query: { subject: props.book.subject.slug } })
        : null,
);
</script>

<template>
    <Head :title="book.title" />

    <WebHero
        :image="hero"
        :title="book.title"
        :description="book.author"
        :crumbs="[
            { title: t('Kutubxona'), href: index() },
            { title: book.title },
        ]"
    >
        <template #lead>
            <div class="shrink-0 [perspective:1000px]">
                <div
                    class="group relative aspect-[2/3] w-40 origin-left overflow-hidden rounded-[3px_10px_10px_3px] bg-navy-900 shadow-[0_24px_40px_-18px_rgba(0,30,60,0.75)] transition-transform duration-500 hover:[transform:rotateY(-12deg)] sm:w-48"
                >
                    <img
                        v-if="book.coverUrl"
                        :src="book.coverUrl"
                        :alt="book.title"
                        class="size-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-full flex-col items-center justify-center gap-3 bg-girih-gold p-5 text-center"
                    >
                        <BookOpen class="size-10 text-gold-300" />
                        <span
                            class="line-clamp-5 font-serif text-base leading-snug font-bold text-white"
                            >{{ book.title }}</span
                        >
                    </div>
                    <span
                        class="absolute inset-y-0 left-0 w-2.5 bg-gradient-to-r from-black/35 to-transparent"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </template>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <span
                v-if="book.isFeatured"
                class="inline-flex items-center gap-1 rounded-full bg-gold-100 px-2.5 py-1 text-xs font-semibold text-gold-700"
            >
                <Star class="size-3.5 fill-current" />
                {{ t('Tavsiya etilgan') }}
            </span>
            <Link
                v-if="book.subject && subjectHref"
                :href="subjectHref"
                class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-100"
            >
                <Layers class="size-3.5" /> {{ book.subject.name }}
            </Link>
            <span
                class="inline-flex items-center gap-3 px-1 text-xs text-navy-500 tabular-nums"
            >
                <span class="inline-flex items-center gap-1"
                    ><Eye class="size-3.5" />
                    {{ formatNumber(book.views) }}</span
                >
                <span v-if="book.file" class="inline-flex items-center gap-1"
                    ><Download class="size-3.5" />
                    {{ formatNumber(book.downloads) }}</span
                >
            </span>
        </div>

        <div class="mt-6 flex flex-wrap gap-2.5">
            <a
                v-if="book.file"
                :href="book.file.readUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-navy-900 px-5 text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(0,30,60,0.9)] transition-all hover:-translate-y-0.5 hover:bg-brand-700"
            >
                <BookOpenText class="size-4" /> {{ t("Onlayn o'qish") }}
            </a>
            <a
                v-if="book.file"
                :href="book.file.downloadUrl"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-navy-900 ring-1 ring-line transition-all hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-300"
            >
                <Download class="size-4" />
                {{ t('PDF yuklab olish') }}
                <span
                    v-if="book.file.size"
                    class="text-xs font-medium text-navy-400"
                    >{{ formatFileSize(book.file.size) }}</span
                >
            </a>
            <a
                v-if="book.externalUrl"
                :href="book.externalUrl"
                target="_blank"
                rel="noopener nofollow"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-semibold text-navy-900 ring-1 ring-line transition-all hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-300"
            >
                <ExternalLink class="size-4" /> {{ t("Manbaga o'tish") }}
            </a>
        </div>
    </WebHero>

    <div class="bg-white">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-8 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-0 lg:py-12"
        >
            <section class="min-w-0">
                <h2 class="font-serif text-2xl font-bold text-navy-950">
                    {{ t('Annotatsiya') }}
                </h2>
                <div class="mt-3 gold-rule w-16" />
                <p
                    v-if="book.description"
                    class="mt-5 text-[15px] leading-relaxed whitespace-pre-line text-navy-700"
                >
                    {{ book.description }}
                </p>
                <p v-else class="mt-5 text-sm text-navy-400">
                    {{ t('Annotatsiya kiritilmagan.') }}
                </p>

                <div
                    v-if="!book.file && !book.externalUrl"
                    class="mt-6 flex items-center gap-3 rounded-xl bg-[#f8f7f4] p-4 text-sm text-navy-600"
                >
                    <FileText class="size-5 shrink-0 text-navy-400" />
                    {{
                        t(
                            "Bu kitobning elektron varianti hozircha mavjud emas. Uni jurnal tahririyatidan so'rashingiz mumkin.",
                        )
                    }}
                </div>
            </section>

            <aside
                class="h-fit rounded-2xl border border-[#ebe8e1] bg-[#f8f7f4] p-5"
            >
                <h2 class="mb-3 font-serif text-lg font-bold text-navy-950">
                    {{ t('Kitob haqida') }}
                </h2>
                <dl class="divide-y divide-[#e8e4db]">
                    <div
                        v-for="item in details"
                        :key="item.label"
                        class="flex items-start gap-3 py-2.5 text-sm"
                    >
                        <component
                            :is="item.icon"
                            class="mt-0.5 size-4 shrink-0 text-gold-600"
                        />
                        <dt class="w-28 shrink-0 text-navy-500">
                            {{ item.label }}
                        </dt>
                        <dd
                            class="min-w-0 font-medium break-words text-navy-900"
                        >
                            {{ item.value }}
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>

    <section v-if="related.length" class="bg-[#f6f8fb]">
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-12"
        >
            <div class="mb-5 flex items-end justify-between gap-4">
                <h2 class="font-serif text-2xl font-bold text-navy-950">
                    {{ t('Boshqa kitoblar') }}
                </h2>
                <Link
                    :href="index()"
                    class="group inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700"
                >
                    {{ t('Kutubxona') }}
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-1"
                    />
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                <BookCard v-for="item in related" :key="item.id" :book="item" />
            </div>
        </div>
    </section>
</template>

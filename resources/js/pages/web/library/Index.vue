<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, FileText, LibraryBig, Search, Star, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import BookCard from '@/components/web/library/BookCard.vue';
import WebHero from '@/components/web/WebHero.vue';
import { formatNumber } from '@/lib/format';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index } from '@/routes/library';
import type { LibraryIndexProps, LibraryLanguage, LibrarySort } from '@/types';

/**
 * Kutubxona (/library): qidiruv, ilmiy yo'nalish, til va saralash; "Tavsiya etilgan" qatori
 * (filtrsiz birinchi sahifada) va kitoblar to'ri.
 */
const props = defineProps<LibraryIndexProps>();

const q = ref(props.filters.q);

type Filters = {
    q: string;
    subject: string | null;
    language: LibraryLanguage | null;
    sort: LibrarySort;
};

function visit(changes: Partial<Filters>): void {
    const next: Filters = { ...props.filters, q: q.value.trim(), ...changes };
    const query: Record<string, string> = {};

    if (next.q !== '') {
        query.q = next.q;
    }

    if (next.subject) {
        query.subject = next.subject;
    }

    if (next.language) {
        query.language = next.language;
    }

    if (next.sort !== 'recommended') {
        query.sort = next.sort;
    }

    router.get(index.url(), query, {
        preserveScroll: true,
        preserveState: true,
    });
}

const hasFilters = computed(
    () =>
        props.filters.q !== '' ||
        props.filters.subject !== null ||
        props.filters.language !== null,
);

function reset(): void {
    q.value = '';
    visit({ q: '', subject: null, language: null });
}

const sorts = computed<{ value: LibrarySort; label: string }[]>(() => [
    { value: 'recommended', label: t('Tavsiya etilganlar avval') },
    { value: 'newest', label: t('Yangi nashrlar') },
    { value: 'popular', label: t('Ommabop') },
    { value: 'title', label: t("Nomi bo'yicha (A–Z)") },
]);

const languages = computed<{ value: LibraryLanguage; label: string }[]>(() => [
    { value: 'uz', label: t("O'zbekcha") },
    { value: 'ru', label: t('Ruscha') },
    { value: 'en', label: t('Inglizcha') },
]);

const stats = computed(() => [
    { icon: LibraryBig, value: props.stats.books, label: t('kitob') },
    { icon: FileText, value: props.stats.files, label: t('PDF variant') },
    { icon: Download, value: props.stats.downloads, label: t('yuklab olish') },
]);

const chip =
    'inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-medium transition-all';
</script>

<template>
    <Head :title="t('Kutubxona')" />

    <WebHero
        :image="hero"
        :title="t('Kutubxona')"
        :description="
            t(
                'Ijtimoiy-gumanitar fanlar bo\'yicha monografiyalar, darsliklar va o\'quv qo\'llanmalar. Kitoblarni onlayn o\'qing yoki PDF shaklida yuklab oling.',
            )
        "
        :crumbs="[{ title: t('Kutubxona') }]"
    >
        <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2">
            <li
                v-for="item in stats"
                :key="item.label"
                class="inline-flex items-center gap-2 text-sm text-navy-600"
            >
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-white text-brand-600 ring-1 ring-line"
                >
                    <component :is="item.icon" class="size-4" />
                </span>
                <b class="font-serif text-lg text-navy-950 tabular-nums">{{
                    formatNumber(item.value)
                }}</b>
                {{ item.label }}
            </li>
        </ul>

        <template #search>
            <div class="flex flex-col gap-2 lg:flex-row">
                <form
                    class="flex flex-1 gap-2"
                    @submit.prevent="visit({ q: q.trim() })"
                >
                    <label class="relative flex-1">
                        <span class="sr-only">{{ t('Qidirish') }}</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="q"
                            type="search"
                            :placeholder="
                                t('Kitob nomi yoki muallifni qidiring...')
                            "
                            class="h-12 w-full rounded-xl border border-line bg-white pr-4 pl-12 text-[15px] text-navy-900 outline-none placeholder:text-navy-400 focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                        />
                    </label>
                    <button
                        type="submit"
                        class="h-12 rounded-xl bg-navy-900 px-6 text-sm font-semibold text-white transition-all hover:-translate-y-px hover:bg-brand-700"
                    >
                        {{ t('Qidirish') }}
                    </button>
                </form>
                <div class="grid grid-cols-2 gap-2 lg:w-[26rem]">
                    <SelectInput
                        :model-value="filters.language"
                        :aria-label="t('Til')"
                        class="[&_select]:h-12"
                        @update:model-value="
                            (v) =>
                                visit({
                                    language: (v as LibraryLanguage) || null,
                                })
                        "
                    >
                        <option :value="null">{{ t('Barcha tillar') }}</option>
                        <option
                            v-for="item in languages"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </SelectInput>
                    <SelectInput
                        :model-value="filters.sort"
                        :aria-label="t('Tartib')"
                        class="[&_select]:h-12"
                        @update:model-value="
                            (v) => visit({ sort: v as LibrarySort })
                        "
                    >
                        <option
                            v-for="item in sorts"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </SelectInput>
                </div>
            </div>
        </template>
    </WebHero>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto flex w-full max-w-[1700px] flex-col gap-8 px-4 py-8 sm:px-6 lg:w-[90%] lg:px-0 lg:py-10"
        >
            <!-- Ilmiy yo'nalishlar -->
            <nav
                v-if="subjects.length"
                class="flex flex-wrap gap-2"
                :aria-label="t('Ilmiy yo\'nalishlar')"
            >
                <button
                    type="button"
                    :class="
                        cn(
                            chip,
                            filters.subject === null
                                ? 'bg-navy-900 text-white shadow-md shadow-navy-900/20'
                                : 'bg-white text-navy-700 ring-1 ring-[#e6e1d6] hover:-translate-y-px hover:text-brand-700 hover:ring-brand-200',
                        )
                    "
                    @click="visit({ subject: null })"
                >
                    {{ t("Barcha yo'nalishlar") }}
                </button>
                <button
                    v-for="subject in subjects"
                    :key="subject.value"
                    type="button"
                    :class="
                        cn(
                            chip,
                            filters.subject === subject.value
                                ? 'bg-navy-900 text-white shadow-md shadow-navy-900/20'
                                : 'bg-white text-navy-700 ring-1 ring-[#e6e1d6] hover:-translate-y-px hover:text-brand-700 hover:ring-brand-200',
                        )
                    "
                    @click="visit({ subject: subject.value })"
                >
                    {{ subject.label }}
                </button>
            </nav>

            <!-- Tavsiya etilgan -->
            <section v-if="featured.length">
                <h2
                    class="mb-4 flex items-center gap-2 font-serif text-2xl font-bold text-navy-950"
                >
                    <Star class="size-5 fill-gold-400 text-gold-400" />
                    {{ t('Tavsiya etilgan kitoblar') }}
                </h2>
                <div
                    class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4"
                >
                    <BookCard
                        v-for="book in featured"
                        :key="book.id"
                        :book="book"
                    />
                </div>
            </section>

            <section>
                <div
                    class="mb-4 flex flex-wrap items-end justify-between gap-3"
                >
                    <h2 class="font-serif text-2xl font-bold text-navy-950">
                        {{
                            hasFilters
                                ? t('Qidiruv natijalari')
                                : t('Barcha kitoblar')
                        }}
                        <span
                            class="ml-1 align-middle font-sans text-sm font-medium text-navy-400 tabular-nums"
                            >({{ books.meta.total }})</span
                        >
                    </h2>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-navy-600 transition-colors hover:bg-white hover:text-brand-700"
                        @click="reset"
                    >
                        <X class="size-4" /> {{ t('Filtrlarni tozalash') }}
                    </button>
                </div>

                <div
                    v-if="books.data.length"
                    class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6"
                >
                    <BookCard
                        v-for="book in books.data"
                        :key="book.id"
                        :book="book"
                    />
                </div>

                <div
                    v-else
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-line bg-white py-16 text-center"
                >
                    <LibraryBig class="size-10 text-navy-300" />
                    <p class="font-semibold text-navy-900">
                        {{ t('Kitob topilmadi') }}
                    </p>
                    <p class="text-sm text-navy-500">
                        {{
                            t(
                                "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring.",
                            )
                        }}
                    </p>
                    <Link
                        v-if="hasFilters"
                        :href="index()"
                        class="text-sm font-semibold text-brand-700 hover:text-brand-600"
                        >{{ t('Filtrlarni tozalash') }}</Link
                    >
                </div>

                <div class="mt-10">
                    <Pagination :meta="books.meta" :preserve-scroll="false" />
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Files, Plus, Search, X } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import AuthorArticlesTable from '@/components/cabinet/AuthorArticlesTable.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import { cn } from '@/lib/utils';
import { create, index } from '@/routes/cabinet/articles';
import type { ArticleFilterKey, AuthorArticle, Paginated } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Mening maqolalarim" — holat bo'yicha tablar, nom bo'yicha qidiruv, sahifalash.
 */
const props = defineProps<{
    articles: Paginated<AuthorArticle>;
    filters: { status: ArticleFilterKey | null; search: string | null };
    counts: Record<'all' | ArticleFilterKey, number>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mening maqolalarim', href: index() }],
    },
});

const tabs = computed<{ key: ArticleFilterKey | null; label: string }[]>(() => [
    { key: null, label: t('Barchasi') },
    { key: 'reviewing', label: t("Ko'rib chiqilmoqda") },
    { key: 'revision', label: t('Tuzatish talab qilingan') },
    { key: 'accepted', label: t('Qabul qilingan') },
    { key: 'published', label: t('Nashr etilgan') },
    { key: 'draft', label: t('Qoralamalar') },
    { key: 'closed', label: t('Rad etilgan / qaytarilgan') },
]);

const form = reactive({
    status: props.filters.status,
    search: props.filters.search ?? '',
});

const offset = computed(() => props.articles.meta.from ?? 1);

function apply(): void {
    const query: Record<string, string> = {};

    if (form.status) {
        query.status = form.status;
    }

    if (form.search.trim() !== '') {
        query.search = form.search.trim();
    }

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['articles', 'filters'],
    });
}

let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => form.search,
    () => {
        clearTimeout(timer);
        timer = setTimeout(apply, 350);
    },
);
watch(() => form.status, apply);
</script>

<template>
    <Head :title="t('Mening maqolalarim')" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            :title="t('Mening maqolalarim')"
            :description="
                t(
                    'Yuborgan maqolalaringiz, ularning holati va tahririyat izohlari.',
                )
            "
            :icon="Files"
            :breadcrumbs="[{ title: t('Mening maqolalarim'), href: index() }]"
        />

        <DashCard>
            <div
                class="mb-4 flex flex-col gap-3 2xl:flex-row 2xl:items-center 2xl:justify-between"
            >
                <div
                    class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1"
                    role="tablist"
                    :aria-label="t('Holat bo\'yicha filtr')"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key ?? 'all'"
                        type="button"
                        role="tab"
                        :aria-selected="form.status === tab.key"
                        :class="
                            cn(
                                'flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold whitespace-nowrap transition-all duration-200',
                                form.status === tab.key
                                    ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                                    : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                            )
                        "
                        @click="form.status = tab.key"
                    >
                        {{ tab.label }}
                        <span
                            :class="
                                cn(
                                    'rounded-full px-1.5 py-px text-[10px] tabular-nums',
                                    form.status === tab.key
                                        ? 'bg-white/20 text-white'
                                        : 'bg-navy-50 text-navy-500',
                                )
                            "
                        >
                            {{ counts[tab.key ?? 'all'] }}
                        </span>
                    </button>
                </div>

                <div
                    class="flex items-center gap-2 sm:justify-between 2xl:justify-end"
                >
                    <label class="relative block flex-1 sm:max-w-sm 2xl:w-64">
                        <span class="sr-only">{{
                            t("Maqola nomi bo'yicha qidirish")
                        }}</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="form.search"
                            type="search"
                            :placeholder="t('Maqola nomi bo\'yicha qidirish')"
                            class="h-9 w-full rounded-lg border border-line bg-white pr-8 pl-9 text-[13px] text-navy-900 transition-colors outline-none placeholder:text-navy-400 hover:border-brand-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100"
                        />
                        <button
                            v-if="form.search"
                            type="button"
                            class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-navy-400 hover:text-navy-700"
                            :aria-label="t('Qidiruvni tozalash')"
                            @click="form.search = ''"
                        >
                            <X class="size-3.5" />
                        </button>
                    </label>
                    <Link
                        :href="create()"
                        class="group inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 text-xs font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-0.5 hover:bg-brand-700"
                    >
                        <Plus
                            class="size-4 transition-transform duration-300 group-hover:rotate-90"
                        />
                        <span class="hidden sm:inline">{{
                            t('Yangi maqola')
                        }}</span>
                    </Link>
                </div>
            </div>

            <AuthorArticlesTable :items="articles.data" :offset="offset - 1">
                <template #empty>
                    <p class="text-sm text-navy-600">
                        {{
                            form.search || form.status
                                ? "Filtr bo'yicha maqola topilmadi"
                                : 'Siz hali maqola yubormagansiz'
                        }}
                    </p>
                </template>
            </AuthorArticlesTable>

            <Pagination :meta="articles.meta" class="mt-4" />
        </DashCard>
    </div>
</template>

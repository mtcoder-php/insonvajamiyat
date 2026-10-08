<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    BookCheck,
    CalendarCheck,
    FileStack,
    Hourglass,
    Search,
} from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/production';
import type { ProductionIndexProps, ProductionTab } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * "Nashr jarayoni" — qabul qilingan maqolalarni maketlash va nashrga tayyorlash navbati.
 */
const props = defineProps<ProductionIndexProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Nashr jarayoni'), href: index() },
        ],
    },
});

const tabs: { key: ProductionTab; label: string }[] = [
    { key: 'new', label: t('Maketga olinmagan') },
    { key: 'production', label: t('Maketlanmoqda') },
    { key: 'approved', label: t('Nashrga tayyor') },
    { key: 'published', label: t('Nashr etilgan') },
    { key: 'all', label: t('Barchasi') },
];

const statIcon = {
    new: Hourglass,
    layout: FileStack,
    approved: BadgeCheck,
    published: CalendarCheck,
} as const;

const statTint: Record<string, string> = {
    new: 'bg-amber-50 text-amber-600',
    layout: 'bg-brand-50 text-brand-600',
    approved: 'bg-emerald-50 text-emerald-600',
    published: 'bg-violet-50 text-violet-600',
};

const search = ref(props.filters.search ?? '');

function go(tab: ProductionTab, query?: string): void {
    router.get(
        index.url(),
        { tab, search: query || undefined },
        { preserveScroll: true, preserveState: true },
    );
}

const percent = (done: number, total: number): number =>
    total > 0 ? Math.round((done / total) * 100) : 0;
</script>

<template>
    <Head :title="t('Nashr jarayoni')" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            :title="t('Nashr jarayoni')"
            :description="
                t(
                    'Qabul qilingan maqolalarni maketlash, korrektura, nashr oldidan tekshiruv va tasdiqlash',
                )
            "
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.key"
                class="flex items-center gap-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-18px_rgba(0,36,66,0.35)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl',
                            statTint[stat.key],
                        )
                    "
                >
                    <component
                        :is="statIcon[stat.key as keyof typeof statIcon]"
                        class="size-5"
                    />
                </span>
                <span class="min-w-0">
                    <span
                        class="block text-2xl font-bold text-navy-950 tabular-nums"
                        >{{ stat.value }}</span
                    >
                    <span
                        class="block truncate text-xs font-semibold text-navy-700"
                        >{{ stat.label }}</span
                    >
                    <span class="block truncate text-[11px] text-navy-400">{{
                        stat.hint
                    }}</span>
                </span>
            </div>
        </div>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="flex flex-col gap-3 border-b border-line px-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex gap-1 overflow-x-auto" role="tablist">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="filters.tab === tab.key"
                        :class="
                            cn(
                                '-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-3 text-[13px] font-semibold whitespace-nowrap transition-colors',
                                filters.tab === tab.key
                                    ? 'border-brand-600 text-brand-700'
                                    : 'border-transparent text-navy-500 hover:text-navy-900',
                            )
                        "
                        @click="go(tab.key, search)"
                    >
                        {{ tab.label }}
                        <span
                            :class="
                                cn(
                                    'rounded-full px-1.5 text-[10px] tabular-nums',
                                    tab.key === 'new' && counts.new > 0
                                        ? 'bg-amber-100 text-amber-800'
                                        : 'bg-navy-50 text-navy-500',
                                )
                            "
                            >{{ counts[tab.key] }}</span
                        >
                    </button>
                </div>
                <form
                    class="relative pb-3 lg:w-72 lg:pb-0"
                    @submit.prevent="go(filters.tab, search)"
                >
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400 max-lg:top-[calc(50%-0.375rem)]"
                    />
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="t('Sarlavha yoki DOI...')"
                        :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                    />
                </form>
            </div>

            <ul v-if="articles.data.length" class="divide-y divide-line">
                <li v-for="item in articles.data" :key="item.uuid">
                    <Link
                        :href="item.url"
                        class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-brand-50/40 lg:flex-row lg:items-center"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-11 shrink-0 items-center justify-center rounded-xl text-white transition-transform group-hover:scale-105',
                                    item.approved
                                        ? 'bg-emerald-600'
                                        : 'bg-navy-950',
                                )
                            "
                        >
                            <BookCheck class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="font-mono text-[11px] font-semibold text-navy-400"
                                >#IJ-{{ item.code }}</span
                            >
                            <span
                                class="mt-0.5 line-clamp-2 block text-sm font-semibold text-navy-900 group-hover:text-brand-700"
                            >
                                {{ item.title }}
                            </span>
                            <span class="mt-0.5 block text-xs text-navy-500">
                                {{
                                    [item.author, item.subject]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                        </span>

                        <span
                            class="grid shrink-0 gap-1 text-xs text-navy-600 lg:w-44"
                        >
                            <span>
                                <span class="text-navy-400">{{
                                    t('Son:')
                                }}</span>
                                {{ item.issue ?? '—' }}
                                <template v-if="item.pages"
                                    >·
                                    {{
                                        t(':pages-b.', { pages: item.pages })
                                    }}</template
                                >
                            </span>
                            <span class="truncate">
                                <span class="text-navy-400">DOI:</span>
                                {{ item.doi ?? '—' }}
                            </span>
                        </span>

                        <span class="w-full shrink-0 lg:w-40">
                            <template v-if="item.progress.total">
                                <span
                                    class="mb-1 flex justify-between text-[11px] text-navy-500"
                                >
                                    {{ t('Tekshiruv') }}
                                    <b class="text-navy-800 tabular-nums"
                                        >{{ item.progress.done }}/{{
                                            item.progress.total
                                        }}</b
                                    >
                                </span>
                                <span
                                    class="block h-1.5 overflow-hidden rounded-full bg-navy-100"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'block h-full rounded-full transition-all',
                                                item.progress.done ===
                                                    item.progress.total
                                                    ? 'bg-emerald-500'
                                                    : 'bg-brand-500',
                                            )
                                        "
                                        :style="{
                                            width: `${percent(item.progress.done, item.progress.total)}%`,
                                        }"
                                    />
                                </span>
                            </template>
                            <span v-else class="text-[11px] text-navy-400">
                                {{
                                    t('Qabul: :date', {
                                        date: formatDate(item.acceptedAt),
                                    })
                                }}
                            </span>
                        </span>

                        <span
                            class="flex shrink-0 items-center gap-2 lg:w-44 lg:justify-end"
                        >
                            <span
                                v-if="item.approved"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset"
                            >
                                <BadgeCheck class="size-3.5" />
                                {{ t('Tasdiqlangan') }}
                            </span>
                            <ArticleStatusPill
                                v-else
                                :group="item.statusGroup"
                                :label="item.statusLabel"
                            />
                            <ArrowRight
                                class="hidden size-4 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600 lg:block"
                            />
                        </span>
                    </Link>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center gap-2 py-16 text-center"
            >
                <BookCheck class="size-8 text-navy-300" />
                <p class="text-sm text-navy-500">
                    {{ t("Bu bo'limda maqola yo'q") }}
                </p>
            </div>

            <div
                v-if="articles.meta.last_page > 1"
                class="border-t border-line px-4 py-3"
            >
                <Pagination :meta="articles.meta" />
            </div>
        </section>
    </div>
</template>

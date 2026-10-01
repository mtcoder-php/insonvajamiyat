<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ClipboardPen, Hourglass, Lock } from '@lucide/vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/reviews';
import type { ReviewIndexProps, ReviewListItem, ReviewTab } from '@/types';

/**
 * "Taqrizlarim" — taqrizchiga yuborilgan takliflar va taqrizlar.
 */
const props = defineProps<ReviewIndexProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Taqrizlarim', href: index() },
        ],
    },
});

const tabs: { key: ReviewTab; label: string }[] = [
    { key: 'invited', label: 'Yangi takliflar' },
    { key: 'active', label: 'Jarayonda' },
    { key: 'completed', label: 'Yakunlangan' },
    { key: 'closed', label: 'Rad etilgan / bekor' },
    { key: 'all', label: 'Barchasi' },
];

const statusTint: Record<string, string> = {
    invited: 'bg-amber-50 text-amber-700 ring-amber-200',
    accepted: 'bg-sky-50 text-sky-700 ring-sky-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    declined: 'bg-red-50 text-red-700 ring-red-200',
    cancelled: 'bg-navy-50 text-navy-500 ring-navy-200',
};

function setTab(tab: ReviewTab): void {
    router.get(
        index.url(),
        { tab },
        { preserveScroll: true, preserveState: true },
    );
}

const dueText = (item: ReviewListItem): string =>
    item.daysLeft === null
        ? ''
        : item.daysLeft < 0
          ? `${Math.abs(item.daysLeft)} kun kechikdi`
          : item.daysLeft === 0
            ? 'Bugun tugaydi'
            : `${item.daysLeft} kun qoldi`;
</script>

<template>
    <Head title="Taqrizlarim" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            title="Taqrizlarim"
            description="Sizga yuborilgan taqriz takliflari va taqrizlaringiz"
        />

        <div
            class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-[13px] leading-relaxed text-emerald-900"
        >
            <Lock class="mt-0.5 size-4 shrink-0 text-emerald-600" />
            Blind review: muallifning shaxsiy ma'lumotlari sizga, sizning
            ismingiz esa muallifga ko'rsatilmaydi.
        </div>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="flex gap-1 overflow-x-auto border-b border-line px-4"
                role="tablist"
            >
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
                    @click="setTab(tab.key)"
                >
                    {{ tab.label }}
                    <span
                        :class="
                            cn(
                                'rounded-full px-1.5 text-[10px] tabular-nums',
                                tab.key === 'invited' &&
                                    props.counts.invited > 0
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-navy-50 text-navy-500',
                            )
                        "
                        >{{ counts[tab.key] }}</span
                    >
                </button>
            </div>

            <ul v-if="reviews.data.length" class="divide-y divide-line">
                <li v-for="item in reviews.data" :key="item.id">
                    <Link
                        :href="item.url"
                        class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-brand-50/40 sm:flex-row sm:items-center"
                    >
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy-950 text-white transition-transform group-hover:scale-105"
                        >
                            <ClipboardPen class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="font-mono text-[11px] font-semibold text-navy-400"
                                >#{{ item.code }} · {{ item.round }}-raund</span
                            >
                            <span
                                class="mt-0.5 line-clamp-2 block text-sm font-semibold text-navy-900 group-hover:text-brand-700"
                            >
                                {{ item.title }}
                            </span>
                            <span class="mt-0.5 block text-xs text-navy-500">
                                {{
                                    [item.subject, item.type]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                        </span>
                        <span
                            class="flex shrink-0 flex-wrap items-center gap-3 sm:flex-col sm:items-end sm:gap-1.5"
                        >
                            <span
                                :class="
                                    cn(
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
                                        statusTint[item.status],
                                    )
                                "
                                >{{ item.statusLabel }}</span
                            >
                            <span
                                v-if="item.daysLeft !== null"
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1 text-xs',
                                        item.daysLeft < 0
                                            ? 'font-semibold text-red-600'
                                            : item.daysLeft <= 3
                                              ? 'font-semibold text-amber-600'
                                              : 'text-navy-500',
                                    )
                                "
                            >
                                <Hourglass class="size-3.5" />
                                {{ dueText(item) }}
                            </span>
                            <span
                                v-else-if="item.recommendation"
                                class="text-xs text-navy-500"
                            >
                                {{ item.recommendation }}
                            </span>
                            <span
                                v-else
                                class="text-xs text-navy-400 tabular-nums"
                            >
                                {{ formatDate(item.invitedAt) }}
                            </span>
                        </span>
                        <ArrowRight
                            class="hidden size-4 shrink-0 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600 sm:block"
                        />
                    </Link>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center gap-2 py-16 text-center"
            >
                <ClipboardPen class="size-8 text-navy-300" />
                <p class="text-sm text-navy-500">Bu bo'limda taqriz yo'q</p>
            </div>

            <div
                v-if="reviews.meta.last_page > 1"
                class="border-t border-line px-4 py-3"
            >
                <Pagination :meta="reviews.meta" />
            </div>
        </section>
    </div>
</template>

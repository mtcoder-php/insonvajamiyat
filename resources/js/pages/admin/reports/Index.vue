<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ChartNoAxesCombined,
    ChartPie,
    FileDown,
    UserCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import AiStatsCard from '@/components/admin/reports/AiStatsCard.vue';
import ArticlesReportTable from '@/components/admin/reports/ArticlesReportTable.vue';
import BarChart from '@/components/admin/reports/BarChart.vue';
import DonutChart from '@/components/admin/reports/DonutChart.vue';
import ExportsPanel from '@/components/admin/reports/ExportsPanel.vue';
import HBarList from '@/components/admin/reports/HBarList.vue';
import KpiCards from '@/components/admin/reports/KpiCards.vue';
import LineChart from '@/components/admin/reports/LineChart.vue';
import QuickStatsCard from '@/components/admin/reports/QuickStatsCard.vue';
import ReportActionsCard from '@/components/admin/reports/ReportActionsCard.vue';
import ReportToolbar from '@/components/admin/reports/ReportToolbar.vue';
import ReviewerStatsTable from '@/components/admin/reports/ReviewerStatsTable.vue';
import SystemStatsCard from '@/components/admin/reports/SystemStatsCard.vue';
import TopAuthorsCard from '@/components/admin/reports/TopAuthorsCard.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index as auditIndex } from '@/routes/admin/audit';
import { index } from '@/routes/admin/reports';
import type { ReportTab, ReportTableTab, ReportsPageProps } from '@/types';

/**
 * Admin → Statistika va hisobotlar (super admin analistic page.png).
 * Davr va yo'nalish filtrlari URL'da saqlanadi; jadval sahifalash/qidiruvi
 * partial reload bilan faqat jadvalni yangilaydi.
 */
const props = defineProps<ReportsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Statistika va hisobotlar', href: index() },
        ],
    },
});

const { can } = usePermissions();

const tabs: { key: ReportTab; label: string; icon: Component }[] = [
    { key: 'overview', label: "Umumiy ko'rsatkichlar", icon: ChartPie },
    { key: 'reviewers', label: 'Taqrizchilar', icon: UserCheck },
    { key: 'exports', label: 'Hisobotlar', icon: FileDown },
];

type Query = Record<string, string | number>;

function baseQuery(
    overrides: Partial<{
        from: string;
        to: string;
        subject: string | null;
        tab: ReportTab;
    }> = {},
): Query {
    const merged = {
        from: props.filters.from,
        to: props.filters.to,
        subject: props.filters.subject,
        tab: props.filters.tab,
        ...overrides,
    };
    const query: Query = { from: merged.from, to: merged.to };

    if (merged.subject) {
        query.subject = merged.subject;
    }

    if (merged.tab !== 'overview') {
        query.tab = merged.tab;
    }

    return query;
}

function visit(query: Query, only?: string[]): void {
    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        only,
    });
}

function applyFilters(next: {
    from: string;
    to: string;
    subject: string | null;
}): void {
    visit(baseQuery(next));
}

function setTab(tab: ReportTab): void {
    visit(baseQuery({ tab }));
}

function changeTable(next: {
    status: ReportTableTab;
    q: string | null;
    page: number;
}): void {
    const query = baseQuery();

    if (next.status !== 'all') {
        query.status = next.status;
    }

    if (next.q) {
        query.q = next.q;
    }

    if (next.page > 1) {
        query.page = next.page;
    }

    visit(query, ['articles', 'filters']);
}

const dynamicsSeries = computed(() =>
    props.dynamics
        ? [
              {
                  key: 'submitted',
                  label: 'Yuborilgan',
                  color: '#1a82f7',
                  values: props.dynamics.submitted,
              },
              {
                  key: 'accepted',
                  label: 'Qabul qilingan',
                  color: '#0fa37f',
                  values: props.dynamics.accepted,
              },
              {
                  key: 'rejected',
                  label: 'Rad etilgan',
                  color: '#e5484d',
                  values: props.dynamics.rejected,
              },
          ]
        : [],
);

const revenueSeries = computed(() =>
    props.revenue
        ? [
              {
                  key: 'click',
                  label: 'Click',
                  color: '#1a82f7',
                  values: props.revenue.click,
              },
              {
                  key: 'payme',
                  label: 'Payme',
                  color: '#0fa37f',
                  values: props.revenue.payme,
              },
              {
                  key: 'manual',
                  label: "Bank o'tkazmasi",
                  color: '#8b5cf6',
                  values: props.revenue.manual,
              },
          ]
        : [],
);
</script>

<template>
    <Head title="Statistika va hisobotlar" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            title="Statistika va hisobotlar"
            description="Platforma faoliyati bo'yicha statistik ma'lumotlar va tahlillar"
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <ChartNoAxesCombined class="size-5" />
                </span>
            </template>
            <template #actions>
                <ReportToolbar
                    :filters="filters"
                    :subjects="subjects"
                    :exports="exports"
                    :print-url="printUrl"
                    @apply="applyFilters"
                />
            </template>
        </PageHeader>

        <nav
            class="-mb-1 flex gap-1 overflow-x-auto border-b border-line"
            role="tablist"
            aria-label="Bo'limlar"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="filters.tab === tab.key"
                :class="
                    cn(
                        'group -mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-3 pb-3 text-[13px] font-semibold whitespace-nowrap transition-colors',
                        filters.tab === tab.key
                            ? 'border-brand-600 text-brand-700'
                            : 'border-transparent text-navy-500 hover:border-navy-200 hover:text-navy-900',
                    )
                "
                @click="setTab(tab.key)"
            >
                <component
                    :is="tab.icon"
                    class="size-4 transition-transform group-hover:scale-110"
                />
                {{ tab.label }}
            </button>
        </nav>

        <div class="grid grid-cols-1 gap-5 2xl:grid-cols-[minmax(0,1fr)_21rem]">
            <div class="flex min-w-0 flex-col gap-5">
                <KpiCards :kpis="kpis" :days="filters.days" />

                <template v-if="filters.tab === 'overview'">
                    <div
                        class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
                    >
                        <DashCard v-if="dynamics" title="Maqolalar dinamikasi">
                            <template #actions>
                                <ul
                                    class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-navy-600"
                                >
                                    <li
                                        v-for="s in dynamicsSeries"
                                        :key="s.key"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span
                                            class="size-2.5 rounded-full"
                                            :style="{ background: s.color }"
                                        />
                                        {{ s.label }}
                                    </li>
                                </ul>
                            </template>
                            <LineChart
                                :labels="dynamics.labels"
                                :series="dynamicsSeries"
                                aria-label="Maqolalar dinamikasi"
                            />
                        </DashCard>

                        <DashCard
                            v-if="subjectsChart"
                            title="Maqolalar yo'nalishlari"
                        >
                            <DonutChart
                                :items="subjectsChart.items"
                                :total="subjectsChart.total"
                                center-label="Jami maqola"
                            />
                        </DashCard>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
                    >
                        <DashCard v-if="revenue" title="Daromadlar">
                            <template #actions>
                                <div class="text-right">
                                    <p
                                        class="text-[15px] font-bold text-navy-950 tabular-nums"
                                    >
                                        {{ formatSum(revenue.total) }}
                                    </p>
                                    <p class="text-[11px] text-navy-400">
                                        {{ revenue.count }} ta to'lov
                                        <span
                                            v-if="revenue.trend !== null"
                                            :class="
                                                revenue.trend >= 0
                                                    ? 'text-emerald-600'
                                                    : 'text-red-600'
                                            "
                                        >
                                            · {{ revenue.trend >= 0 ? '+' : ''
                                            }}{{ revenue.trend }}%
                                        </span>
                                    </p>
                                </div>
                            </template>
                            <ul
                                class="mb-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-navy-600"
                            >
                                <li
                                    v-for="s in revenueSeries"
                                    :key="s.key"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="size-2.5 rounded-sm"
                                        :style="{ background: s.color }"
                                    />
                                    {{ s.label }}
                                </li>
                            </ul>
                            <BarChart
                                :labels="revenue.labels"
                                :series="revenueSeries"
                                :format="formatSum"
                                aria-label="Daromadlar dinamikasi"
                            />
                        </DashCard>

                        <DashCard
                            v-if="countries"
                            title="Mamlakatlar bo'yicha mualliflar"
                        >
                            <HBarList
                                :items="countries.items"
                                :total="countries.total"
                            />
                        </DashCard>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
                    >
                        <AiStatsCard v-if="ai" :data="ai" />

                        <DashCard
                            v-if="organizations"
                            title="Tashkilotlar / muassasalar"
                        >
                            <DonutChart
                                :items="organizations.items"
                                :total="organizations.total"
                                center-label="Tashkilot"
                            />
                            <ul
                                v-if="organizations.top.length"
                                class="mt-4 space-y-1 border-t border-line pt-3"
                            >
                                <li
                                    v-for="org in organizations.top"
                                    :key="org.name"
                                    class="flex items-center justify-between gap-3 text-[12px]"
                                >
                                    <span
                                        class="truncate text-navy-600"
                                        :title="org.name"
                                        >{{ org.name }}</span
                                    >
                                    <span
                                        class="shrink-0 font-semibold text-navy-900 tabular-nums"
                                        >{{ org.authors }}</span
                                    >
                                </li>
                            </ul>
                        </DashCard>
                    </div>

                    <ArticlesReportTable
                        v-if="articles"
                        :rows="articles.data"
                        :meta="articles.meta"
                        :status="filters.status"
                        :q="filters.q"
                        @change="changeTable"
                    />
                </template>

                <ReviewerStatsTable
                    v-else-if="filters.tab === 'reviewers' && reviewers"
                    :data="reviewers"
                />

                <ExportsPanel
                    v-else-if="filters.tab === 'exports'"
                    :exports="exports"
                    :print-url="printUrl"
                    :period="filters.label"
                />
            </div>

            <aside
                class="grid grid-cols-1 content-start gap-5 md:grid-cols-2 2xl:grid-cols-1"
                aria-label="Qo'shimcha ma'lumotlar"
            >
                <QuickStatsCard :items="quick" />
                <TopAuthorsCard :items="topAuthors" />
                <ReportActionsCard
                    :exports="exports"
                    :print-url="printUrl"
                    :audit-url="can('audit_log.view') ? auditIndex.url() : null"
                />
                <SystemStatsCard :data="system" />
            </aside>
        </div>
    </div>
</template>

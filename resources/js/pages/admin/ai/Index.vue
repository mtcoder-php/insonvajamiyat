<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import { BrainCircuit, History, Settings2, TriangleAlert } from '@lucide/vue';
import type { Component } from 'vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AiActivityList from '@/components/ai/AiActivityList.vue';
import AiHistoryTable from '@/components/ai/AiHistoryTable.vue';
import AiSettingsPanel from '@/components/ai/AiSettingsPanel.vue';
import AiStatsPanel from '@/components/ai/AiStatsPanel.vue';
import AiTextForm from '@/components/ai/AiTextForm.vue';
import AnalysisResult from '@/components/ai/AnalysisResult.vue';
import EmptyResult from '@/components/ai/EmptyResult.vue';
import ProofreadResult from '@/components/ai/ProofreadResult.vue';
import RequestProgress from '@/components/ai/RequestProgress.vue';
import TokenBalanceCard from '@/components/ai/TokenBalanceCard.vue';
import TranslationResult from '@/components/ai/TranslationResult.vue';
import { studios } from '@/components/ai/aiMeta';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/ai';
import type { AiRequestTypeValue, AiStudioPageProps, AiTab } from '@/types';

/**
 * Admin → AI Studio (super admin ai page.png): Proofreader, Translator, Analytics,
 * tarix va sozlamalar. So'rov navbatda bajariladi — natija tayyor bo'lguncha
 * faqat kerakli qismlar har 2.5 soniyada yangilanadi.
 */
const props = defineProps<AiStudioPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'AI Studio', href: index() },
        ],
    },
});

const serviceTabs: { tab: AiTab; type: AiRequestTypeValue }[] = [
    { tab: 'proofreader', type: 'spell_check' },
    { tab: 'translator', type: 'translation' },
    { tab: 'analytics', type: 'analysis' },
];

const extraTabs = computed<{ tab: AiTab; label: string; icon: Component }[]>(
    () => [
        { tab: 'history', label: 'Tarix', icon: History },
        ...(props.canManage
            ? [
                  {
                      tab: 'settings' as AiTab,
                      label: 'Sozlamalar',
                      icon: Settings2,
                  },
              ]
            : []),
    ],
);

const activeType = computed<AiRequestTypeValue | null>(
    () => serviceTabs.find((s) => s.tab === props.tab)?.type ?? null,
);

/** Tanlangan so'rov shu tabga tegishli bo'lsa ko'rsatiladi */
const current = computed(() =>
    props.current && props.current.type === activeType.value
        ? props.current
        : null,
);

function go(tab: AiTab, extra: Record<string, string | number> = {}): void {
    router.get(
        index.url(),
        { ...(tab !== 'proofreader' ? { tab } : {}), ...extra },
        { preserveScroll: true },
    );
}

function filterHistory(query: {
    type?: AiRequestTypeValue | null;
    scope?: 'own' | 'all';
    page?: number;
}): void {
    const params: Record<string, string | number> = {};
    const type = query.type !== undefined ? query.type : props.filters.type;
    const scope = query.scope ?? props.filters.scope;

    if (props.tab !== 'proofreader') {
        params.tab = props.tab;
    }

    if (props.tab === 'history' && type) {
        params.type = type;
    }

    if (scope === 'all') {
        params.scope = 'all';
    }

    if (props.current) {
        params.request = props.current.uuid;
    }

    if (query.page && query.page > 1) {
        params.hpage = query.page;
    }

    router.get(index.url(), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['history', 'filters'],
    });
}

/* Natija tayyorlanayotganda avtomatik yangilash */
const { start, stop } = usePoll(
    2500,
    { only: ['current', 'budget', 'history', 'activity', 'stats'] },
    { autoStart: false },
);

watch(
    () => (props.current ? props.current.finished : true),
    (finished) => (finished ? stop() : start()),
    { immediate: true },
);

onBeforeUnmount(stop);

/* Xato bo'lgan so'rov matnini formaga qaytarish */
const retry = ref<{
    text: string;
    source: string;
    target: string | null;
} | null>(null);

function retryCurrent(): void {
    if (!props.current) {
        return;
    }

    const [source, target] = props.current.languages.toLowerCase().split(' → ');
    retry.value = { text: props.current.input, source, target: target ?? null };
}
</script>

<template>
    <Head title="AI Studio" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            title="AI Studio"
            description="Ilmiy maqolalar uchun sun'iy intellekt yordamida tahrirlash, tarjima va tahlil qilish."
        >
            <template #before>
                <span class="mb-2 inline-flex items-center gap-2">
                    <span
                        class="inline-flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-md"
                    >
                        <BrainCircuit class="size-5" />
                    </span>
                    <span
                        class="rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-bold text-violet-700"
                        >Beta</span
                    >
                </span>
            </template>
        </PageHeader>

        <p
            v-if="!ready"
            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900"
        >
            <TriangleAlert class="mt-0.5 size-4 shrink-0 text-amber-600" />
            <span>
                AI xizmati hali ishga tushirilmagan: API kaliti yoki model
                kiritilmagan yoki xizmat o'chirilgan.
                <button
                    v-if="canManage"
                    type="button"
                    class="font-semibold text-brand-700 underline underline-offset-2"
                    @click="go('settings')"
                >
                    Sozlamalarni ochish
                </button>
                <template v-else>Administratorga murojaat qiling.</template>
            </span>
        </p>

        <!-- Xizmat kartalari -->
        <div class="grid gap-3 sm:grid-cols-3">
            <button
                v-for="service in serviceTabs"
                :key="service.tab"
                type="button"
                :class="
                    cn(
                        'group flex items-start gap-3 rounded-xl border bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-18px_rgba(0,36,66,0.45)]',
                        tab === service.tab
                            ? 'border-brand-300 ring-2 ring-brand-100'
                            : 'border-line hover:border-brand-200',
                    )
                "
                @click="go(service.tab)"
            >
                <span
                    :class="
                        cn(
                            'flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-md transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3',
                            studios[service.type].gradient,
                        )
                    "
                >
                    <component
                        :is="studios[service.type].icon"
                        class="size-6"
                    />
                </span>
                <span class="min-w-0">
                    <span
                        class="block font-sans text-[15px] font-bold text-navy-950"
                        >{{ studios[service.type].name }}</span
                    >
                    <span
                        class="mt-0.5 block text-xs leading-relaxed text-navy-500"
                        >{{ studios[service.type].description }}</span
                    >
                </span>
            </button>
        </div>

        <!-- Tablar -->
        <nav
            class="flex flex-wrap gap-2 rounded-xl border border-line bg-white p-2 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <button
                v-for="service in serviceTabs"
                :key="service.tab"
                type="button"
                :class="
                    cn(
                        'inline-flex h-9 items-center gap-2 rounded-lg px-4 text-[13px] font-semibold transition-all',
                        tab === service.tab
                            ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                            : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                    )
                "
                @click="go(service.tab)"
            >
                <component :is="studios[service.type].icon" class="size-4" />
                {{ studios[service.type].short }}
            </button>
            <button
                v-for="extra in extraTabs"
                :key="extra.tab"
                type="button"
                :class="
                    cn(
                        'inline-flex h-9 items-center gap-2 rounded-lg px-4 text-[13px] font-semibold transition-all',
                        tab === extra.tab
                            ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                            : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                    )
                "
                @click="go(extra.tab)"
            >
                <component :is="extra.icon" class="size-4" />
                {{ extra.label }}
            </button>
        </nav>

        <div class="grid grid-cols-1 gap-5 2xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid min-w-0 grid-cols-1 content-start gap-5">
                <!-- Xizmat ish maydoni -->
                <section
                    v-if="activeType"
                    class="grid grid-cols-1 gap-5 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] md:p-5 xl:grid-cols-[19rem_minmax(0,1fr)]"
                >
                    <div class="min-w-0">
                        <header class="mb-4 flex items-center gap-3">
                            <span
                                :class="
                                    cn(
                                        'flex size-10 items-center justify-center rounded-xl',
                                        studios[activeType].soft,
                                    )
                                "
                            >
                                <component
                                    :is="studios[activeType].icon"
                                    class="size-5"
                                />
                            </span>
                            <div>
                                <h2
                                    class="font-sans text-[15px] font-bold text-navy-950"
                                >
                                    {{ studios[activeType].name }}
                                </h2>
                                <p class="text-xs text-navy-500">
                                    Matnni joylashtiring
                                </p>
                            </div>
                        </header>
                        <AiTextForm
                            :key="activeType"
                            :type="activeType"
                            :url="urls.store"
                            :languages="languages"
                            :checks="checks"
                            :max-chars="maxChars"
                            :disabled="!ready"
                            :initial="retry"
                        />
                    </div>

                    <div class="min-w-0">
                        <RequestProgress
                            v-if="current && current.status !== 'completed'"
                            :request="current"
                            @retry="retryCurrent"
                        />
                        <template v-else-if="current">
                            <ProofreadResult
                                v-if="current.proofread"
                                :request="current"
                            />
                            <TranslationResult
                                v-else-if="current.type === 'translation'"
                                :request="current"
                            />
                            <AnalysisResult
                                v-else-if="current.analysis"
                                :request="current"
                            />
                        </template>
                        <EmptyResult v-else :type="activeType" :ready="ready" />
                    </div>
                </section>

                <AiSettingsPanel
                    v-if="tab === 'settings' && settings"
                    :settings="settings"
                    :index-url="urls.index"
                />

                <AiHistoryTable
                    v-if="tab !== 'settings'"
                    :title="
                        tab === 'history'
                            ? 'So\'rovlar tarixi'
                            : 'Mening so\'rovlarim'
                    "
                    :items="history.data"
                    :meta="history.meta"
                    :type="filters.type"
                    :show-types="tab === 'history'"
                    :scope="filters.scope"
                    :can-scope="canManage && tab === 'history'"
                    :selected="current?.uuid ?? null"
                    @filter="filterHistory"
                />
            </div>

            <aside
                class="grid min-w-0 grid-cols-1 content-start gap-5 md:grid-cols-2 2xl:grid-cols-1"
            >
                <TokenBalanceCard :budget="budget" />
                <AiStatsPanel :stats="stats" :global="canManage" />
                <AiActivityList
                    :items="activity"
                    :history-url="index.url({ query: { tab: 'history' } })"
                />
            </aside>
        </div>
    </div>
</template>

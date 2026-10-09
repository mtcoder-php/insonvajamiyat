<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileSearch, Route } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import ArticleDetail from '@/components/admin/articles/ArticleDetail.vue';
import AssignReviewersDialog from '@/components/admin/articles/AssignReviewersDialog.vue';
import ArticleQueueList from '@/components/admin/articles/ArticleQueueList.vue';
import EditorialActions from '@/components/admin/articles/EditorialActions.vue';
import EditorialStatCards from '@/components/admin/articles/EditorialStatCards.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import StatusTimeline from '@/components/cabinet/StatusTimeline.vue';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/articles';
import type { EditorialPageProps, EditorialQueue } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Muharrir ish joyi (admin muharir.png): chapda navbat va ro'yxat, o'rtada maqola,
 * o'ngda jarayon va amallar.
 */
const props = defineProps<EditorialPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Maqolalar'), href: index() },
        ],
    },
});

const titles: Record<EditorialQueue, string> = {
    new: t('Yangi maqolalar'),
    reviewing: t("Ko'rib chiqilayotganlar"),
    revision: t('Tuzatish talab qilinganlar'),
    accepted: t('Nashrga tayyorlar'),
    payment: t("To'lov kutilmoqda"),
    published: t('Nashr etilganlar'),
    closed: t('Rad etilgan va qaytarib olinganlar'),
    mine: t('Mening vazifalarim'),
    all: t('Barcha maqolalar'),
};

const form = reactive({
    queue: props.filters.queue,
    search: props.filters.search ?? '',
});

const selectedUuid = computed(() => props.selected?.uuid ?? null);
const loading = ref<string | null>(null);
const inviteOpen = ref(false);

// Maqola kartasining faol tabi; boshqa maqola tanlanganda "Asosiy"ga qaytadi
const detailTab = ref<
    'main' | 'documents' | 'reviewers' | 'messages' | 'process'
>('main');

watch(
    () => props.selected?.uuid,
    () => (detailTab.value = 'main'),
);

function openMessages(): void {
    detailTab.value = 'messages';
    document
        .getElementById('article-detail')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function query(extra: Record<string, string> = {}): Record<string, string> {
    const q: Record<string, string> = { queue: form.queue, ...extra };

    if (form.search.trim()) {
        q.search = form.search.trim();
    }

    return q;
}

function reload(): void {
    router.get(index.url(), query(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['filters', 'articles', 'counts', 'selected'],
    });
}

let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => form.search,
    () => {
        clearTimeout(timer);
        timer = setTimeout(reload, 350);
    },
);
watch(() => form.queue, reload);
watch(
    () => props.filters.queue,
    (queue) => (form.queue = queue),
);

function open(uuid: string): void {
    loading.value = uuid;
    router.get(
        index.url(),
        query({
            article: uuid,
            page: String(props.articles.meta.current_page),
        }),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['selected'],
            onFinish: () => (loading.value = null),
        },
    );
}

function page(url: string): void {
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        only: ['articles', 'selected'],
    });
}
</script>

<template>
    <Head :title="t('Maqolalar — :queue', { queue: titles[filters.queue] })" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            :title="t('Maqolalar — :queue', { queue: titles[filters.queue] })"
            :description="
                t(
                    'Yuborilgan maqolalarni ko\'rib chiqish, mas\'ul muharrir biriktirish va qaror qabul qilish',
                )
            "
        />

        <EditorialStatCards
            :stats="stats"
            :active="form.queue"
            @select="form.queue = $event"
        />

        <div class="grid items-start gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <ArticleQueueList
                v-model:queue="form.queue"
                v-model:search="form.search"
                :items="articles.data"
                :meta="articles.meta"
                :counts="counts"
                :selected="selectedUuid"
                :loading="loading"
                class="xl:sticky xl:top-[calc(var(--app-header-h,4rem)+1rem)] xl:self-start"
                @open="open"
                @page="page"
            />

            <div
                v-if="selected"
                class="grid items-start gap-5 2xl:grid-cols-[minmax(0,1fr)_19rem]"
            >
                <ArticleDetail
                    id="article-detail"
                    :key="selected.uuid"
                    v-model:tab="detailTab"
                    class="scroll-mt-28"
                    :article="selected"
                    @invite="inviteOpen = true"
                />

                <aside
                    class="grid content-start gap-5 md:grid-cols-2 2xl:grid-cols-1"
                >
                    <DashCard>
                        <h2
                            class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                        >
                            <Route class="size-[18px] text-brand-600" />
                            {{ t('Maqola jarayoni') }}
                        </h2>
                        <StatusTimeline :steps="selected.steps" />
                    </DashCard>
                    <EditorialActions
                        :article="selected"
                        :editors="editors"
                        @invite="inviteOpen = true"
                        @message="openMessages"
                    />
                </aside>
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-navy-200 bg-white px-6 py-20 text-center"
            >
                <FileSearch class="size-10 text-navy-300" />
                <p class="text-sm font-semibold text-navy-900">
                    {{ t('Maqola tanlanmagan') }}
                </p>
                <p class="text-xs text-navy-500">
                    {{ t("Chapdagi ro'yxatdan maqolani tanlang.") }}
                </p>
            </div>
        </div>
    </div>

    <AssignReviewersDialog
        v-if="selected"
        v-model:open="inviteOpen"
        :article="selected"
        :reviewers="reviewers"
    />
</template>

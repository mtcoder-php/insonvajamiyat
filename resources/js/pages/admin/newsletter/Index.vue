<script setup lang="ts">
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    Check,
    ChevronDown,
    CircleCheck,
    Clock,
    Download,
    Hourglass,
    Link2,
    LoaderCircle,
    MailCheck,
    MailMinus,
    MailPlus,
    Search,
    Send,
    Sparkles,
    Trash2,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { formatDateTime, formatNumber } from '@/lib/format';
import {
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index, settings } from '@/routes/admin/newsletter';
import { store as campaignStore } from '@/routes/admin/newsletter/campaigns';
import {
    destroy as subscriberDestroy,
    exportMethod as subscriberExport,
} from '@/routes/admin/newsletter/subscribers';
import type {
    NewsletterCampaignItem,
    NewsletterIssueOption,
    NewsletterPageProps,
    NewsletterSubscriberRow,
    NewsletterSubscriberState,
} from '@/types';

/**
 * Admin → Obuna: sayt obunachilari (double opt-in), ularga xat yuborish, yuborilganlar tarixi,
 * "yangi son chop etilganda avtomatik xat" sozlamasi, obunachilar ro'yxati va CSV eksport.
 */
const props = defineProps<NewsletterPageProps>();

const MAX = 10000;

// ——— Statistika
const statCards = computed<
    {
        key: string;
        label: string;
        hint: string;
        value: number;
        icon: Component;
        tint: string;
    }[]
>(() => [
    {
        key: 'confirmed',
        label: t('Faol obunachilar'),
        hint: t('Xat shularga yuboriladi'),
        value: props.stats.confirmed,
        icon: MailCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        key: 'pending',
        label: t('Tasdiq kutilmoqda'),
        hint: t('Pochtadagi havolani bosmagan'),
        value: props.stats.pending,
        icon: Hourglass,
        tint: 'bg-amber-50 text-amber-600',
    },
    {
        key: 'unsubscribed',
        label: t('Obunadan chiqqan'),
        hint: t('Xat yuborilmaydi'),
        value: props.stats.unsubscribed,
        icon: MailMinus,
        tint: 'bg-slate-100 text-slate-500',
    },
    {
        key: 'campaigns',
        label: t('Yuborilgan xatlar'),
        hint: t('Muvaffaqiyatli tarqatilgan'),
        value: props.stats.campaigns,
        icon: Send,
        tint: 'bg-brand-50 text-brand-600',
    },
]);

// ——— Tablar
const tabs = computed(() => [
    {
        key: 'compose' as const,
        label: t('Xat yuborish'),
        count: props.history.length,
    },
    {
        key: 'subscribers' as const,
        label: t('Obunachilar'),
        count:
            props.stats.confirmed +
            props.stats.pending +
            props.stats.unsubscribed,
    },
]);

function openTab(tab: NewsletterPageProps['tab']): void {
    router.get(
        index.url({ query: tab === 'compose' ? {} : { tab } }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

// ——— Xat formasi
const form = useForm({
    audience: 'all' as string,
    subject: '',
    body: '',
    button_label: '',
    button_url: '',
    journal_issue_id: null as number | null,
});

const selectedAudience = computed(() =>
    props.audiences.find((a) => a.value === form.audience),
);
const confirmOpen = ref(false);
const expanded = ref<string | null>(null);

function useIssue(issue: NewsletterIssueOption): void {
    form.subject = issue.template.subject;
    form.body = issue.template.body;
    form.button_label = issue.template.button_label;
    form.button_url = issue.template.button_url;
    form.journal_issue_id = issue.id;
    form.clearErrors();
}

function clearForm(): void {
    form.reset();
    form.clearErrors();
}

function submit(): void {
    form.post(campaignStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            form.reset();
        },
        onError: () => (confirmOpen.value = false),
    });
}

const canSubmit = computed(
    () =>
        !form.processing &&
        form.subject.trim().length >= 3 &&
        form.body.trim().length >= 10 &&
        (selectedAudience.value?.count ?? 0) > 0,
);

// Yuborilayotgan xat bo'lsa — holatini yangilab turamiz
const { start, stop } = usePoll(
    5000,
    { only: ['history', 'stats'] },
    { autoStart: false },
);
const sending = computed(() =>
    props.history.some((c) => c.status === 'queued' || c.status === 'sending'),
);
watch(sending, (active) => (active ? start() : stop()), { immediate: true });

const autoIssueForm = useForm({ auto_issue: props.autoIssue });

function toggleAutoIssue(): void {
    autoIssueForm.auto_issue = !autoIssueForm.auto_issue;
    autoIssueForm.put(settings.url(), { preserveScroll: true });
}

const statusMeta: Record<
    NewsletterCampaignItem['status'],
    { label: string; class: string }
> = {
    queued: { label: t('Navbatda'), class: 'bg-slate-100 text-slate-600' },
    sending: { label: t('Yuborilmoqda'), class: 'bg-amber-50 text-amber-700' },
    sent: { label: t('Yuborildi'), class: 'bg-emerald-50 text-emerald-700' },
    failed: { label: t('Xato'), class: 'bg-red-50 text-red-700' },
};

function audienceLabel(locale: string | null): string {
    return (
        props.audiences.find((a) => a.value === (locale ?? 'all'))?.label ??
        String(locale)
    );
}

// ——— Obunachilar ro'yxati
const search = ref(props.filters.q ?? '');
const status = ref<NewsletterSubscriberState | ''>(props.filters.status ?? '');

function applyFilters(): void {
    router.get(
        index.url({
            query: {
                tab: 'subscribers',
                ...(search.value.trim() ? { q: search.value.trim() } : {}),
                ...(status.value ? { status: status.value } : {}),
            },
        }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

const exportUrl = computed(() =>
    subscriberExport.url({
        query: {
            ...(props.filters.q ? { q: props.filters.q } : {}),
            ...(props.filters.status ? { status: props.filters.status } : {}),
        },
    }),
);

const stateMeta: Record<
    NewsletterSubscriberState,
    { label: string; class: string; icon: Component }
> = {
    confirmed: {
        label: t('Faol'),
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200/60',
        icon: CircleCheck,
    },
    pending: {
        label: t('Tasdiq kutilmoqda'),
        class: 'bg-amber-50 text-amber-700 ring-amber-200/60',
        icon: Hourglass,
    },
    unsubscribed: {
        label: t('Chiqqan'),
        class: 'bg-slate-100 text-slate-500 ring-slate-200',
        icon: MailMinus,
    },
};

const deleting = ref<NewsletterSubscriberRow | null>(null);
const deleteForm = useForm({});

function confirmDelete(): void {
    if (!deleting.value) {
        return;
    }

    deleteForm.delete(subscriberDestroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head :title="t('Obuna')" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            :title="t('Obuna')"
            :description="
                t(
                    'Saytdagi «Yangiliklardan xabardor bo\'ling» formasi orqali obuna bo\'lganlar va ularga yuboriladigan xatlar',
                )
            "
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="stat in statCards"
                :key="stat.key"
                class="flex items-center gap-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-18px_rgba(0,36,66,0.35)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl',
                            stat.tint,
                        )
                    "
                >
                    <component :is="stat.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span
                        class="block text-2xl font-bold text-navy-950 tabular-nums"
                        >{{ formatNumber(stat.value) }}</span
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

        <div class="flex gap-1 border-b border-line" role="tablist">
            <button
                v-for="item in tabs"
                :key="item.key"
                type="button"
                role="tab"
                :aria-selected="tab === item.key"
                :class="
                    cn(
                        '-mb-px flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-[13px] font-semibold whitespace-nowrap transition-colors',
                        tab === item.key
                            ? 'border-brand-600 text-brand-700'
                            : 'border-transparent text-navy-500 hover:text-navy-900',
                    )
                "
                @click="openTab(item.key)"
            >
                {{ item.label }}
                <span
                    class="rounded-full bg-navy-50 px-1.5 text-[10px] text-navy-500 tabular-nums"
                    >{{ formatNumber(item.count) }}</span
                >
            </button>
        </div>

        <!-- ═══════════ Xat yuborish ═══════════ -->
        <div
            v-if="tab === 'compose'"
            class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]"
        >
            <SectionCard
                :title="t('Yangi xat')"
                :description="
                    t(
                        'Faqat obunasini tasdiqlagan manzillarga, har biriga alohida yuboriladi. Har bir xatda obunadan chiqish havolasi bo\'ladi.',
                    )
                "
                :icon="MailPlus"
            >
                <form
                    class="grid grid-cols-1 gap-5"
                    @submit.prevent="confirmOpen = true"
                >
                    <!-- Yangi son haqida — tayyor matn -->
                    <div v-if="issues.length">
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[13px] font-semibold text-navy-800"
                        >
                            <Sparkles class="size-3.5 text-gold-600" />
                            {{ t('Yangi son haqida tayyor matn') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="issue in issues"
                                :key="issue.id"
                                type="button"
                                :class="
                                    cn(
                                        'group inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-all duration-200',
                                        form.journal_issue_id === issue.id
                                            ? 'border-gold-400 bg-gold-400 text-navy-950 shadow-[0_8px_18px_-10px_rgba(196,154,69,0.9)]'
                                            : 'border-line bg-white text-navy-700 hover:-translate-y-px hover:border-gold-300 hover:text-navy-950 hover:shadow-sm',
                                    )
                                "
                                :title="
                                    issue.announced
                                        ? t(
                                              'Bu son haqida xat allaqachon yuborilgan',
                                          )
                                        : undefined
                                "
                                @click="useIssue(issue)"
                            >
                                <BookOpenCheck class="size-3.5" />
                                {{ issue.label }}
                                <Check
                                    v-if="issue.announced"
                                    class="size-3 text-emerald-600"
                                    :stroke-width="3"
                                />
                            </button>
                            <button
                                v-if="form.isDirty"
                                type="button"
                                class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold text-navy-400 transition-colors hover:text-red-600"
                                @click="clearForm"
                            >
                                {{ t('Tozalash') }}
                            </button>
                        </div>
                    </div>

                    <!-- Kimga -->
                    <div>
                        <p class="mb-2 text-[13px] font-semibold text-navy-800">
                            {{ t('Kimga') }} <span class="text-red-500">*</span>
                        </p>
                        <div
                            class="grid grid-cols-2 gap-2 2xl:grid-cols-4"
                            role="radiogroup"
                        >
                            <button
                                v-for="audience in audiences"
                                :key="audience.value"
                                type="button"
                                role="radio"
                                :aria-checked="form.audience === audience.value"
                                :class="
                                    cn(
                                        'group flex items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition-all',
                                        form.audience === audience.value
                                            ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-200'
                                            : 'border-line bg-white hover:-translate-y-px hover:border-brand-200 hover:shadow-sm',
                                    )
                                "
                                @click="form.audience = audience.value"
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                                            form.audience === audience.value
                                                ? 'bg-brand-600 text-white'
                                                : 'bg-[#eef3fa] text-navy-500 group-hover:text-brand-600',
                                        )
                                    "
                                >
                                    <Check
                                        v-if="form.audience === audience.value"
                                        class="size-4"
                                        :stroke-width="3"
                                    />
                                    <Users v-else class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-[13px] font-semibold text-navy-900"
                                        >{{ audience.label }}</span
                                    >
                                    <span
                                        class="block text-xs text-navy-500 tabular-nums"
                                        >{{
                                            t(':count kishi', {
                                                count: formatNumber(
                                                    audience.count,
                                                ),
                                            })
                                        }}</span
                                    >
                                </span>
                            </button>
                        </div>
                        <p
                            v-if="form.errors.audience"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.audience }}
                        </p>
                    </div>

                    <FormField
                        :label="t('Mavzu')"
                        for="nl-subject"
                        required
                        :error="form.errors.subject"
                    >
                        <input
                            id="nl-subject"
                            v-model="form.subject"
                            maxlength="200"
                            :class="inputClass"
                            :placeholder="
                                t(
                                    'Navbatdagi son uchun maqolalar qabuli boshlandi',
                                )
                            "
                        />
                    </FormField>

                    <FormField
                        :label="t('Matn')"
                        for="nl-body"
                        required
                        :error="form.errors.body"
                        :hint="
                            t(
                                ':count / :MAX · xatboshilarni bo\'sh qator bilan ajrating',
                                {
                                    count: formatNumber(form.body.length),
                                    MAX: formatNumber(MAX),
                                },
                            )
                        "
                    >
                        <textarea
                            id="nl-body"
                            v-model="form.body"
                            rows="10"
                            :maxlength="MAX"
                            :class="textareaClass"
                            :placeholder="t('Hurmatli mualliflar! ...')"
                        />
                    </FormField>

                    <div
                        class="grid grid-cols-1 gap-3 rounded-xl border border-dashed border-line bg-[#fafbfd] p-3.5 sm:grid-cols-[12rem_minmax(0,1fr)]"
                    >
                        <FormField
                            :label="t('Tugma matni')"
                            for="nl-btn-label"
                            :error="form.errors.button_label"
                        >
                            <input
                                id="nl-btn-label"
                                v-model="form.button_label"
                                maxlength="60"
                                :class="inputClass"
                                :placeholder="t('Batafsil')"
                            />
                        </FormField>
                        <FormField
                            :label="t('Tugma havolasi')"
                            for="nl-btn-url"
                            :error="form.errors.button_url"
                            :hint="
                                t(
                                    'Ixtiyoriy · xatda ko\'k tugma bo\'lib chiqadi',
                                )
                            "
                        >
                            <div class="relative">
                                <Link2
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                                />
                                <input
                                    id="nl-btn-url"
                                    v-model.trim="form.button_url"
                                    type="url"
                                    maxlength="500"
                                    :class="cn(inputClass, 'pl-9')"
                                    placeholder="https://insonvajamiyat.uz/…"
                                />
                            </div>
                        </FormField>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            :class="primaryButtonClass"
                        >
                            <Send class="size-4" />
                            {{ t('Yuborish') }}
                            <span
                                v-if="selectedAudience"
                                class="rounded-full bg-white/20 px-1.5 text-xs tabular-nums"
                                >{{
                                    formatNumber(selectedAudience.count)
                                }}</span
                            >
                        </button>
                    </div>
                </form>
            </SectionCard>

            <div class="grid gap-5">
                <!-- Avtomatik xat -->
                <button
                    type="button"
                    role="switch"
                    :aria-checked="autoIssueForm.auto_issue"
                    :disabled="autoIssueForm.processing"
                    class="group flex items-start gap-3 rounded-xl border border-line bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-gold-300 hover:shadow-[0_14px_30px_-18px_rgba(0,36,66,0.35)]"
                    @click="toggleAutoIssue"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-gold-300 to-gold-600 text-navy-950 shadow-[0_8px_18px_-10px_rgba(196,154,69,0.9)]"
                    >
                        <BookOpenCheck class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="block text-[13px] font-bold text-navy-900"
                            >{{ t('Yangi son haqida avtomatik xat') }}</span
                        >
                        <span
                            class="mt-0.5 block text-xs leading-relaxed text-navy-500"
                            >{{
                                t(
                                    'Son chop etilganda barcha faol obunachilarga mundarija va havola bilan xat ketadi.',
                                )
                            }}</span
                        >
                    </span>
                    <span
                        :class="
                            cn(
                                'relative mt-1 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-300',
                                autoIssueForm.auto_issue
                                    ? 'bg-emerald-500'
                                    : 'bg-navy-200',
                            )
                        "
                    >
                        <span
                            :class="
                                cn(
                                    'inline-block size-5 rounded-full bg-white shadow transition-transform duration-300',
                                    autoIssueForm.auto_issue
                                        ? 'translate-x-5.5'
                                        : 'translate-x-0.5',
                                )
                            "
                        />
                    </span>
                </button>

                <SectionCard
                    :title="t('Yuborilganlar')"
                    :description="
                        t('So\'nggi :count ta', { count: history.length })
                    "
                    :icon="Clock"
                >
                    <ul
                        v-if="history.length"
                        class="-mx-5 -my-5 divide-y divide-line"
                    >
                        <li v-for="item in history" :key="item.uuid">
                            <button
                                type="button"
                                class="group w-full px-5 py-3 text-left transition-colors hover:bg-brand-50/40"
                                :aria-expanded="expanded === item.uuid"
                                @click="
                                    expanded =
                                        expanded === item.uuid
                                            ? null
                                            : item.uuid
                                "
                            >
                                <span class="flex items-start gap-2">
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block text-[13px] font-semibold [overflow-wrap:anywhere] text-navy-900 transition-colors group-hover:text-brand-700"
                                        >
                                            <BookOpenCheck
                                                v-if="item.kind === 'issue'"
                                                class="mr-1 inline size-3.5 text-gold-600"
                                            />{{ item.subject }}</span
                                        >
                                        <span
                                            class="mt-0.5 block text-xs text-navy-500 tabular-nums"
                                            >{{ audienceLabel(item.locale) }} ·
                                            {{ formatNumber(item.sent) }}/{{
                                                formatNumber(item.recipients)
                                            }}</span
                                        >
                                    </span>
                                    <span
                                        :class="
                                            cn(
                                                'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                statusMeta[item.status].class,
                                            )
                                        "
                                    >
                                        <LoaderCircle
                                            v-if="item.status === 'sending'"
                                            class="size-3 animate-spin"
                                        />
                                        <CircleCheck
                                            v-else-if="item.status === 'sent'"
                                            class="size-3"
                                        />
                                        <TriangleAlert
                                            v-else-if="item.status === 'failed'"
                                            class="size-3"
                                        />
                                        {{ statusMeta[item.status].label }}
                                    </span>
                                    <ChevronDown
                                        :class="
                                            cn(
                                                'mt-0.5 size-4 shrink-0 text-navy-300 transition-transform',
                                                expanded === item.uuid &&
                                                    'rotate-180',
                                            )
                                        "
                                    />
                                </span>
                                <span
                                    class="mt-1 block text-[11px] text-navy-400 tabular-nums"
                                    >{{ item.sender ?? t('Avtomatik') }} ·
                                    {{ formatDateTime(item.createdAt) }}</span
                                >
                            </button>
                            <div
                                v-if="expanded === item.uuid"
                                class="mx-5 mb-3 rounded-lg bg-[#f6f9fd] px-3 py-2.5 text-xs leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line text-navy-700"
                            >
                                {{ item.body }}
                                <a
                                    v-if="item.buttonUrl"
                                    :href="item.buttonUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-2 block text-brand-600 hover:underline"
                                    >{{ item.buttonUrl }}</a
                                >
                                <p v-if="item.error" class="mt-2 text-red-600">
                                    {{ item.error }}
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border border-dashed border-line px-4 py-8 text-center text-sm text-navy-400"
                    >
                        {{ t('Hali obunachilarga xat yuborilmagan') }}
                    </p>
                </SectionCard>
            </div>
        </div>

        <!-- ═══════════ Obunachilar ═══════════ -->
        <section
            v-else
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <form
                class="flex flex-col gap-3 border-b border-line p-4 md:flex-row md:items-center"
                @submit.prevent="applyFilters"
            >
                <div class="relative md:w-80">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="t('Email bo\'yicha qidirish...')"
                        :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                    />
                </div>
                <SelectInput
                    v-model="status"
                    class="md:w-56"
                    :aria-label="t('Holat')"
                    @change="applyFilters"
                >
                    <option value="">{{ t('Barcha holatlar') }}</option>
                    <option value="confirmed">{{ t('Faol') }}</option>
                    <option value="pending">
                        {{ t('Tasdiq kutilmoqda') }}
                    </option>
                    <option value="unsubscribed">{{ t('Chiqqan') }}</option>
                </SelectInput>
                <a
                    :href="exportUrl"
                    :class="cn(secondaryButtonClass, 'h-9 md:ml-auto')"
                >
                    <Download class="size-4" />
                    {{ t('CSV yuklab olish') }}
                </a>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left text-[13px]">
                    <thead
                        class="bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">
                                {{ t('Elektron pochta') }}
                            </th>
                            <th class="px-4 py-3">{{ t('Til') }}</th>
                            <th class="px-4 py-3">{{ t('Holat') }}</th>
                            <th class="px-4 py-3">{{ t('Obuna sanasi') }}</th>
                            <th class="px-4 py-3">{{ t('Tasdiqlangan') }}</th>
                            <th class="w-12 px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="row in subscribers.data"
                            :key="row.id"
                            class="group transition-colors hover:bg-brand-50/30"
                        >
                            <td
                                class="px-4 py-3 font-medium [overflow-wrap:anywhere] text-navy-900"
                            >
                                {{ row.email }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-md bg-navy-50 px-1.5 py-0.5 text-[11px] font-bold text-navy-600 uppercase"
                                    >{{ row.locale }}</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                            stateMeta[row.state].class,
                                        )
                                    "
                                >
                                    <component
                                        :is="stateMeta[row.state].icon"
                                        class="size-3"
                                    />
                                    {{ stateMeta[row.state].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-navy-600 tabular-nums">
                                {{ formatDateTime(row.createdAt) }}
                            </td>
                            <td class="px-4 py-3 text-navy-600 tabular-nums">
                                {{
                                    row.confirmedAt
                                        ? formatDateTime(row.confirmedAt)
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-lg text-navy-400 opacity-60 transition-all group-hover:opacity-100 hover:bg-red-50 hover:text-red-600"
                                    :aria-label="t('O\'chirish')"
                                    :title="t('O\'chirish')"
                                    @click="deleting = row"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!subscribers.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-sm text-navy-400"
                            >
                                {{ t('Obunachilar topilmadi') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line px-4 py-3">
                <Pagination :meta="subscribers.meta" />
            </div>
        </section>

        <ActionDialog
            v-model:open="confirmOpen"
            :title="t('Xatni yuborish')"
            :description="
                t(
                    '«:subject» :count ta obunachiga (:audience) yuboriladi. Yuborilgan xatni qaytarib bo\'lmaydi.',
                    {
                        subject: form.subject,
                        count: formatNumber(selectedAudience?.count ?? 0),
                        audience: selectedAudience?.label ?? '',
                    },
                )
            "
            :icon="Send"
            :confirm-text="t('Ha, yuborish')"
            :processing="form.processing"
            @confirm="submit"
        />

        <ActionDialog
            :open="deleting !== null"
            :title="t('Obunachini o\'chirish')"
            :description="
                t(
                    ':email bazadan butunlay o\'chiriladi. Obunadan chiqqanlarni o\'chirmaslik tavsiya etiladi — aks holda manzil qayta obuna bo\'lishi mumkin.',
                    { email: deleting?.email ?? '' },
                )
            "
            :icon="Trash2"
            tone="danger"
            :confirm-text="t('O\'chirish')"
            :processing="deleteForm.processing"
            @update:open="(open: boolean) => !open && (deleting = null)"
            @confirm="confirmDelete"
        />
    </div>
</template>

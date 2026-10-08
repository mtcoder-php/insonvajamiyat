<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Eye,
    HandCoins,
    Hourglass,
    Search,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import PaymentsChart from '@/components/admin/dashboard/PaymentsChart.vue';
import RecentPaymentsCard from '@/components/admin/dashboard/RecentPaymentsCard.vue';
import ConfirmPaymentDialog from '@/components/admin/payments/ConfirmPaymentDialog.vue';
import PaymentDetailsSheet from '@/components/admin/payments/PaymentDetailsSheet.vue';
import PaymentStatCards from '@/components/admin/payments/PaymentStatCards.vue';
import PaymentStatusPill from '@/components/admin/payments/PaymentStatusPill.vue';
import ProviderBadge from '@/components/admin/payments/ProviderBadge.vue';
import ProviderDonut from '@/components/admin/payments/ProviderDonut.vue';
import WaivePaymentDialog from '@/components/admin/payments/WaivePaymentDialog.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDate, formatDateTime, formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/payments';
import type {
    AdminPaymentsProps,
    AwaitingPaymentItem,
    PaymentListItem,
    PaymentTab,
} from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Admin → To'lovlar (supper admin payments.png): statistika, dinamika,
 * to'lov kutilayotgan maqolalar (qo'lda tasdiqlash / ozod qilish) va to'lovlar ro'yxati.
 */
const props = defineProps<AdminPaymentsProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk("To'lovlar"), href: index() },
        ],
    },
});

const tabs: { key: PaymentTab; label: string }[] = [
    { key: 'awaiting', label: t("To'lov kutilmoqda") },
    { key: 'all', label: t("Barcha to'lovlar") },
    { key: 'click', label: 'Click' },
    { key: 'payme', label: 'Payme' },
    { key: 'manual', label: t("Qo'lda tasdiqlangan") },
    { key: 'failed', label: t('Muvaffaqiyatsiz') },
];

const form = reactive({
    tab: props.filters.tab,
    search: props.filters.search ?? '',
});

function apply(): void {
    const query: Record<string, string> = { tab: form.tab };

    if (form.search.trim()) {
        query.search = form.search.trim();
    }

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['filters', 'awaiting', 'payments', 'counts'],
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
watch(() => form.tab, apply);

// Server tabni o'zi tanlashi mumkin (standart) — sinxronlash
watch(
    () => props.filters.tab,
    (tab) => (form.tab = tab),
);

const meta = computed(() => (props.awaiting ?? props.payments)?.meta ?? null);

// Dialoglar
const selected = ref<AwaitingPaymentItem | null>(null);
const confirmOpen = ref(false);
const waiveOpen = ref(false);

function confirmPayment(article: AwaitingPaymentItem): void {
    selected.value = article;
    confirmOpen.value = true;
}

function waivePayment(article: AwaitingPaymentItem): void {
    selected.value = article;
    waiveOpen.value = true;
}

const detail = ref<PaymentListItem | null>(null);
const detailOpen = ref(false);

function showDetail(payment: PaymentListItem): void {
    detail.value = payment;
    detailOpen.value = true;
}
</script>

<template>
    <Head :title="t('To\'lovlar')" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            :title="t('To\'lovlar')"
            :description="
                t(
                    'Maqolalar uchun to\'lovlar, qo\'lda tasdiqlash va moliyaviy statistika',
                )
            "
        />

        <PaymentStatCards
            :stats="stats"
            :active="form.tab"
            @select="form.tab = $event"
        />

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]">
            <PaymentsChart :data="monthly" />
            <div class="grid content-start gap-5 md:grid-cols-2 xl:grid-cols-1">
                <ProviderDonut :data="breakdown" />
                <RecentPaymentsCard :items="recent" />
            </div>
        </div>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="flex flex-col gap-3 border-b border-line px-4 pt-3 xl:flex-row xl:items-end xl:justify-between"
            >
                <div
                    class="-mb-px flex gap-1 overflow-x-auto"
                    role="tablist"
                    :aria-label="t('To\'lovlar bo\'limlari')"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="form.tab === tab.key"
                        :class="
                            cn(
                                'flex shrink-0 items-center gap-1.5 border-b-2 px-3 pt-1 pb-3 text-[13px] font-semibold whitespace-nowrap transition-colors',
                                form.tab === tab.key
                                    ? 'border-brand-600 text-brand-700'
                                    : 'border-transparent text-navy-500 hover:text-navy-900',
                            )
                        "
                        @click="form.tab = tab.key"
                    >
                        {{ tab.label }}
                        <span
                            :class="
                                cn(
                                    'rounded-full px-1.5 py-px text-[10px] tabular-nums',
                                    tab.key === 'awaiting' &&
                                        counts.awaiting > 0
                                        ? 'bg-amber-100 text-amber-800'
                                        : form.tab === tab.key
                                          ? 'bg-brand-50 text-brand-700'
                                          : 'bg-navy-50 text-navy-500',
                                )
                            "
                        >
                            {{ counts[tab.key] }}
                        </span>
                    </button>
                </div>
                <label class="relative mb-3 block xl:w-80">
                    <span class="sr-only">{{ t('Qidirish') }}</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="form.search"
                        type="search"
                        :placeholder="
                            form.tab === 'awaiting'
                                ? t('Maqola yoki muallif...')
                                : t(
                                      'Chek, hujjat raqami, muallif yoki maqola...',
                                  )
                        "
                        :class="cn(inputClass, 'h-9 pr-8 pl-9 text-[13px]')"
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
            </div>

            <!-- To'lov kutilayotgan maqolalar -->
            <div v-if="awaiting" class="overflow-x-auto">
                <table
                    v-if="awaiting.data.length"
                    class="w-full min-w-[980px] text-left text-[13px]"
                >
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-3 pl-5">{{ t('Maqola') }}</th>
                            <th class="py-3 pr-4">{{ t('Muallif') }}</th>
                            <th class="py-3 pr-4">{{ t('Maqola turi') }}</th>
                            <th class="py-3 pr-4 text-right">
                                {{ t('Summa') }}
                            </th>
                            <th class="py-3 pr-4">{{ t('Yuborilgan') }}</th>
                            <th class="py-3 pr-5 text-right">
                                {{ t('Amallar') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="article in awaiting.data"
                            :key="article.uuid"
                            class="group transition-colors hover:bg-brand-50/40"
                        >
                            <td class="py-3.5 pl-5">
                                <p
                                    class="line-clamp-2 max-w-md font-semibold text-navy-900"
                                >
                                    {{ article.title }}
                                </p>
                                <p
                                    v-if="article.subject"
                                    class="mt-0.5 text-xs text-navy-500"
                                >
                                    {{ article.subject }}
                                </p>
                            </td>
                            <td class="py-3.5 pr-4">
                                <p
                                    class="font-medium whitespace-nowrap text-navy-800"
                                >
                                    {{ article.author.name }}
                                </p>
                                <p class="text-xs text-navy-500">
                                    {{ article.author.email }}
                                </p>
                            </td>
                            <td
                                class="py-3.5 pr-4 whitespace-nowrap text-navy-700"
                            >
                                {{ article.type }}
                            </td>
                            <td
                                class="py-3.5 pr-4 text-right font-semibold whitespace-nowrap text-navy-950 tabular-nums"
                            >
                                {{ formatSum(article.amountDue) }}
                            </td>
                            <td
                                class="py-3.5 pr-4 whitespace-nowrap tabular-nums"
                            >
                                <p class="text-navy-700">
                                    {{ formatDate(article.submittedAt) }}
                                </p>
                                <p
                                    v-if="article.waitingDays !== null"
                                    :class="
                                        cn(
                                            'mt-0.5 inline-flex items-center gap-1 text-xs',
                                            article.waitingDays >= 7
                                                ? 'font-semibold text-red-600'
                                                : 'text-amber-600',
                                        )
                                    "
                                >
                                    <Hourglass class="size-3" />
                                    {{
                                        article.waitingDays === 0
                                            ? 'bugun'
                                            : t(':waitingDays kun kutmoqda', {
                                                  waitingDays:
                                                      article.waitingDays,
                                              })
                                    }}
                                </p>
                            </td>
                            <td class="py-3.5 pr-5">
                                <div
                                    v-if="can.confirm"
                                    class="flex justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white shadow-[0_6px_14px_-8px_rgba(5,150,105,0.9)] transition-all hover:-translate-y-px hover:bg-emerald-500"
                                        @click="confirmPayment(article)"
                                    >
                                        <BadgeCheck class="size-4" />
                                        {{ t('Tasdiqlash') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                                        @click="waivePayment(article)"
                                    >
                                        <HandCoins class="size-4" />
                                        {{ t('Ozod qilish') }}
                                    </button>
                                </div>
                                <span
                                    v-else
                                    class="block text-right text-xs text-navy-400"
                                >
                                    {{ t("Ruxsat yo'q") }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-else
                    class="flex flex-col items-center gap-2 py-14 text-center"
                >
                    <span
                        class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                    >
                        <BadgeCheck class="size-6" />
                    </span>
                    <p class="text-sm font-semibold text-navy-900">
                        {{ t("To'lov kutilayotgan maqola yo'q") }}
                    </p>
                    <p class="text-xs text-navy-500">
                        {{
                            t(
                                "Barcha yuborilgan maqolalarning to'lovi tasdiqlangan.",
                            )
                        }}
                    </p>
                </div>
            </div>

            <!-- To'lovlar -->
            <div v-else-if="payments" class="overflow-x-auto">
                <table
                    v-if="payments.data.length"
                    class="w-full min-w-[1000px] text-left text-[13px]"
                >
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-3 pl-5">{{ t('Chek') }}</th>
                            <th class="py-3 pr-4">
                                {{ t('Maqola / xizmat') }}
                            </th>
                            <th class="py-3 pr-4">{{ t("To'lovchi") }}</th>
                            <th class="py-3 pr-4 text-right">
                                {{ t('Summa') }}
                            </th>
                            <th class="py-3 pr-4">{{ t("To'lov usuli") }}</th>
                            <th class="py-3 pr-4">{{ t('Holat') }}</th>
                            <th class="py-3 pr-4">{{ t('Sana') }}</th>
                            <th class="py-3 pr-5 text-right">
                                {{ t('Amallar') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="payment in payments.data"
                            :key="payment.uuid"
                            class="group cursor-pointer transition-colors hover:bg-brand-50/40"
                            @click="showDetail(payment)"
                        >
                            <td
                                class="py-3.5 pl-5 font-mono text-xs font-semibold text-navy-800"
                            >
                                {{ payment.receipt }}
                            </td>
                            <td class="py-3.5 pr-4">
                                <p
                                    class="line-clamp-1 max-w-xs font-medium text-navy-900 group-hover:text-brand-700"
                                >
                                    {{
                                        payment.article?.title ??
                                        payment.purposeLabel
                                    }}
                                </p>
                                <p
                                    v-if="payment.article"
                                    class="text-xs text-navy-500"
                                >
                                    {{ payment.purposeLabel }}
                                </p>
                            </td>
                            <td
                                class="py-3.5 pr-4 whitespace-nowrap text-navy-700"
                            >
                                {{ payment.user.name }}
                            </td>
                            <td
                                class="py-3.5 pr-4 text-right font-semibold whitespace-nowrap text-navy-950 tabular-nums"
                            >
                                {{ formatSum(payment.amount) }}
                            </td>
                            <td class="py-3.5 pr-4">
                                <ProviderBadge
                                    :provider="payment.provider"
                                    :label="payment.providerLabel"
                                />
                            </td>
                            <td class="py-3.5 pr-4">
                                <PaymentStatusPill
                                    :status="payment.status"
                                    :label="payment.statusLabel"
                                />
                            </td>
                            <td
                                class="py-3.5 pr-4 whitespace-nowrap text-navy-600 tabular-nums"
                            >
                                {{
                                    formatDateTime(
                                        payment.paidAt ?? payment.createdAt,
                                    )
                                }}
                            </td>
                            <td class="py-3.5 pr-5 text-right">
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-colors group-hover:border-brand-200 group-hover:text-brand-600"
                                    :aria-label="
                                        t(':receipt — tafsilotlar', {
                                            receipt: payment.receipt,
                                        })
                                    "
                                    @click.stop="showDetail(payment)"
                                >
                                    <Eye class="size-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-else
                    class="flex flex-col items-center gap-2 py-14 text-center"
                >
                    <span
                        class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-600"
                    >
                        <Wallet class="size-6" />
                    </span>
                    <p class="text-sm text-navy-600">
                        {{
                            form.search
                                ? "Qidiruv bo'yicha to'lov topilmadi"
                                : "Hozircha to'lovlar yo'q"
                        }}
                    </p>
                </div>
            </div>

            <div
                v-if="meta && meta.total > 0"
                class="border-t border-line px-4 py-3"
            >
                <Pagination :meta="meta" />
            </div>
        </section>
    </div>

    <ConfirmPaymentDialog v-model:open="confirmOpen" :article="selected" />
    <WaivePaymentDialog v-model:open="waiveOpen" :article="selected" />
    <PaymentDetailsSheet v-model:open="detailOpen" :payment="detail" />
</template>

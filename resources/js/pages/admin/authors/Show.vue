<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpenCheck,
    CreditCard,
    Download,
    ExternalLink,
    Eye,
    FileText,
    NotebookPen,
    PenTool,
    ShieldCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import PersonHeader from '@/components/admin/people/PersonHeader.vue';
import ProfileFacts from '@/components/admin/people/ProfileFacts.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { formatDate, formatNumber, formatSum } from '@/lib/format';
import { secondaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/authors';
import type { AuthorShowProps } from '@/types';

/**
 * Muallif sahifasi: profil, ko'rsatkichlar, holatlar taqsimoti, maqolalar va to'lovlar.
 */
const props = defineProps<AuthorShowProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Mualliflar', href: index() },
            { title: 'Muallif' },
        ],
    },
});

const groups = [
    { key: 'new', label: 'Yangi', bar: 'bg-brand-500' },
    { key: 'reviewing', label: "Ko'rib chiqilmoqda", bar: 'bg-sky-500' },
    { key: 'revision', label: 'Tuzatishda', bar: 'bg-amber-500' },
    { key: 'accepted', label: 'Qabul qilingan', bar: 'bg-emerald-500' },
    { key: 'published', label: 'Nashr etilgan', bar: 'bg-violet-500' },
    { key: 'closed', label: 'Rad / qaytarilgan', bar: 'bg-red-400' },
] as const;

type GroupKey = (typeof groups)[number]['key'];

const filter = ref<GroupKey | ''>('');

// Uzun ro'yxatlar — avval 10 tasi, "Barchasini ko'rsatish" bilan to'liq
const LIMIT = 10;
const allArticles = ref(false);
const allPayments = ref(false);

const filtered = computed(() =>
    props.articles.filter((a) => {
        if (!filter.value) {
            return true;
        }

        const key =
            a.statusGroup === 'rejected' || a.statusGroup === 'withdrawn'
                ? 'closed'
                : a.statusGroup;

        return key === filter.value;
    }),
);

const visible = computed(() =>
    allArticles.value ? filtered.value : filtered.value.slice(0, LIMIT),
);

const visiblePayments = computed(() =>
    allPayments.value
        ? (props.payments ?? [])
        : (props.payments ?? []).slice(0, LIMIT),
);

const tiles = computed(() => [
    {
        key: 'articles',
        label: 'Maqolalar',
        value: formatNumber(props.stats.articles),
        hint:
            props.stats.coauthored > 0
                ? `${props.stats.submitted} o'zi · ${props.stats.coauthored} hammuallif`
                : props.stats.drafts
                  ? `+${props.stats.drafts} qoralama`
                  : undefined,
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        key: 'published',
        label: 'Nashr etilgan',
        value: formatNumber(props.stats.groups.published),
        hint: props.stats.articles
            ? `${Math.round((props.stats.groups.published / props.stats.articles) * 100)}% maqolalar`
            : undefined,
        icon: BookOpenCheck,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'views',
        label: "Ko'rishlar",
        value: formatNumber(props.stats.views),
        hint: `${formatNumber(props.stats.downloads)} yuklab olish`,
        icon: Eye,
        tint: 'bg-sky-50 text-sky-600',
    },
    ...(props.stats.paid !== null
        ? [
              {
                  key: 'paid',
                  label: "To'langan",
                  value: formatSum(props.stats.paid),
                  hint: `${props.payments?.length ?? 0} ta to'lov`,
                  icon: Wallet,
                  tint: 'bg-emerald-50 text-emerald-600',
              },
          ]
        : []),
]);

const paymentTone: Record<string, string> = {
    paid: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    processing: 'bg-amber-50 text-amber-700',
    refunded: 'bg-violet-50 text-violet-700',
    cancelled: 'bg-navy-50 text-navy-500',
    failed: 'bg-red-50 text-red-700',
};
</script>

<template>
    <Head :title="profile.name" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <Link
            :href="urls.index"
            class="group inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-navy-500 hover:text-brand-700"
        >
            <ArrowLeft
                class="size-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            Mualliflar ro'yxati
        </Link>

        <PersonHeader :profile="profile">
            <template #badges>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-200 ring-inset"
                >
                    <PenTool class="size-3" /> Muallif
                </span>
                <span
                    v-if="urls.reviewer"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200 ring-inset"
                >
                    <NotebookPen class="size-3" /> Taqrizchi ham
                </span>
            </template>
            <template #actions>
                <Link
                    v-if="urls.reviewer"
                    :href="urls.reviewer"
                    :class="secondaryButtonClass"
                >
                    <NotebookPen class="size-4" /> Taqrizchi profili
                </Link>
                <Link
                    v-if="urls.user"
                    :href="urls.user"
                    :class="secondaryButtonClass"
                >
                    <ShieldCheck class="size-4" /> Hisobni boshqarish
                </Link>
            </template>
        </PersonHeader>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="min-w-0">
                <ProfileFacts :profile="profile" :subjects="subjects" />
            </div>

            <div class="flex min-w-0 flex-col gap-5">
                <section
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-4"
                >
                    <MetricTile
                        v-for="tile in tiles"
                        :key="tile.key"
                        :label="tile.label"
                        :value="tile.value"
                        :hint="tile.hint"
                        :icon="tile.icon"
                        :tint="tile.tint"
                    />
                </section>

                <SectionCard
                    title="Maqolalar"
                    description="O'zi yuborgan va hammuallif bo'lgan maqolalar (qoralamalarsiz)"
                    :icon="FileText"
                >
                    <!-- Holatlar taqsimoti -->
                    <div v-if="stats.articles" class="mb-4">
                        <div
                            class="flex h-2.5 overflow-hidden rounded-full bg-[#eef2f8]"
                        >
                            <span
                                v-for="group in groups"
                                v-show="stats.groups[group.key]"
                                :key="group.key"
                                :class="
                                    cn(
                                        'h-full border-r-2 border-white last:border-r-0',
                                        group.bar,
                                    )
                                "
                                :style="{
                                    width: `${(stats.groups[group.key] / stats.articles) * 100}%`,
                                }"
                                :title="`${group.label}: ${stats.groups[group.key]}`"
                            />
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                :class="
                                    cn(
                                        'inline-flex h-7 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold transition-all',
                                        filter === ''
                                            ? 'bg-navy-900 text-white'
                                            : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                                    )
                                "
                                @click="filter = ''"
                            >
                                Hammasi
                                <span class="tabular-nums opacity-70">{{
                                    stats.articles
                                }}</span>
                            </button>
                            <template v-for="group in groups" :key="group.key">
                                <button
                                    v-if="stats.groups[group.key]"
                                    type="button"
                                    :class="
                                        cn(
                                            'inline-flex h-7 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold transition-all',
                                            filter === group.key
                                                ? 'bg-navy-900 text-white'
                                                : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                                        )
                                    "
                                    @click="filter = group.key"
                                >
                                    <span
                                        :class="
                                            cn('size-2 rounded-full', group.bar)
                                        "
                                    />
                                    {{ group.label }}
                                    <span class="tabular-nums opacity-70">{{
                                        stats.groups[group.key]
                                    }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <ul v-if="visible.length" class="-mx-2 grid grid-cols-1">
                        <li v-for="article in visible" :key="article.uuid">
                            <Link
                                :href="article.adminUrl"
                                class="group flex flex-col gap-2 rounded-lg px-2 py-3 transition-colors hover:bg-brand-50/50 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-navy-500"
                                    >
                                        <span
                                            class="font-mono font-semibold text-navy-600"
                                            >{{ article.code }}</span
                                        >
                                        <span v-if="article.subject"
                                            >· {{ article.subject }}</span
                                        >
                                        <span
                                            v-if="!article.isSubmitter"
                                            class="inline-flex items-center gap-0.5 rounded bg-navy-50 px-1 font-semibold text-navy-600"
                                        >
                                            <Users class="size-3" /> hammuallif
                                        </span>
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-2 text-[13px] font-semibold [overflow-wrap:anywhere] text-navy-950 transition-colors group-hover:text-brand-700"
                                    >
                                        {{ article.title }}
                                    </p>
                                </div>
                                <div
                                    class="flex shrink-0 flex-wrap items-center gap-3 text-xs text-navy-500 sm:justify-end"
                                >
                                    <span
                                        v-if="article.publishedAt"
                                        class="inline-flex items-center gap-2 tabular-nums"
                                    >
                                        <span
                                            class="inline-flex items-center gap-0.5"
                                            ><Eye class="size-3.5" />
                                            {{
                                                formatNumber(article.views)
                                            }}</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-0.5"
                                            ><Download class="size-3.5" />
                                            {{
                                                formatNumber(article.downloads)
                                            }}</span
                                        >
                                    </span>
                                    <span class="tabular-nums">{{
                                        formatDate(
                                            article.publishedAt ??
                                                article.submittedAt,
                                        )
                                    }}</span>
                                    <ArticleStatusPill
                                        :group="article.statusGroup"
                                        :label="article.statusLabel"
                                    />
                                    <a
                                        v-if="article.publicUrl"
                                        :href="article.publicUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex size-7 items-center justify-center rounded-md text-navy-400 transition-colors hover:bg-white hover:text-brand-700"
                                        aria-label="Saytda ko'rish"
                                        @click.stop
                                    >
                                        <ExternalLink class="size-3.5" />
                                    </a>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border border-dashed border-line px-4 py-8 text-center text-sm text-navy-400"
                    >
                        Maqolalar yo'q
                    </p>
                    <button
                        v-if="filtered.length > LIMIT"
                        type="button"
                        class="mt-2 w-full rounded-lg border border-line py-2 text-xs font-semibold text-navy-600 transition-colors hover:border-brand-200 hover:bg-brand-50/50 hover:text-brand-700"
                        @click="allArticles = !allArticles"
                    >
                        {{
                            allArticles
                                ? 'Qisqartirish'
                                : `Barchasini ko'rsatish (${filtered.length})`
                        }}
                    </button>
                </SectionCard>

                <SectionCard
                    v-if="canPayments && payments"
                    title="To'lovlar"
                    description="So'nggi 30 ta to'lov"
                    :icon="CreditCard"
                >
                    <div
                        v-if="payments.length"
                        class="-mx-5 -my-5 overflow-x-auto"
                    >
                        <table
                            class="w-full min-w-[640px] text-left text-[13px]"
                        >
                            <thead>
                                <tr
                                    class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                                >
                                    <th class="py-2.5 pl-5">Maqsad</th>
                                    <th class="py-2.5 pr-4">Usul</th>
                                    <th class="py-2.5 pr-4 text-right">
                                        Summa
                                    </th>
                                    <th class="py-2.5 pr-4">Holat</th>
                                    <th class="py-2.5 pr-5">Sana</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr
                                    v-for="payment in visiblePayments"
                                    :key="payment.uuid"
                                    class="transition-colors hover:bg-brand-50/40"
                                >
                                    <td class="max-w-[18rem] py-2.5 pl-5">
                                        <p class="font-semibold text-navy-900">
                                            {{ payment.purpose }}
                                        </p>
                                        <p
                                            v-if="payment.article"
                                            class="truncate text-xs text-navy-500"
                                        >
                                            {{ payment.article }}
                                        </p>
                                    </td>
                                    <td class="py-2.5 pr-4 text-navy-700">
                                        {{ payment.provider }}
                                    </td>
                                    <td
                                        class="py-2.5 pr-4 text-right font-semibold whitespace-nowrap text-navy-950 tabular-nums"
                                    >
                                        {{ formatSum(payment.amount) }}
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        <span
                                            :class="
                                                cn(
                                                    'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                    paymentTone[
                                                        payment.status
                                                    ] ??
                                                        'bg-navy-50 text-navy-600',
                                                )
                                            "
                                            >{{ payment.statusLabel }}</span
                                        >
                                    </td>
                                    <td
                                        class="py-2.5 pr-5 whitespace-nowrap text-navy-600 tabular-nums"
                                    >
                                        {{ formatDate(payment.date) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button
                            v-if="payments.length > LIMIT"
                            type="button"
                            class="w-full border-t border-line py-2.5 text-xs font-semibold text-navy-600 transition-colors hover:bg-brand-50/50 hover:text-brand-700"
                            @click="allPayments = !allPayments"
                        >
                            {{
                                allPayments
                                    ? 'Qisqartirish'
                                    : `Barchasini ko'rsatish (${payments.length})`
                            }}
                        </button>
                    </div>
                    <p
                        v-else
                        class="rounded-lg border border-dashed border-line px-4 py-8 text-center text-sm text-navy-400"
                    >
                        To'lovlar yo'q
                    </p>
                </SectionCard>
            </div>
        </div>
    </div>
</template>

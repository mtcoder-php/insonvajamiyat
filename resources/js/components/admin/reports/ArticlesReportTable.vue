<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportArticleRow, ReportTableTab, SimpleMeta } from '@/types';

/**
 * "So'nggi maqolalar" jadvali: davrda yuborilgan maqolalar, holat tablari,
 * qidiruv (sarlavha, kod, muallif) va sahifalash. Qator — muharrir ish joyida ochiladi.
 */
const props = defineProps<{
    rows: ReportArticleRow[];
    meta: SimpleMeta;
    status: ReportTableTab;
    q: string | null;
}>();

const emit = defineEmits<{
    change: [query: { status: ReportTableTab; q: string | null; page: number }];
}>();

const tabs: { key: ReportTableTab; label: string }[] = [
    { key: 'all', label: 'Barchasi' },
    { key: 'new', label: 'Yangi' },
    { key: 'reviewing', label: "Ko'rib chiqilmoqda" },
    { key: 'accepted', label: 'Qabul qilingan' },
    { key: 'published', label: 'Nashr etilgan' },
    { key: 'rejected', label: 'Rad etilgan' },
];

const pill: Record<string, string> = {
    new: 'bg-brand-50 text-brand-700 ring-brand-200',
    reviewing: 'bg-amber-50 text-amber-700 ring-amber-200',
    revision: 'bg-orange-50 text-orange-700 ring-orange-200',
    accepted: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    published: 'bg-violet-50 text-violet-700 ring-violet-200',
    rejected: 'bg-red-50 text-red-700 ring-red-200',
    other: 'bg-navy-50 text-navy-600 ring-navy-200',
};

const pillFor = (row: ReportArticleRow): string =>
    pill[row.status === 'rejected' ? 'rejected' : row.statusGroup] ??
    pill.other;

const search = ref(props.q ?? '');
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(
        () =>
            emit('change', {
                status: props.status,
                q: value.trim() || null,
                page: 1,
            }),
        350,
    );
});

function setTab(key: ReportTableTab): void {
    emit('change', { status: key, q: search.value.trim() || null, page: 1 });
}

function go(page: number): void {
    emit('change', {
        status: props.status,
        q: search.value.trim() || null,
        page,
    });
}
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <div
            class="-mx-5 -mt-5 flex flex-col gap-3 border-b border-line px-5 pt-4 xl:flex-row xl:items-end xl:justify-between"
        >
            <div
                class="flex min-w-0 flex-col gap-2 xl:flex-row xl:items-end xl:gap-5"
            >
                <h2
                    class="pb-3 text-[15px] font-bold whitespace-nowrap text-navy-950"
                >
                    So'nggi maqolalar
                </h2>
                <div class="-mb-px flex gap-1 overflow-x-auto" role="tablist">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="status === tab.key"
                        :class="
                            cn(
                                'shrink-0 border-b-2 px-2.5 pb-3 text-[13px] font-semibold whitespace-nowrap transition-colors',
                                status === tab.key
                                    ? 'border-brand-600 text-brand-700'
                                    : 'border-transparent text-navy-500 hover:text-navy-900',
                            )
                        "
                        @click="setTab(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
            <label class="relative mb-3 block xl:w-72">
                <span class="sr-only">Qidirish</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Kod, sarlavha yoki muallif..."
                    :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                />
            </label>
        </div>

        <div v-if="rows.length" class="-mx-5 -mb-px overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-[13px]">
                <thead>
                    <tr
                        class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >
                        <th class="w-10 py-2.5 pr-2 pl-5">№</th>
                        <th class="py-2.5 pr-4">Kod</th>
                        <th class="py-2.5 pr-4">Sarlavha</th>
                        <th class="py-2.5 pr-4">Muallif</th>
                        <th class="py-2.5 pr-4">Yo'nalish</th>
                        <th class="py-2.5 pr-4">Holat</th>
                        <th class="py-2.5 pr-4">Yuborilgan</th>
                        <th class="py-2.5 pr-5 text-right">
                            <span class="sr-only">Ochish</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr
                        v-for="(row, i) in rows"
                        :key="row.id"
                        class="group transition-colors hover:bg-brand-50/40"
                    >
                        <td class="py-3 pr-2 pl-5 text-navy-400 tabular-nums">
                            {{ (meta.from ?? 1) + i }}
                        </td>
                        <td
                            class="py-3 pr-4 font-mono text-xs whitespace-nowrap text-navy-600"
                        >
                            #{{ row.code }}
                        </td>
                        <td class="max-w-[22rem] py-3 pr-4">
                            <Link
                                :href="row.url"
                                class="line-clamp-2 font-medium text-navy-900 transition-colors group-hover:text-brand-700"
                            >
                                {{ row.title }}
                            </Link>
                        </td>
                        <td class="py-3 pr-4 whitespace-nowrap text-navy-700">
                            {{ row.author ?? '—' }}
                            <span
                                v-if="row.authorsCount > 1"
                                class="ml-1 rounded bg-navy-50 px-1 text-[10px] text-navy-500"
                                >+{{ row.authorsCount - 1 }}</span
                            >
                        </td>
                        <td class="py-3 pr-4 text-navy-600">
                            {{ row.subject ?? '—' }}
                        </td>
                        <td class="py-3 pr-4">
                            <span
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                                        pillFor(row),
                                    )
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full bg-current"
                                />
                                {{ row.statusLabel }}
                            </span>
                        </td>
                        <td
                            class="py-3 pr-4 whitespace-nowrap text-navy-500 tabular-nums"
                        >
                            {{ formatDateTime(row.submittedAt) }}
                        </td>
                        <td class="py-3 pr-5 text-right">
                            <Link
                                :href="row.url"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-400 transition-all hover:bg-brand-50 hover:text-brand-700"
                                :aria-label="`${row.code} ni ochish`"
                            >
                                <ArrowUpRight class="size-4" />
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="py-10 text-center text-sm text-navy-400">
            Tanlangan shartlar bo'yicha maqola topilmadi
        </p>

        <div class="mt-4">
            <SimplePager :meta="meta" @go="go" />
        </div>
    </section>
</template>

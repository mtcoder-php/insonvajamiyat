<script setup lang="ts">
import {
    AlarmClock,
    CheckCheck,
    Hourglass,
    Send,
    Timer,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReviewerStats, ReviewerStatsRow } from '@/types';

/**
 * "Taqrizchilar" tabi: umumiy ko'rsatkichlar va har bir taqrizchi bo'yicha jadval
 * (saralash ustun sarlavhasini bosib).
 */
const props = defineProps<{ data: ReviewerStats }>();

type SortKey =
    | 'name'
    | 'invited'
    | 'completed'
    | 'declined'
    | 'pending'
    | 'overdue'
    | 'avgDays'
    | 'onTime';

const sort = ref<SortKey>('completed');
const desc = ref(true);

function toggle(key: SortKey): void {
    if (sort.value === key) {
        desc.value = !desc.value;

        return;
    }

    sort.value = key;
    desc.value = key !== 'name' && key !== 'avgDays';
}

const rows = computed(() =>
    [...props.data.rows].sort((a, b) => {
        const va = a[sort.value];
        const vb = b[sort.value];

        if (va === null && vb === null) return 0;
        if (va === null) return 1;
        if (vb === null) return -1;

        const cmp =
            typeof va === 'string' && typeof vb === 'string'
                ? va.localeCompare(vb)
                : Number(va) - Number(vb);

        return desc.value ? -cmp : cmp;
    }),
);

const cards = computed<
    { label: string; value: string; icon: Component; tint: string }[]
>(() => [
    {
        label: 'Taqrizchilar',
        value: formatNumber(props.data.totals.reviewers),
        icon: UsersRound,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        label: 'Takliflar',
        value: formatNumber(props.data.totals.invited),
        icon: Send,
        tint: 'bg-cyan-50 text-cyan-700',
    },
    {
        label: 'Topshirilgan xulosalar',
        value: formatNumber(props.data.totals.completed),
        icon: CheckCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'Jarayonda',
        value: formatNumber(props.data.totals.pending),
        icon: Hourglass,
        tint: 'bg-amber-50 text-amber-600',
    },
    {
        label: "Muddati o'tgan",
        value: formatNumber(props.data.totals.overdue),
        icon: AlarmClock,
        tint: 'bg-red-50 text-red-600',
    },
    {
        label: "O'rtacha muddat",
        value:
            props.data.totals.avgDays === null
                ? '—'
                : `${String(props.data.totals.avgDays).replace('.', ',')} kun`,
        icon: Timer,
        tint: 'bg-violet-50 text-violet-600',
    },
]);

const columns: { key: SortKey; label: string; numeric?: boolean }[] = [
    { key: 'name', label: 'Taqrizchi' },
    { key: 'invited', label: 'Takliflar', numeric: true },
    { key: 'completed', label: 'Topshirgan', numeric: true },
    { key: 'declined', label: 'Rad etgan', numeric: true },
    { key: 'pending', label: 'Jarayonda', numeric: true },
    { key: 'overdue', label: "Muddati o'tgan", numeric: true },
    { key: 'avgDays', label: "O'rt. kun", numeric: true },
    { key: 'onTime', label: 'Muddatida', numeric: true },
];

function initials(row: ReviewerStatsRow): string {
    return row.name
        .split(/\s+/)
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function onTimeClass(value: number | null): string {
    if (value === null) return 'text-navy-400';
    if (value >= 80) return 'bg-emerald-50 text-emerald-700';
    if (value >= 50) return 'bg-amber-50 text-amber-700';

    return 'bg-red-50 text-red-700';
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <section class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6">
            <article
                v-for="card in cards"
                :key="card.label"
                class="group flex items-center gap-3 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-16px_rgba(0,36,66,0.3)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-110',
                            card.tint,
                        )
                    "
                >
                    <component
                        :is="card.icon"
                        class="size-5"
                        :stroke-width="1.8"
                    />
                </span>
                <span class="min-w-0">
                    <span class="block text-[11px] text-navy-500">{{
                        card.label
                    }}</span>
                    <span
                        class="block text-lg font-bold text-navy-950 tabular-nums"
                    >
                        {{ card.value }}
                    </span>
                </span>
            </article>
        </section>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <header
                class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5"
            >
                <h2 class="text-[15px] font-bold text-navy-950">
                    Taqrizchilar samaradorligi
                </h2>
                <p class="text-xs text-navy-400">
                    Takliflar va xulosalar — tanlangan davrda; "Jarayonda" —
                    hozirgi holat
                </p>
            </header>
            <div v-if="rows.length" class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-[13px]">
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                :class="
                                    cn(
                                        'py-2.5 first:pl-5 last:pr-5',
                                        col.numeric
                                            ? 'pr-4 text-right'
                                            : 'pr-4',
                                    )
                                "
                                :aria-sort="
                                    sort === col.key
                                        ? desc
                                            ? 'descending'
                                            : 'ascending'
                                        : undefined
                                "
                            >
                                <button
                                    type="button"
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 uppercase transition-colors hover:text-navy-900',
                                            sort === col.key &&
                                                'text-brand-700',
                                        )
                                    "
                                    @click="toggle(col.key)"
                                >
                                    {{ col.label }}
                                    <span
                                        v-if="sort === col.key"
                                        aria-hidden="true"
                                        >{{ desc ? '↓' : '↑' }}</span
                                    >
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-brand-50/40"
                        >
                            <td class="py-2.5 pr-4 pl-5">
                                <span class="flex items-center gap-2.5">
                                    <img
                                        v-if="row.avatarUrl"
                                        :src="row.avatarUrl"
                                        alt=""
                                        class="size-8 rounded-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-[11px] font-bold text-brand-700"
                                    >
                                        {{ initials(row) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="block font-semibold text-navy-900"
                                            >{{ row.name }}</span
                                        >
                                        <span
                                            class="block text-[11px] text-navy-400"
                                        >
                                            {{ row.degree ?? row.email }}
                                        </span>
                                    </span>
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ row.invited }}
                            </td>
                            <td
                                class="py-2.5 pr-4 text-right font-semibold text-navy-950 tabular-nums"
                            >
                                {{ row.completed }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ row.declined }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ row.pending }}
                            </td>
                            <td
                                :class="
                                    cn(
                                        'py-2.5 pr-4 text-right tabular-nums',
                                        row.overdue > 0 &&
                                            'font-semibold text-red-600',
                                    )
                                "
                            >
                                {{ row.overdue }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums">
                                {{ row.avgDays ?? '—' }}
                            </td>
                            <td class="py-2.5 pr-5 text-right">
                                <span
                                    :class="
                                        cn(
                                            'inline-block min-w-12 rounded-full px-2 py-0.5 text-center text-[11px] font-semibold tabular-nums',
                                            onTimeClass(row.onTime),
                                        )
                                    "
                                >
                                    {{
                                        row.onTime === null
                                            ? '—'
                                            : `${row.onTime}%`
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="py-10 text-center text-sm text-navy-400">
                Taqrizchilar hali qo'shilmagan
            </p>
        </section>
    </div>
</template>

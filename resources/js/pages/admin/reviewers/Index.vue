<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlarmClock,
    ChevronRight,
    CirclePause,
    Gauge,
    NotebookPen,
    Search,
    Timer,
    UserCheck,
    UserRoundPlus,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AddReviewerDialog from '@/components/admin/people/AddReviewerDialog.vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import { usePeopleFilters } from '@/components/admin/people/usePeopleFilters';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { formatNumber } from '@/lib/format';
import { inputClass, primaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/reviewers';
import type {
    ReviewerFilters,
    ReviewerListItem,
    ReviewersPageProps,
} from '@/types';

/**
 * Admin → Taqrizchilar: bazasi, joriy yuklama, tezlik va muddatga rioya.
 */
const props = defineProps<ReviewersPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Taqrizchilar', href: index() },
        ],
    },
});

const defaults: ReviewerFilters = {
    search: '',
    subject: null,
    status: '',
    sort: 'name',
};

const { form, hasFilters, apply, reset } = usePeopleFilters<ReviewerFilters>(
    props.urls.index,
    { ...props.filters },
    defaults,
    ['reviewers', 'filters'],
);

const addOpen = ref(false);

const statuses = [
    { value: '', label: 'Barcha holatlar' },
    { value: 'available', label: "Bo'sh (taklif qilish mumkin)" },
    { value: 'busy', label: 'Band' },
    { value: 'overdue', label: "Muddati o'tgan taqrizi bor" },
    { value: 'paused', label: "To'xtatilgan" },
];

const sorts = [
    { value: 'name', label: "Ism bo'yicha (A–Z)" },
    { value: 'load', label: 'Eng band' },
    { value: 'completed', label: "Ko'p taqriz yozgan" },
    { value: 'latest', label: "Yangi qo'shilganlar" },
];

const tiles = computed(() => [
    {
        key: 'total',
        label: 'Taqrizchilar',
        value: formatNumber(props.counts.total),
        hint: props.counts.paused
            ? `${props.counts.paused} ta to'xtatilgan`
            : 'Barchasi faol',
        icon: NotebookPen,
        tint: 'bg-brand-50 text-brand-600',
        status: '' as const,
    },
    {
        key: 'available',
        label: "Bo'sh",
        value: formatNumber(props.counts.available),
        hint: `${props.busyFrom} tadan kam faol taqriz`,
        icon: UserCheck,
        tint: 'bg-emerald-50 text-emerald-600',
        status: 'available' as const,
    },
    {
        key: 'active',
        label: 'Faol taqrizlar',
        value: formatNumber(props.counts.active),
        hint: props.counts.overdue
            ? `${props.counts.overdue} tasi muddati o'tgan`
            : "Muddati o'tgani yo'q",
        icon: AlarmClock,
        tint: props.counts.overdue
            ? 'bg-red-50 text-red-600'
            : 'bg-amber-50 text-amber-600',
        status: 'overdue' as const,
    },
    {
        key: 'speed',
        label: "O'rtacha muddat",
        value:
            props.counts.avgDays === null ? '—' : `${props.counts.avgDays} kun`,
        hint: 'Taklifdan xulosagacha',
        icon: Timer,
        tint: 'bg-violet-50 text-violet-600',
        status: null,
    },
]);

function load(reviewer: ReviewerListItem): {
    label: string;
    class: string;
} {
    if (reviewer.isPaused) {
        return {
            label: "To'xtatilgan",
            class: 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }

    if (reviewer.overdue) {
        return {
            label: "Muddati o'tgan",
            class: 'bg-red-50 text-red-700 ring-red-200',
        };
    }

    if (reviewer.active >= props.busyFrom) {
        return {
            label: 'Band',
            class: 'bg-amber-50 text-amber-700 ring-amber-200',
        };
    }

    return {
        label: "Bo'sh",
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    };
}

function speedTone(onTime: number | null): string {
    if (onTime === null) {
        return 'text-navy-300';
    }

    return onTime >= 80
        ? 'text-emerald-700'
        : onTime >= 50
          ? 'text-amber-700'
          : 'text-red-600';
}
</script>

<template>
    <Head title="Taqrizchilar" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            title="Taqrizchilar"
            description="Taqrizchilar bazasi: yo'nalishlar, joriy yuklama, tezlik va muddatga rioya"
        >
            <template #actions>
                <button
                    type="button"
                    :class="primaryButtonClass"
                    @click="addOpen = true"
                >
                    <UserRoundPlus class="size-4" />
                    Taqrizchi qo'shish
                </button>
            </template>
        </PageHeader>

        <section
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Statistika"
        >
            <MetricTile
                v-for="tile in tiles"
                :key="tile.key"
                :as="tile.status !== null ? 'button' : 'div'"
                :label="tile.label"
                :value="tile.value"
                :hint="tile.hint"
                :icon="tile.icon"
                :tint="tile.tint"
                :active="tile.status !== null && form.status === tile.status"
                @click="tile.status !== null && (form.status = tile.status)"
            />
        </section>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="grid grid-cols-1 gap-3 border-b border-line p-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_13rem_13rem_12rem_auto]"
            >
                <label class="relative md:col-span-2 xl:col-span-1">
                    <span class="sr-only">Qidirish</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="form.search"
                        type="search"
                        :class="cn(inputClass, 'pl-9')"
                        placeholder="Ism, email yoki tashkilot..."
                    />
                </label>
                <SelectInput v-model="form.subject" aria-label="Yo'nalish">
                    <option :value="null">Barcha yo'nalishlar</option>
                    <option
                        v-for="subject in subjects"
                        :key="subject.value"
                        :value="subject.value"
                    >
                        {{ subject.label }}
                    </option>
                </SelectInput>
                <SelectInput v-model="form.status" aria-label="Holat">
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </SelectInput>
                <SelectInput v-model="form.sort" aria-label="Tartib">
                    <option v-for="s in sorts" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </option>
                </SelectInput>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-navy-600 transition-colors hover:bg-navy-50 hover:text-navy-900"
                    @click="reset"
                >
                    <X class="size-4" /> Tozalash
                </button>
            </div>

            <div
                v-if="reviewers.data.length"
                class="hidden overflow-x-auto md:block"
            >
                <table class="w-full min-w-[960px] text-left text-[13px]">
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-3 pl-5">Taqrizchi</th>
                            <th class="py-3 pr-4">Yo'nalishlar</th>
                            <th class="py-3 pr-4">Yuklama</th>
                            <th class="py-3 pr-4 text-right">Yakunlangan</th>
                            <th class="py-3 pr-4 text-right">O'rtacha</th>
                            <th class="py-3 pr-4 text-right">Muddatida</th>
                            <th class="py-3 pr-4">Holat</th>
                            <th class="w-10 py-3 pr-5" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="reviewer in reviewers.data"
                            :key="reviewer.id"
                            :class="
                                cn(
                                    'group cursor-pointer transition-colors hover:bg-brand-50/40',
                                    reviewer.isPaused && 'bg-[#fbfcfe]',
                                )
                            "
                            @click="router.visit(reviewer.url)"
                        >
                            <td class="py-3 pl-5">
                                <div class="flex items-center gap-3">
                                    <UserAvatar
                                        :name="reviewer.name"
                                        :url="reviewer.avatarUrl"
                                        size="md"
                                        :class="
                                            reviewer.isPaused
                                                ? 'opacity-60'
                                                : ''
                                        "
                                    />
                                    <div class="max-w-[20rem] min-w-0">
                                        <p
                                            class="truncate font-semibold text-navy-950 group-hover:text-brand-700"
                                        >
                                            {{ reviewer.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-navy-500"
                                        >
                                            {{
                                                [
                                                    reviewer.degree,
                                                    reviewer.organization,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') ||
                                                reviewer.email
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 pr-4">
                                <div
                                    v-if="reviewer.subjects.length"
                                    class="flex max-w-[15rem] flex-wrap gap-1"
                                >
                                    <span
                                        v-for="subject in reviewer.subjects.slice(
                                            0,
                                            2,
                                        )"
                                        :key="subject"
                                        class="truncate rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-medium text-brand-700"
                                        >{{ subject }}</span
                                    >
                                    <span
                                        v-if="reviewer.subjects.length > 2"
                                        class="rounded-md bg-navy-50 px-1.5 py-0.5 text-[11px] font-medium text-navy-500"
                                        >+{{
                                            reviewer.subjects.length - 2
                                        }}</span
                                    >
                                </div>
                                <span
                                    v-else
                                    class="text-xs font-medium text-amber-600"
                                    >Belgilanmagan</span
                                >
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-2 w-20 gap-0.5"
                                        :title="`${reviewer.active} ta faol taqriz`"
                                    >
                                        <span
                                            v-for="slot in busyFrom"
                                            :key="slot"
                                            :class="
                                                cn(
                                                    'h-full flex-1 rounded-full transition-colors',
                                                    slot <= reviewer.active
                                                        ? reviewer.active >=
                                                          busyFrom
                                                            ? 'bg-amber-500'
                                                            : 'bg-brand-500'
                                                        : 'bg-[#e6ecf4]',
                                                )
                                            "
                                        />
                                    </div>
                                    <span
                                        class="text-xs font-semibold text-navy-700 tabular-nums"
                                        >{{ reviewer.active }}</span
                                    >
                                    <span
                                        v-if="reviewer.overdue"
                                        class="inline-flex items-center gap-0.5 rounded bg-red-50 px-1 text-[10px] font-semibold text-red-700"
                                    >
                                        <AlarmClock class="size-3" />
                                        {{ reviewer.overdue }}
                                    </span>
                                </div>
                            </td>
                            <td
                                class="py-3 pr-4 text-right font-semibold text-navy-900 tabular-nums"
                            >
                                {{ formatNumber(reviewer.completed) }}
                                <span
                                    v-if="reviewer.declined"
                                    class="ml-1 text-[11px] font-normal text-navy-400"
                                    :title="`${reviewer.declined} ta rad etgan`"
                                    >/ −{{ reviewer.declined }}</span
                                >
                            </td>
                            <td
                                class="py-3 pr-4 text-right whitespace-nowrap text-navy-700 tabular-nums"
                            >
                                {{
                                    reviewer.avgDays === null
                                        ? '—'
                                        : `${reviewer.avgDays} kun`
                                }}
                            </td>
                            <td
                                :class="
                                    cn(
                                        'py-3 pr-4 text-right font-semibold tabular-nums',
                                        speedTone(reviewer.onTime),
                                    )
                                "
                            >
                                {{
                                    reviewer.onTime === null
                                        ? '—'
                                        : `${reviewer.onTime}%`
                                }}
                            </td>
                            <td class="py-3 pr-4">
                                <span
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                                            load(reviewer).class,
                                        )
                                    "
                                >
                                    <CirclePause
                                        v-if="reviewer.isPaused"
                                        class="size-3"
                                    />
                                    {{ load(reviewer).label }}
                                </span>
                            </td>
                            <td class="py-3 pr-5 text-right">
                                <ChevronRight
                                    class="inline size-4 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <ul
                v-if="reviewers.data.length"
                class="divide-y divide-line md:hidden"
            >
                <li v-for="reviewer in reviewers.data" :key="reviewer.id">
                    <Link
                        :href="reviewer.url"
                        class="flex items-start gap-3 p-4 transition-colors hover:bg-brand-50/40"
                    >
                        <UserAvatar
                            :name="reviewer.name"
                            :url="reviewer.avatarUrl"
                            size="md"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-navy-950">
                                {{ reviewer.name }}
                            </p>
                            <p class="truncate text-xs text-navy-500">
                                {{ reviewer.organization ?? reviewer.email }}
                            </p>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-2 text-xs text-navy-600"
                            >
                                <span
                                    :class="
                                        cn(
                                            'rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                                            load(reviewer).class,
                                        )
                                    "
                                    >{{ load(reviewer).label }}</span
                                >
                                <span class="tabular-nums"
                                    >Faol: <b>{{ reviewer.active }}</b> ·
                                    Yakunlangan:
                                    <b>{{ reviewer.completed }}</b></span
                                >
                            </div>
                        </div>
                    </Link>
                </li>
            </ul>

            <div
                v-if="!reviewers.data.length"
                class="flex flex-col items-center px-6 py-16 text-center"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-500"
                >
                    <Gauge class="size-7" />
                </span>
                <p class="mt-4 font-semibold text-navy-900">
                    {{
                        hasFilters
                            ? 'Taqrizchi topilmadi'
                            : "Hali taqrizchilar yo'q"
                    }}
                </p>
                <p class="mt-1 text-sm text-navy-500">
                    {{
                        hasFilters
                            ? "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring."
                            : "Ro'yxatdan o'tgan foydalanuvchini taqrizchi qilib qo'shing."
                    }}
                </p>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="mt-4 text-sm font-semibold text-brand-700 hover:text-brand-600"
                    @click="reset"
                >
                    Filtrlarni tozalash
                </button>
                <button
                    v-else
                    type="button"
                    :class="cn(primaryButtonClass, 'mt-4')"
                    @click="addOpen = true"
                >
                    <UserRoundPlus class="size-4" /> Taqrizchi qo'shish
                </button>
            </div>

            <div
                v-if="reviewers.meta.lastPage > 1"
                class="border-t border-line bg-[#fbfcfe] px-4 py-3"
            >
                <SimplePager :meta="reviewers.meta" @go="apply" />
            </div>
        </section>

        <AddReviewerDialog
            v-model:open="addOpen"
            :store-url="urls.store"
            :candidates-url="urls.candidates"
            :create-user-url="urls.createUser"
            :subjects="subjects"
        />
    </div>
</template>

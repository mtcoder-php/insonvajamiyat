<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    BookCheck,
    BookText,
    ChevronDown,
    ClipboardPen,
    CreditCard,
    Download,
    FileDown,
    FileText,
    History,
    LogIn,
    RotateCcw,
    Search,
    ShieldAlert,
    ShieldCheck,
    Users,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, reactive, ref, watch } from 'vue';
import AuditProperties from '@/components/admin/audit/AuditProperties.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDate, formatNumber, formatTime, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/audit';
import type { AuditLogRow, AuditPageProps, AuditSeverity } from '@/types';

/**
 * Admin → Audit log: kim, qachon, qayerdan (IP), nima qildi.
 * Filtrlar URL'da; qatorni bosib tafsilotlar (o'zgargan maydonlar) ochiladi.
 */
const props = defineProps<AuditPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Audit log', href: index() },
        ],
    },
});

const categoryIcon: Record<string, Component> = {
    auth: LogIn,
    article: FileText,
    review: ClipboardPen,
    production: BookCheck,
    issue: BookText,
    payment: CreditCard,
    user: Users,
    report: FileDown,
};

const severityStyle: Record<AuditSeverity, string> = {
    info: 'bg-brand-50 text-brand-700 ring-brand-200',
    warning: 'bg-amber-50 text-amber-800 ring-amber-200',
    danger: 'bg-red-50 text-red-700 ring-red-200',
};

const severityLabel: Record<AuditSeverity, string> = {
    info: "Ma'lumot",
    warning: 'Muhim',
    danger: 'Xavfli',
};

const form = reactive({
    q: props.filters.q ?? '',
    category: props.filters.category,
    event: props.filters.event,
    severity: props.filters.severity,
    user: props.filters.user,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const eventOptions = computed(() =>
    props.events.filter((e) => !form.category || e.category === form.category),
);

function query(page = 1): Record<string, string | number> {
    const q: Record<string, string | number> = {};

    if (form.q.trim()) q.q = form.q.trim();
    if (form.category) q.category = form.category;
    if (form.event) q.event = form.event;
    if (form.severity) q.severity = form.severity;
    if (form.user) q.user = form.user;
    if (form.from) q.from = form.from;
    if (form.to) q.to = form.to;
    if (page > 1) q.page = page;

    return q;
}

function apply(page = 1): void {
    router.get(index.url(), query(page), {
        preserveState: true,
        preserveScroll: true,
        only: ['logs', 'filters', 'exportUrl'],
    });
}

let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => form.q,
    () => {
        clearTimeout(timer);
        timer = setTimeout(() => apply(), 350);
    },
);

watch(
    () => form.category,
    () => {
        if (
            form.event &&
            !eventOptions.value.some((e) => e.value === form.event)
        ) {
            form.event = null;
        }
    },
);

watch(
    () => [
        form.category,
        form.event,
        form.severity,
        form.user,
        form.from,
        form.to,
    ],
    () => apply(),
);

function reset(): void {
    Object.assign(form, {
        q: '',
        category: null,
        event: null,
        severity: null,
        user: null,
        from: '',
        to: '',
    });
}

const hasFilters = computed(() =>
    Boolean(
        form.q ||
        form.category ||
        form.event ||
        form.severity ||
        form.user ||
        form.from ||
        form.to,
    ),
);

const open = ref<number | null>(null);

function toggle(row: AuditLogRow): void {
    open.value = open.value === row.id ? null : row.id;
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function browser(ua: string | null): string {
    if (!ua) return '—';

    const name = /Edg\//.test(ua)
        ? 'Edge'
        : /OPR\//.test(ua)
          ? 'Opera'
          : /Firefox\//.test(ua)
            ? 'Firefox'
            : /Chrome\//.test(ua)
              ? 'Chrome'
              : /Safari\//.test(ua)
                ? 'Safari'
                : 'Boshqa';
    const os = /Windows/.test(ua)
        ? 'Windows'
        : /Android/.test(ua)
          ? 'Android'
          : /(iPhone|iPad)/.test(ua)
            ? 'iOS'
            : /Mac OS/.test(ua)
              ? 'macOS'
              : /Linux/.test(ua)
                ? 'Linux'
                : '';

    return os ? `${name} · ${os}` : name;
}

const cards = computed(() => [
    {
        label: 'Bugungi amallar',
        value: props.stats.today,
        icon: Activity,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        label: 'Faol foydalanuvchilar (24 soat)',
        value: props.stats.activeUsers,
        icon: UsersRound,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'Muvaffaqiyatsiz kirishlar (24 soat)',
        value: props.stats.failedLogins,
        icon: ShieldAlert,
        tint:
            props.stats.failedLogins > 0
                ? 'bg-red-50 text-red-600'
                : 'bg-navy-50 text-navy-500',
    },
    {
        label: 'Jami yozuvlar',
        value: props.stats.total,
        icon: History,
        tint: 'bg-violet-50 text-violet-600',
    },
]);
</script>

<template>
    <Head title="Audit log" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            title="Audit log"
            :description="`Tizimdagi muhim amallar tarixi: kim, qachon, qayerdan va nima qildi. Yozuvlar ${retentionDays} kun saqlanadi.`"
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <ShieldCheck class="size-5" />
                </span>
            </template>
            <template #actions>
                <a
                    :href="exportUrl"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-700"
                >
                    <Download class="size-4" /> Excel (CSV) eksport
                </a>
            </template>
        </PageHeader>

        <section
            class="grid grid-cols-2 gap-3 xl:grid-cols-4"
            aria-label="Qisqa statistika"
        >
            <article
                v-for="card in cards"
                :key="card.label"
                class="group flex items-center gap-3 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-16px_rgba(0,36,66,0.3)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-110',
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
                    <span
                        class="block text-[11px] leading-tight text-navy-500"
                        >{{ card.label }}</span
                    >
                    <span
                        class="block text-xl font-bold text-navy-950 tabular-nums"
                        >{{ formatNumber(card.value) }}</span
                    >
                </span>
            </article>
        </section>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="grid gap-2 border-b border-line p-4 md:grid-cols-2 xl:grid-cols-4"
            >
                <label class="relative block md:col-span-2">
                    <span class="sr-only">Qidirish</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="form.q"
                        type="search"
                        placeholder="Foydalanuvchi, obyekt, izoh yoki IP..."
                        :class="cn(inputClass, 'h-10 pl-9 text-[13px]')"
                    />
                </label>
                <SelectInput
                    v-model="form.category"
                    aria-label="Bo'lim"
                    class="text-[13px]"
                >
                    <option :value="null">Barcha bo'limlar</option>
                    <option
                        v-for="c in categories"
                        :key="c.value"
                        :value="c.value"
                    >
                        {{ c.label }}
                    </option>
                </SelectInput>
                <SelectInput
                    v-model="form.event"
                    aria-label="Amal"
                    class="text-[13px]"
                >
                    <option :value="null">Barcha amallar</option>
                    <option
                        v-for="e in eventOptions"
                        :key="e.value"
                        :value="e.value"
                    >
                        {{ e.label }}
                    </option>
                </SelectInput>
                <SelectInput
                    v-model="form.severity"
                    aria-label="Ahamiyati"
                    class="text-[13px]"
                >
                    <option :value="null">Har qanday ahamiyat</option>
                    <option
                        v-for="(label, key) in severityLabel"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </SelectInput>
                <SelectInput
                    v-model="form.user"
                    aria-label="Foydalanuvchi"
                    class="text-[13px]"
                >
                    <option :value="null">Barcha foydalanuvchilar</option>
                    <option v-for="u in users" :key="u.value" :value="u.value">
                        {{ u.label }}
                    </option>
                </SelectInput>
                <div class="grid grid-cols-2 gap-2 md:col-span-2 xl:col-span-1">
                    <input
                        v-model="form.from"
                        type="date"
                        aria-label="Sanadan"
                        :class="cn(inputClass, 'h-10 text-[13px]')"
                    />
                    <input
                        v-model="form.to"
                        type="date"
                        aria-label="Sanagacha"
                        :class="cn(inputClass, 'h-10 text-[13px]')"
                    />
                </div>
                <button
                    type="button"
                    :disabled="!hasFilters"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-line px-3 text-[13px] font-semibold text-navy-600 transition-colors hover:border-brand-300 hover:text-brand-700 disabled:opacity-40 disabled:hover:border-line disabled:hover:text-navy-600"
                    @click="reset"
                >
                    <RotateCcw class="size-4" /> Tozalash
                </button>
            </div>

            <div v-if="logs.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-left text-[13px]">
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-2.5 pr-4 pl-5">Vaqt</th>
                            <th class="py-2.5 pr-4">Foydalanuvchi</th>
                            <th class="py-2.5 pr-4">Amal</th>
                            <th class="py-2.5 pr-4">Obyekt</th>
                            <th class="py-2.5 pr-4">IP / brauzer</th>
                            <th class="w-10 py-2.5 pr-5">
                                <span class="sr-only">Tafsilot</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in logs.data" :key="row.id">
                            <tr
                                :class="
                                    cn(
                                        'cursor-pointer border-b border-line transition-colors hover:bg-brand-50/40',
                                        open === row.id && 'bg-brand-50/50',
                                    )
                                "
                                :aria-expanded="open === row.id"
                                @click="toggle(row)"
                            >
                                <td class="py-3 pr-4 pl-5 whitespace-nowrap">
                                    <span
                                        class="block font-medium text-navy-900 tabular-nums"
                                    >
                                        {{ formatDate(row.createdAt) }},
                                        {{ formatTime(row.createdAt) }}
                                    </span>
                                    <span
                                        class="block text-[11px] text-navy-400"
                                        >{{ timeAgo(row.createdAt) }}</span
                                    >
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        v-if="row.user"
                                        class="flex items-center gap-2.5"
                                    >
                                        <img
                                            v-if="row.user.avatarUrl"
                                            :src="row.user.avatarUrl"
                                            alt=""
                                            class="size-8 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy-50 text-[11px] font-bold text-navy-600"
                                        >
                                            {{ initials(row.user.name) }}
                                        </span>
                                        <span class="min-w-0">
                                            <span
                                                :class="
                                                    cn(
                                                        'block truncate font-semibold text-navy-900',
                                                        row.user.deleted &&
                                                            'line-through',
                                                    )
                                                "
                                            >
                                                {{ row.user.name }}
                                            </span>
                                            <span
                                                class="block truncate text-[11px] text-navy-400"
                                            >
                                                {{
                                                    row.user.role ??
                                                    row.user.email
                                                }}
                                            </span>
                                        </span>
                                    </span>
                                    <span
                                        v-else
                                        class="text-[12px] text-navy-400 italic"
                                        >Tizim / mehmon</span
                                    >
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        :class="
                                            cn(
                                                'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                                                severityStyle[row.severity],
                                            )
                                        "
                                    >
                                        <component
                                            :is="
                                                categoryIcon[row.category] ??
                                                Activity
                                            "
                                            class="size-3.5"
                                        />
                                        {{ row.label }}
                                    </span>
                                    <span
                                        v-if="row.description"
                                        class="mt-1 block max-w-xs truncate text-[12px] text-navy-500"
                                        :title="row.description"
                                    >
                                        {{ row.description }}
                                    </span>
                                </td>
                                <td class="max-w-[18rem] py-3 pr-4">
                                    <template v-if="row.subject">
                                        <Link
                                            v-if="row.subject.url"
                                            :href="row.subject.url"
                                            class="line-clamp-2 text-navy-800 transition-colors hover:text-brand-700 hover:underline"
                                            @click.stop
                                        >
                                            {{ row.subject.label }}
                                        </Link>
                                        <span
                                            v-else
                                            class="line-clamp-2 text-navy-700"
                                            >{{ row.subject.label }}</span
                                        >
                                    </template>
                                    <span v-else class="text-navy-300">—</span>
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap">
                                    <span
                                        class="block font-mono text-[12px] text-navy-700"
                                        >{{ row.ip ?? '—' }}</span
                                    >
                                    <span
                                        class="block text-[11px] text-navy-400"
                                        >{{ browser(row.userAgent) }}</span
                                    >
                                </td>
                                <td class="py-3 pr-5 text-right">
                                    <ChevronDown
                                        :class="
                                            cn(
                                                'ml-auto size-4 text-navy-400 transition-transform',
                                                open === row.id &&
                                                    'rotate-180 text-brand-600',
                                            )
                                        "
                                    />
                                </td>
                            </tr>
                            <tr
                                v-if="open === row.id"
                                class="border-b border-line bg-[#fafcff]"
                            >
                                <td colspan="6" class="px-5 py-4">
                                    <div
                                        class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]"
                                    >
                                        <div>
                                            <p
                                                class="mb-2 text-[11px] font-semibold tracking-wide text-navy-400 uppercase"
                                            >
                                                Tafsilotlar
                                            </p>
                                            <AuditProperties
                                                v-if="row.properties"
                                                :properties="row.properties"
                                            />
                                            <p
                                                v-else
                                                class="text-[12px] text-navy-400"
                                            >
                                                Qo'shimcha ma'lumot yo'q
                                            </p>
                                        </div>
                                        <dl
                                            class="grid content-start gap-1 text-[12px]"
                                        >
                                            <dt
                                                class="font-semibold text-navy-400"
                                            >
                                                Hodisa kodi
                                            </dt>
                                            <dd class="font-mono text-navy-700">
                                                {{ row.event }}
                                            </dd>
                                            <dt
                                                class="mt-1 font-semibold text-navy-400"
                                            >
                                                Ahamiyati
                                            </dt>
                                            <dd class="text-navy-700">
                                                {{
                                                    severityLabel[row.severity]
                                                }}
                                            </dd>
                                            <dt
                                                class="mt-1 font-semibold text-navy-400"
                                            >
                                                Brauzer (user agent)
                                            </dt>
                                            <dd class="break-all text-navy-600">
                                                {{ row.userAgent ?? '—' }}
                                            </dd>
                                        </dl>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div
                v-else
                class="flex flex-col items-center gap-2 px-6 py-14 text-center"
            >
                <ShieldCheck class="size-10 text-navy-200" />
                <p class="font-semibold text-navy-700">Yozuvlar topilmadi</p>
                <p class="text-sm text-navy-400">
                    Filtrlarni o'zgartirib ko'ring.
                </p>
            </div>

            <div class="border-t border-line px-5 py-3">
                <SimplePager :meta="logs.meta" @go="apply" />
            </div>
        </section>
    </div>
</template>

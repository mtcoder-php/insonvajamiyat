<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    ChevronRight,
    PenTool,
    Search,
    UserPlus,
    Users,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import { usePeopleFilters } from '@/components/admin/people/usePeopleFilters';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { formatDate, formatNumber, formatSum } from '@/lib/format';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/authors';
import type { AuthorFilters, AuthorsPageProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Admin → Mualliflar: qidiruv (ism, email, tashkilot, ORCID), yo'nalish, tartib.
 */
const props = defineProps<AuthorsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Mualliflar'), href: index() },
        ],
    },
});

const defaults: AuthorFilters = { search: '', subject: null, sort: 'latest' };

const { form, hasFilters, apply, reset } = usePeopleFilters<AuthorFilters>(
    props.urls.index,
    { ...props.filters },
    defaults,
    ['authors', 'filters'],
);

const sorts = [
    { value: 'latest', label: t("Yangi qo'shilganlar") },
    { value: 'name', label: t("Ism bo'yicha (A–Z)") },
    { value: 'articles', label: t("Ko'p maqola yuborgan") },
    { value: 'published', label: t("Ko'p nashr etilgan") },
];

const tiles = computed(() => [
    {
        key: 'total',
        label: t('Jami mualliflar'),
        value: formatNumber(props.counts.total),
        hint: `Bu oy: +${formatNumber(props.counts.newThisMonth)}`,
        icon: Users,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        key: 'active',
        label: t('Maqola yuborgan'),
        value: formatNumber(props.counts.active),
        hint: t('Kamida bitta maqola'),
        icon: PenTool,
        tint: 'bg-sky-50 text-sky-600',
    },
    {
        key: 'published',
        label: t('Nashr etilgan'),
        value: formatNumber(props.counts.published),
        hint: t('Maqolasi chop etilgan'),
        icon: BookOpenCheck,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'orcid',
        label: t("ORCID bog'langan"),
        value: formatNumber(props.counts.orcid),
        hint: props.counts.total
            ? `${Math.round((props.counts.orcid / props.counts.total) * 100)}% mualliflar`
            : undefined,
        icon: UserPlus,
        tint: 'bg-emerald-50 text-emerald-600',
    },
]);

function open(url: string): void {
    router.visit(url);
}
</script>

<template>
    <Head :title="t('Mualliflar')" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            :title="t('Mualliflar')"
            :description="
                t(
                    'Jurnal mualliflari: profil, yo\'nalishlar, maqolalar va to\'lovlar tarixi',
                )
            "
        />

        <section
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4"
            :aria-label="t('Statistika')"
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

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="grid grid-cols-1 gap-3 border-b border-line p-4 md:grid-cols-[minmax(0,1fr)_14rem_14rem_auto]"
            >
                <label class="relative">
                    <span class="sr-only">{{ t('Qidirish') }}</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="form.search"
                        type="search"
                        :class="cn(inputClass, 'pl-9')"
                        :placeholder="t('Ism, email, tashkilot yoki ORCID...')"
                    />
                </label>
                <SelectInput
                    v-model="form.subject"
                    :aria-label="t('Yo\'nalish')"
                >
                    <option :value="null">
                        {{ t("Barcha yo'nalishlar") }}
                    </option>
                    <option
                        v-for="subject in subjects"
                        :key="subject.value"
                        :value="subject.value"
                    >
                        {{ subject.label }}
                    </option>
                </SelectInput>
                <SelectInput v-model="form.sort" :aria-label="t('Tartib')">
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
                    <X class="size-4" /> {{ t('Tozalash') }}
                </button>
            </div>

            <div
                v-if="authors.data.length"
                class="hidden overflow-x-auto md:block"
            >
                <table class="w-full min-w-[920px] text-left text-[13px]">
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-3 pl-5">{{ t('Muallif') }}</th>
                            <th class="py-3 pr-4">{{ t("Yo'nalishlar") }}</th>
                            <th class="py-3 pr-4 text-right">
                                {{ t('Maqolalar') }}
                            </th>
                            <th class="py-3 pr-4 text-right">
                                {{ t('Nashr') }}
                            </th>
                            <th v-if="canPayments" class="py-3 pr-4 text-right">
                                {{ t("To'langan") }}
                            </th>
                            <th class="py-3 pr-4">
                                {{ t("Ro'yxatdan o'tgan") }}
                            </th>
                            <th class="w-10 py-3 pr-5" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="author in authors.data"
                            :key="author.id"
                            class="group cursor-pointer transition-colors hover:bg-brand-50/40"
                            @click="open(author.url)"
                        >
                            <td class="py-3 pl-5">
                                <div class="flex items-center gap-3">
                                    <UserAvatar
                                        :name="author.name"
                                        :url="author.avatarUrl"
                                        size="md"
                                    />
                                    <div class="max-w-[22rem] min-w-0">
                                        <p
                                            class="flex items-center gap-1.5 truncate font-semibold text-navy-950 group-hover:text-brand-700"
                                        >
                                            <span class="truncate">{{
                                                author.name
                                            }}</span>
                                            <span
                                                v-if="author.orcid"
                                                class="shrink-0 rounded bg-[#eef8e4] px-1 text-[10px] font-black text-[#5d8a1f]"
                                                :title="`ORCID ${author.orcid}`"
                                                >iD</span
                                            >
                                            <span
                                                v-if="author.isBlocked"
                                                class="shrink-0 rounded bg-red-50 px-1 text-[10px] font-semibold text-red-700"
                                                >{{ t('bloklangan') }}</span
                                            >
                                        </p>
                                        <p
                                            class="truncate text-xs text-navy-500"
                                        >
                                            {{
                                                [
                                                    author.degree,
                                                    author.organization,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') || author.email
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 pr-4">
                                <div
                                    v-if="author.subjects.length"
                                    class="flex max-w-[16rem] flex-wrap gap-1"
                                >
                                    <span
                                        v-for="subject in author.subjects.slice(
                                            0,
                                            2,
                                        )"
                                        :key="subject"
                                        class="truncate rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-medium text-brand-700"
                                        >{{ subject }}</span
                                    >
                                    <span
                                        v-if="author.subjects.length > 2"
                                        class="rounded-md bg-navy-50 px-1.5 py-0.5 text-[11px] font-medium text-navy-500"
                                        >+{{ author.subjects.length - 2 }}</span
                                    >
                                </div>
                                <span v-else class="text-navy-300">—</span>
                            </td>
                            <td
                                class="py-3 pr-4 text-right font-semibold text-navy-900 tabular-nums"
                            >
                                {{ formatNumber(author.articles) }}
                            </td>
                            <td class="py-3 pr-4 text-right tabular-nums">
                                <span
                                    :class="
                                        cn(
                                            'inline-flex min-w-7 justify-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                            author.published
                                                ? 'bg-violet-50 text-violet-700'
                                                : 'text-navy-300',
                                        )
                                    "
                                    >{{ formatNumber(author.published) }}</span
                                >
                            </td>
                            <td
                                v-if="canPayments"
                                class="py-3 pr-4 text-right whitespace-nowrap text-navy-700 tabular-nums"
                            >
                                {{ author.paid ? formatSum(author.paid) : '—' }}
                            </td>
                            <td
                                class="py-3 pr-4 whitespace-nowrap text-navy-600 tabular-nums"
                            >
                                {{ formatDate(author.createdAt) }}
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
                v-if="authors.data.length"
                class="divide-y divide-line md:hidden"
            >
                <li v-for="author in authors.data" :key="author.id">
                    <Link
                        :href="author.url"
                        class="flex items-start gap-3 p-4 transition-colors hover:bg-brand-50/40"
                    >
                        <UserAvatar
                            :name="author.name"
                            :url="author.avatarUrl"
                            size="md"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-navy-950">
                                {{ author.name }}
                            </p>
                            <p class="truncate text-xs text-navy-500">
                                {{ author.organization ?? author.email }}
                            </p>
                            <p
                                class="mt-1.5 text-xs text-navy-600 tabular-nums"
                            >
                                {{ t('Maqola:') }} <b>{{ author.articles }}</b>
                                {{ t('· Nashr:') }}
                                <b>{{ author.published }}</b>
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>

            <div
                v-if="!authors.data.length"
                class="flex flex-col items-center px-6 py-16 text-center"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-500"
                >
                    <Users class="size-7" />
                </span>
                <p class="mt-4 font-semibold text-navy-900">
                    {{ t('Muallif topilmadi') }}
                </p>
                <p class="mt-1 text-sm text-navy-500">
                    {{
                        t(
                            "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring.",
                        )
                    }}
                </p>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="mt-4 text-sm font-semibold text-brand-700 hover:text-brand-600"
                    @click="reset"
                >
                    {{ t('Filtrlarni tozalash') }}
                </button>
            </div>

            <div
                v-if="authors.meta.lastPage > 1"
                class="border-t border-line bg-[#fbfcfe] px-4 py-3"
            >
                <SimplePager :meta="authors.meta" @go="apply" />
            </div>
        </section>
    </div>
</template>

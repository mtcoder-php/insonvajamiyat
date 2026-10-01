<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookCopy,
    BookDashed,
    BookOpenCheck,
    BookPlus,
    FilePlus2,
    FileText,
} from '@lucide/vue';
import { ref } from 'vue';
import IssueFormDialog from '@/components/admin/issues/IssueFormDialog.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { primaryButtonClass } from '@/lib/formStyles';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/issues';
import type { IssueIndexProps, IssueStatusKey } from '@/types';

/**
 * "Jurnallar" — jurnal sonlari ro'yxati va yangi son yaratish.
 */
const props = defineProps<IssueIndexProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Jurnallar', href: index() },
        ],
    },
});

const createOpen = ref(false);
const year = ref<number | null>(props.filters.year);
const status = ref<IssueStatusKey | null>(props.filters.status);

function applyFilters(): void {
    router.get(
        index.url(),
        { year: year.value ?? undefined, status: status.value ?? undefined },
        { preserveScroll: true, preserveState: true },
    );
}

const statIcon = {
    total: BookCopy,
    draft: BookDashed,
    published: BookOpenCheck,
    waiting: FilePlus2,
} as const;

const statTint: Record<string, string> = {
    total: 'bg-brand-50 text-brand-600',
    draft: 'bg-amber-50 text-amber-600',
    published: 'bg-emerald-50 text-emerald-600',
    waiting: 'bg-violet-50 text-violet-600',
};

const readyPercent = (ready: number, total: number): number =>
    total > 0 ? Math.round((ready / total) * 100) : 0;
</script>

<template>
    <Head title="Jurnallar" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            title="Jurnal sonlari"
            description="Sonlarni shakllantirish: maqolalarni joylashtirish, sahifalar, muqova, mundarija va to'liq PDF"
        >
            <template #actions>
                <button
                    type="button"
                    :class="primaryButtonClass"
                    @click="createOpen = true"
                >
                    <BookPlus class="size-4" /> Yangi son
                </button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.key"
                class="flex items-center gap-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-18px_rgba(0,36,66,0.35)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl',
                            statTint[stat.key],
                        )
                    "
                >
                    <component
                        :is="statIcon[stat.key as keyof typeof statIcon]"
                        class="size-5"
                    />
                </span>
                <span class="min-w-0">
                    <span
                        class="block text-2xl font-bold text-navy-950 tabular-nums"
                        >{{ stat.value }}</span
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

        <div class="flex flex-wrap items-center gap-3">
            <SelectInput
                v-model="year"
                class="w-36"
                aria-label="Yil"
                @change="applyFilters"
            >
                <option :value="null">Barcha yillar</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </SelectInput>
            <SelectInput
                v-model="status"
                class="w-48"
                aria-label="Holat"
                @change="applyFilters"
            >
                <option :value="null">Barcha holatlar</option>
                <option value="draft">Qoralama</option>
                <option value="published">Chop etilgan</option>
            </SelectInput>
            <span class="text-xs text-navy-500"
                >{{ issues.length }} ta son</span
            >
        </div>

        <div
            v-if="issues.length"
            class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3"
        >
            <Link
                v-for="issue in issues"
                :key="issue.slug"
                :href="issue.url"
                class="group flex gap-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_20px_40px_-24px_rgba(0,36,66,0.45)]"
            >
                <span
                    class="relative h-36 w-26 shrink-0 overflow-hidden rounded-md bg-gradient-to-b from-navy-900 to-brand-800 shadow-[0_12px_26px_-14px_rgba(0,36,66,0.9)] transition-transform duration-300 group-hover:scale-[1.03]"
                >
                    <img
                        v-if="issue.coverUrl"
                        :src="issue.coverUrl"
                        :alt="issue.label"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <span
                        v-else
                        class="flex size-full flex-col items-center justify-center text-white"
                    >
                        <span class="text-[9px] font-semibold opacity-70"
                            >INSON VA JAMIYAT</span
                        >
                        <span class="mt-1 text-xl font-bold"
                            >№{{ issue.number }}</span
                        >
                        <span class="text-[11px] opacity-80">{{
                            issue.year
                        }}</span>
                    </span>
                </span>
                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="flex items-start justify-between gap-2">
                        <span
                            class="font-serif text-lg font-bold whitespace-nowrap text-navy-950 group-hover:text-brand-700"
                            >{{ issue.label }}</span
                        >
                        <span
                            :class="
                                cn(
                                    'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset',
                                    issue.status === 'published'
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                        : 'bg-amber-50 text-amber-700 ring-amber-200',
                                )
                            "
                            >{{ issue.statusLabel }}</span
                        >
                    </span>
                    <span
                        v-if="issue.title"
                        class="mt-0.5 line-clamp-2 text-xs text-navy-600"
                        >{{ issue.title }}</span
                    >
                    <span class="mt-2 grid gap-0.5 text-xs text-navy-500">
                        <span v-if="issue.volume">{{ issue.volume }}-jild</span>
                        <span
                            >{{ issue.articles }} maqola
                            <template v-if="issue.pages"
                                >· {{ issue.pages }} bet</template
                            ></span
                        >
                        <span v-if="issue.publishedAt"
                            >Chop: {{ formatDate(issue.publishedAt) }}</span
                        >
                    </span>
                    <span class="mt-auto pt-3">
                        <span
                            class="mb-1 flex items-center justify-between text-[11px] text-navy-500"
                        >
                            Nashrga tayyor
                            <b class="text-navy-800 tabular-nums"
                                >{{ issue.ready }}/{{ issue.articles }}</b
                            >
                        </span>
                        <span
                            class="block h-1.5 overflow-hidden rounded-full bg-navy-100"
                        >
                            <span
                                :class="
                                    cn(
                                        'block h-full rounded-full',
                                        issue.articles &&
                                            issue.ready === issue.articles
                                            ? 'bg-emerald-500'
                                            : 'bg-brand-500',
                                    )
                                "
                                :style="{
                                    width: `${readyPercent(issue.ready, issue.articles)}%`,
                                }"
                            />
                        </span>
                        <span
                            v-if="issue.hasPdf"
                            class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700"
                        >
                            <FileText class="size-3.5" /> To'liq PDF yuklangan
                        </span>
                    </span>
                </span>
            </Link>
        </div>
        <div
            v-else
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-navy-200 bg-white py-16 text-center"
        >
            <BookDashed class="size-10 text-navy-300" />
            <p class="text-sm text-navy-500">Hali jurnal soni yo'q</p>
            <button
                type="button"
                :class="primaryButtonClass"
                @click="createOpen = true"
            >
                <BookPlus class="size-4" /> Birinchi sonni yaratish
            </button>
        </div>
    </div>

    <IssueFormDialog
        v-model:open="createOpen"
        :url="storeUrl"
        method="post"
        :initial="{
            year: next.year,
            volume: next.volume,
            number: next.number,
            doi: null,
            title: null,
            description: null,
        }"
    />
</template>

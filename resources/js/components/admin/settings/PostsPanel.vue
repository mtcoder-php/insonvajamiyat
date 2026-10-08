<script setup lang="ts">
import {
    CalendarClock,
    ExternalLink,
    FileText,
    ImageOff,
    Megaphone,
    Newspaper,
    PenLine,
    Pin,
    Plus,
    Search,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { ref } from 'vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDateTime, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    SettingsFilters,
    SettingsPageProps,
    SettingsPost,
    SettingsPostStatus,
} from '@/types';
import DeleteDialog from './DeleteDialog.vue';
import PostDialog from './PostDialog.vue';
import { useSettingsQuery } from './useSettingsQuery';
import { t } from '@/lib/i18n';

/** Yangiliklar va e'lonlar: tur bo'yicha filtr, qidiruv, sahifalash */
const props = defineProps<{
    posts: NonNullable<SettingsPageProps['posts']>;
    filters: SettingsFilters;
    storeUrl: string;
    indexUrl: string;
}>();

const { search, apply } = useSettingsQuery(
    props.indexUrl,
    'posts',
    () => props.filters,
);

const editing = ref<SettingsPost | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsPost | null>(null);
const deleteOpen = ref(false);

function open(post: SettingsPost | null): void {
    editing.value = post;
    formOpen.value = true;
}

function remove(post: SettingsPost): void {
    removing.value = post;
    deleteOpen.value = true;
}

const chips = [
    { value: '', label: t('Hammasi'), key: 'all' },
    { value: 'news', label: t('Yangiliklar'), key: 'news' },
    { value: 'announcement', label: t("E'lonlar"), key: 'announcement' },
] as const;

const statuses: Record<SettingsPostStatus, { label: string; class: string }> = {
    published: {
        label: t('Chop etilgan'),
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    },
    scheduled: {
        label: t('Rejalashtirilgan'),
        class: 'bg-amber-50 text-amber-700 ring-amber-200',
    },
    draft: {
        label: t('Qoralama'),
        class: 'bg-slate-100 text-slate-600 ring-slate-200',
    },
};
</script>

<template>
    <section>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t("Yangiliklar va e'lonlar") }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "Saytdagi «Yangiliklar» bo'limi va bosh sahifadagi blok",
                        )
                    }}
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> {{ t("Xabar qo'shish") }}
            </button>
        </header>

        <div
            class="mb-4 flex flex-col gap-3 rounded-xl border border-line bg-white p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="chip in chips"
                    :key="chip.key"
                    type="button"
                    :class="
                        cn(
                            'inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-[13px] font-semibold transition-all',
                            filters.type === chip.value
                                ? 'bg-navy-900 text-white shadow-sm'
                                : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                        )
                    "
                    @click="apply({ type: chip.value })"
                >
                    {{ chip.label }}
                    <span
                        :class="
                            cn(
                                'rounded-full px-1.5 text-[11px] tabular-nums',
                                filters.type === chip.value
                                    ? 'bg-white/20'
                                    : 'bg-white text-navy-500',
                            )
                        "
                        >{{ formatNumber(posts.counts[chip.key]) }}</span
                    >
                </button>
            </div>
            <label class="relative sm:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('Sarlavha bo\'yicha qidirish…')"
                    :class="cn(inputClass, 'pl-9')"
                />
            </label>
        </div>

        <div class="grid grid-cols-1 gap-3">
            <article
                v-for="post in posts.data"
                :key="post.id"
                class="group flex flex-col gap-4 rounded-xl border border-line bg-white p-3 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)] sm:flex-row sm:items-center"
            >
                <div
                    class="relative aspect-[16/10] w-full shrink-0 overflow-hidden rounded-lg bg-[#eef3fa] sm:w-40"
                >
                    <img
                        v-if="post.imageUrl"
                        :src="post.imageUrl"
                        :alt="post.title"
                        loading="lazy"
                        class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <span
                        v-else
                        class="flex size-full items-center justify-center text-navy-300"
                    >
                        <ImageOff class="size-6" />
                    </span>
                    <span
                        v-if="post.isPinned"
                        class="absolute top-1.5 left-1.5 inline-flex size-6 items-center justify-center rounded-full bg-gold-500 text-white shadow"
                        :title="t('Qadalgan')"
                    >
                        <Pin class="size-3.5" />
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="mb-1.5 flex flex-wrap items-center gap-1.5">
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700"
                        >
                            <Megaphone
                                v-if="post.type === 'announcement'"
                                class="size-3"
                            />
                            <Newspaper v-else class="size-3" />
                            {{
                                post.type === 'announcement'
                                    ? t("E'lon")
                                    : t('Yangilik')
                            }}
                        </span>
                        <span
                            :class="
                                cn(
                                    'rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1',
                                    statuses[post.status].class,
                                )
                            "
                            >{{ statuses[post.status].label }}</span
                        >
                    </div>
                    <h3
                        class="line-clamp-2 text-[14px] leading-snug font-bold [overflow-wrap:anywhere] text-navy-950 transition-colors group-hover:text-brand-700"
                    >
                        {{ post.title }}
                    </h3>
                    <p
                        v-if="post.translations.excerpt.uz"
                        class="mt-1 line-clamp-1 text-xs [overflow-wrap:anywhere] text-navy-500"
                    >
                        {{ post.translations.excerpt.uz }}
                    </p>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-navy-500"
                    >
                        <span
                            v-if="post.publishedAt"
                            class="inline-flex items-center gap-1"
                        >
                            <CalendarClock class="size-3.5" />
                            {{ formatDateTime(post.publishedAt) }}
                        </span>
                        <span
                            v-if="post.author"
                            class="inline-flex items-center gap-1"
                        >
                            <UserRound class="size-3.5" /> {{ post.author }}
                        </span>
                        <span
                            v-if="!post.translations.body.uz"
                            class="inline-flex items-center gap-1 text-amber-600"
                        >
                            <FileText class="size-3.5" />
                            {{ t('Matn kiritilmagan') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 gap-1 self-end sm:self-center">
                    <a
                        v-if="post.url && post.status === 'published'"
                        :href="post.url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                        :aria-label="t('Saytda ko\'rish')"
                    >
                        <ExternalLink class="size-4" />
                    </a>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                        :aria-label="t('Tahrirlash')"
                        @click="open(post)"
                    >
                        <PenLine class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                        :aria-label="t('O\'chirish')"
                        @click="remove(post)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </article>
        </div>

        <p
            v-if="!posts.data.length"
            class="rounded-xl border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-navy-400"
        >
            {{
                filters.q || filters.type
                    ? "Filtr bo'yicha xabar topilmadi"
                    : "Hali xabarlar yo'q — birinchisini qo'shing"
            }}
        </p>

        <div v-if="posts.meta.lastPage > 1" class="mt-4">
            <SimplePager :meta="posts.meta" @go="(page) => apply({}, page)" />
        </div>

        <PostDialog
            v-model:open="formOpen"
            :post="editing"
            :store-url="storeUrl"
            :default-type="filters.type || 'news'"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('Xabarni o\'chirish')"
            :description="
                t('«:title» saytdan olib tashlanadi.', {
                    title: removing?.title ?? '',
                })
            "
        />
    </section>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BookPlus,
    Download,
    Eye,
    EyeOff,
    FileText,
    LibraryBig,
    Link2,
    PenLine,
    Search,
    Star,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import BookDialog from '@/components/admin/library/BookDialog.vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { formatFileSize, formatNumber } from '@/lib/format';
import { inputClass } from '@/lib/formStyles';
import { t, tk } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/library';
import type { AdminBook, AdminLibraryProps, AdminLibraryStatus } from '@/types';

/**
 * Admin → Kutubxona: kitoblar ro'yxati, qidiruv va filtrlar, qo'shish/tahrirlash,
 * "Tavsiya etilgan" (bosh sahifa bloki) va "Faol" belgilarini bir bosishda almashtirish.
 */
const props = defineProps<AdminLibraryProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Kutubxona'), href: index() },
        ],
    },
});

const form = reactive({
    q: props.filters.q,
    status: props.filters.status,
    subject: props.filters.subject,
});

const hasFilters = computed(
    () => form.q !== '' || form.status !== 'all' || form.subject !== null,
);

function apply(page = 1): void {
    const query: Record<string, string | number> = {};

    if (form.q.trim() !== '') {
        query.q = form.q.trim();
    }

    if (form.status !== 'all') {
        query.status = form.status;
    }

    if (form.subject !== null) {
        query.subject = form.subject;
    }

    if (page > 1) {
        query.page = page;
    }

    router.get(index().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['books', 'filters'],
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
    () => [form.status, form.subject],
    () => apply(),
);

function reset(): void {
    form.q = '';
    form.status = 'all';
    form.subject = null;
}

const statuses = computed<
    { value: AdminLibraryStatus; label: string; count?: number }[]
>(() => [
    { value: 'all', label: t('Barchasi'), count: props.stats.total },
    {
        value: 'featured',
        label: t('Tavsiya etilgan'),
        count: props.stats.featured,
    },
    { value: 'active', label: t('Faol') },
    { value: 'hidden', label: t('Yashirin') },
    { value: 'pdf', label: t('PDF bor'), count: props.stats.files },
]);

const tiles = computed(() => [
    {
        key: 'total',
        label: t('Jami kitoblar'),
        value: formatNumber(props.stats.total),
        icon: LibraryBig,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        key: 'featured',
        label: t('Tavsiya etilgan'),
        value: formatNumber(props.stats.featured),
        hint: t("Bosh sahifada ko'rinadi"),
        icon: Star,
        tint: 'bg-amber-50 text-amber-600',
    },
    {
        key: 'files',
        label: t('PDF fayli bor'),
        value: formatNumber(props.stats.files),
        icon: FileText,
        tint: 'bg-red-50 text-red-600',
    },
    {
        key: 'views',
        label: t("Ko'rishlar"),
        value: formatNumber(props.stats.views),
        icon: Eye,
        tint: 'bg-sky-50 text-sky-600',
    },
    {
        key: 'downloads',
        label: t('Yuklab olishlar'),
        value: formatNumber(props.stats.downloads),
        icon: Download,
        tint: 'bg-emerald-50 text-emerald-600',
    },
]);

const languages: Record<string, string> = { uz: "O'zb", ru: 'Рус', en: 'Eng' };

const dialogOpen = ref(false);
const editing = ref<AdminBook | null>(null);

function create(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function edit(book: AdminBook): void {
    editing.value = book;
    dialogOpen.value = true;
}

const busy = ref<string | null>(null);

function toggle(book: AdminBook, field: 'is_featured' | 'is_active'): void {
    const value = field === 'is_featured' ? !book.isFeatured : !book.isActive;
    busy.value = `${book.id}-${field}`;

    router.patch(
        book.urls.toggle,
        { field, value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                busy.value = null;
            },
        },
    );
}

const deleting = ref<AdminBook | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function askDelete(book: AdminBook): void {
    deleting.value = book;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    deleteProcessing.value = true;
    router.delete(deleting.value.urls.destroy, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
}

const iconButton =
    'inline-flex size-8 items-center justify-center rounded-lg text-navy-400 transition-all hover:-translate-y-px disabled:opacity-50';
</script>

<template>
    <Head :title="t('Kutubxona')" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            :title="t('Kutubxona')"
            :description="
                t(
                    'Kitoblar: muqova, PDF fayl yoki havola, ilmiy yo\'nalish. «Tavsiya etilgan» kitoblar bosh sahifada chiqadi.',
                )
            "
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                    @click="create"
                >
                    <BookPlus class="size-4" /> {{ t('Yangi kitob') }}
                </button>
            </template>
        </PageHeader>

        <section
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5"
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
            <div class="flex flex-col gap-3 border-b border-line p-4">
                <div
                    class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_16rem_auto]"
                >
                    <label class="relative">
                        <span class="sr-only">{{ t('Qidirish') }}</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="form.q"
                            type="search"
                            :class="cn(inputClass, 'pl-9')"
                            :placeholder="t('Kitob nomi, muallif yoki ISBN...')"
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
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-navy-600 transition-colors hover:bg-navy-50 hover:text-navy-900"
                        @click="reset"
                    >
                        <X class="size-4" /> {{ t('Tozalash') }}
                    </button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="item in statuses"
                        :key="item.value"
                        type="button"
                        :class="
                            cn(
                                'inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-xs font-semibold transition-all',
                                form.status === item.value
                                    ? 'bg-navy-900 text-white shadow-sm'
                                    : 'bg-white text-navy-600 ring-1 ring-line hover:-translate-y-px hover:text-brand-700 hover:ring-brand-200',
                            )
                        "
                        @click="form.status = item.value"
                    >
                        {{ item.label }}
                        <span
                            v-if="item.count !== undefined"
                            :class="
                                cn(
                                    'rounded-full px-1.5 text-[10px] tabular-nums',
                                    form.status === item.value
                                        ? 'bg-white/15'
                                        : 'bg-navy-50 text-navy-500',
                                )
                            "
                            >{{ item.count }}</span
                        >
                    </button>
                </div>
            </div>

            <ul v-if="books.data.length" class="divide-y divide-line">
                <li
                    v-for="book in books.data"
                    :key="book.id"
                    :class="
                        cn(
                            'group flex flex-col gap-3 p-4 transition-colors hover:bg-brand-50/30 sm:flex-row sm:items-center',
                            !book.isActive && 'bg-navy-50/40',
                        )
                    "
                >
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-4 text-left"
                        @click="edit(book)"
                    >
                        <span
                            class="relative h-[4.5rem] w-12 shrink-0 overflow-hidden rounded-[3px_6px_6px_3px] bg-navy-100 shadow-[0_8px_16px_-10px_rgba(0,30,60,0.6)] ring-1 ring-line transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:-rotate-2"
                        >
                            <img
                                v-if="book.coverUrl"
                                :src="book.coverUrl"
                                :alt="book.title"
                                class="size-full object-cover"
                                loading="lazy"
                            />
                            <LibraryBig
                                v-else
                                class="absolute inset-0 m-auto size-5 text-navy-300"
                            />
                            <span
                                class="absolute inset-y-0 left-0 w-1 bg-black/15"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-center gap-2 font-semibold text-navy-950 group-hover:text-brand-700"
                            >
                                <span class="truncate">{{ book.title }}</span>
                                <span
                                    v-if="!book.isActive"
                                    class="shrink-0 rounded bg-navy-100 px-1.5 text-[10px] font-semibold text-navy-600"
                                    >{{ t('yashirin') }}</span
                                >
                            </span>
                            <span class="block truncate text-xs text-navy-500">
                                {{
                                    [book.author, book.publisher, book.year]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                            <span class="mt-1.5 flex flex-wrap gap-1.5">
                                <span
                                    v-if="book.subject"
                                    class="max-w-[16rem] truncate rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-medium text-brand-700"
                                    >{{ book.subject }}</span
                                >
                                <span
                                    class="rounded-md bg-navy-50 px-1.5 py-0.5 text-[11px] font-semibold text-navy-600"
                                    >{{ languages[book.language] }}</span
                                >
                                <span
                                    v-if="book.file"
                                    class="inline-flex items-center gap-1 rounded-md bg-red-50 px-1.5 py-0.5 text-[11px] font-semibold text-red-700"
                                >
                                    <FileText class="size-3" /> PDF
                                    <template v-if="book.file.size"
                                        >·
                                        {{
                                            formatFileSize(book.file.size)
                                        }}</template
                                    >
                                </span>
                                <span
                                    v-if="book.url"
                                    class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-1.5 py-0.5 text-[11px] font-semibold text-sky-700"
                                >
                                    <Link2 class="size-3" /> {{ t('Havola') }}
                                </span>
                            </span>
                        </span>
                    </button>

                    <div
                        class="flex shrink-0 items-center justify-between gap-4 sm:justify-end"
                    >
                        <div
                            class="flex gap-3 text-xs text-navy-500 tabular-nums"
                        >
                            <span
                                class="inline-flex items-center gap-1"
                                :title="t('Ko\'rishlar')"
                                ><Eye class="size-3.5" />
                                {{ formatNumber(book.views) }}</span
                            >
                            <span
                                class="inline-flex items-center gap-1"
                                :title="t('Yuklab olishlar')"
                                ><Download class="size-3.5" />
                                {{ formatNumber(book.downloads) }}</span
                            >
                        </div>
                        <div class="flex items-center gap-0.5">
                            <button
                                type="button"
                                :disabled="busy === `${book.id}-is_featured`"
                                :title="
                                    book.isFeatured
                                        ? t('Tavsiyadan olish')
                                        : t('Tavsiya etish (bosh sahifa)')
                                "
                                :class="
                                    cn(
                                        iconButton,
                                        book.isFeatured
                                            ? 'bg-amber-50 text-amber-500 hover:bg-amber-100'
                                            : 'hover:bg-amber-50 hover:text-amber-500',
                                    )
                                "
                                @click="toggle(book, 'is_featured')"
                            >
                                <Star
                                    :class="
                                        cn(
                                            'size-4',
                                            book.isFeatured && 'fill-current',
                                        )
                                    "
                                />
                            </button>
                            <button
                                type="button"
                                :disabled="busy === `${book.id}-is_active`"
                                :title="
                                    book.isActive
                                        ? t('Saytdan yashirish')
                                        : t('Saytda ko\'rsatish')
                                "
                                :class="
                                    cn(
                                        iconButton,
                                        book.isActive
                                            ? 'text-emerald-600 hover:bg-emerald-50'
                                            : 'hover:bg-navy-100 hover:text-navy-700',
                                    )
                                "
                                @click="toggle(book, 'is_active')"
                            >
                                <Eye v-if="book.isActive" class="size-4" />
                                <EyeOff v-else class="size-4" />
                            </button>
                            <a
                                v-if="book.urls.public && book.isActive"
                                :href="book.urls.public"
                                target="_blank"
                                rel="noopener"
                                :title="t('Saytda ochish')"
                                :class="
                                    cn(
                                        iconButton,
                                        'hover:bg-brand-50 hover:text-brand-600',
                                    )
                                "
                            >
                                <Link2 class="size-4" />
                            </a>
                            <button
                                type="button"
                                :title="t('Tahrirlash')"
                                :class="
                                    cn(
                                        iconButton,
                                        'hover:bg-brand-50 hover:text-brand-600',
                                    )
                                "
                                @click="edit(book)"
                            >
                                <PenLine class="size-4" />
                            </button>
                            <button
                                type="button"
                                :title="t('O\'chirish')"
                                :class="
                                    cn(
                                        iconButton,
                                        'hover:bg-red-50 hover:text-red-600',
                                    )
                                "
                                @click="askDelete(book)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>
                </li>
            </ul>

            <div
                v-else
                class="flex flex-col items-center px-6 py-16 text-center"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-500"
                >
                    <LibraryBig class="size-7" />
                </span>
                <p class="mt-4 font-semibold text-navy-900">
                    {{
                        hasFilters ? t('Kitob topilmadi') : t("Kutubxona bo'sh")
                    }}
                </p>
                <p class="mt-1 text-sm text-navy-500">
                    {{
                        hasFilters
                            ? t(
                                  "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring.",
                              )
                            : t("Birinchi kitobni qo'shing.")
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
                <button
                    v-else
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-600"
                    @click="create"
                >
                    <BookPlus class="size-4" /> {{ t('Yangi kitob') }}
                </button>
            </div>

            <div
                v-if="books.meta.last_page > 1"
                class="border-t border-line bg-[#fbfcfe] px-4 py-3"
            >
                <SimplePager
                    :meta="{
                        currentPage: books.meta.current_page,
                        lastPage: books.meta.last_page,
                        total: books.meta.total,
                        from: books.meta.from,
                        to: books.meta.to,
                    }"
                    @go="apply"
                />
            </div>
        </section>
    </div>

    <BookDialog
        v-model:open="dialogOpen"
        :book="editing"
        :store-url="storeUrl"
        :subjects="subjects"
    />

    <ActionDialog
        v-model:open="deleteOpen"
        :icon="Trash2"
        tone="danger"
        :title="t('Kitobni o\'chirish')"
        :description="
            deleting
                ? t(
                      '«:title» kutubxonadan o\'chiriladi — muqova va PDF fayl ham. Bu amalni qaytarib bo\'lmaydi.',
                      { title: deleting.title },
                  )
                : undefined
        "
        :confirm-text="t('O\'chirish')"
        :processing="deleteProcessing"
        @confirm="destroy"
    />
</template>

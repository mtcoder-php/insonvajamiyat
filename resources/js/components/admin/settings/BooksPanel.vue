<script setup lang="ts">
import { BookOpen, ExternalLink, PenLine, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';
import type { SettingsBook } from '@/types';
import BookDialog from './BookDialog.vue';
import DeleteDialog from './DeleteDialog.vue';
import { t } from '@/lib/i18n';

/** Tavsiya etilgan kitoblar: muqovali kartochkalar, bosh sahifada ko'rinadiganlari belgilangan */
const props = defineProps<{ books: SettingsBook[]; storeUrl: string }>();

const HOME_LIMIT = 3;

// Bosh sahifadagi tartib: sort_order bo'yicha birinchi 3 ta faol kitob
const onHome = computed(
    () =>
        new Set(
            props.books
                .filter((b) => b.isActive)
                .slice(0, HOME_LIMIT)
                .map((b) => b.id),
        ),
);

const editing = ref<SettingsBook | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsBook | null>(null);
const deleteOpen = ref(false);

function open(book: SettingsBook | null): void {
    editing.value = book;
    formOpen.value = true;
}

function remove(book: SettingsBook): void {
    removing.value = book;
    deleteOpen.value = true;
}
</script>

<template>
    <section>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Tavsiya etilgan kitoblar') }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "Bosh sahifada tartib bo'yicha birinchi :count ta faol kitob chiqadi",
                            { count: HOME_LIMIT },
                        )
                    }}
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> {{ t("Kitob qo'shish") }}
            </button>
        </header>

        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="book in books"
                :key="book.id"
                class="group flex gap-4 rounded-xl border border-line bg-white p-3 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
            >
                <div
                    class="relative aspect-[3/4] w-20 shrink-0 overflow-hidden rounded-md bg-gradient-to-br from-navy-800 to-navy-950 shadow-[4px_6px_14px_-6px_rgba(0,36,66,0.55)] transition-transform duration-300 group-hover:-rotate-2"
                >
                    <img
                        v-if="book.coverUrl"
                        :src="book.coverUrl"
                        :alt="book.title"
                        loading="lazy"
                        :class="
                            cn(
                                'size-full object-cover',
                                !book.isActive && 'opacity-60 grayscale',
                            )
                        "
                    />
                    <span
                        v-else
                        class="flex size-full items-center justify-center text-gold-300"
                    >
                        <BookOpen class="size-7" />
                    </span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="mb-1 flex flex-wrap gap-1">
                        <span
                            v-if="onHome.has(book.id)"
                            class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"
                            >{{ t('Bosh sahifada') }}</span
                        >
                        <span
                            v-else-if="!book.isActive"
                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600"
                            >{{ t('Nofaol') }}</span
                        >
                    </div>
                    <h3
                        class="line-clamp-3 font-serif text-[14px] leading-snug font-bold [overflow-wrap:anywhere] text-navy-950 transition-colors group-hover:text-brand-700"
                    >
                        {{ book.title }}
                    </h3>
                    <p class="mt-1 truncate text-xs text-navy-500">
                        {{ book.author
                        }}<template v-if="book.year"
                            >, {{ book.year }}</template
                        >
                    </p>
                    <div class="mt-auto flex items-center gap-1 pt-2">
                        <span class="text-[11px] text-navy-400 tabular-nums"
                            >#{{ book.sortOrder }}</span
                        >
                        <span class="ml-auto inline-flex gap-0.5">
                            <a
                                v-if="book.url"
                                :href="book.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                :aria-label="t('Havolani ochish')"
                            >
                                <ExternalLink class="size-4" />
                            </a>
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                :aria-label="t('Tahrirlash')"
                                @click="open(book)"
                            >
                                <PenLine class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                :aria-label="t('O\'chirish')"
                                @click="remove(book)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </span>
                    </div>
                </div>
            </article>
        </div>
        <p
            v-if="!books.length"
            class="rounded-xl border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-navy-400"
        >
            {{ t("Kitoblar yo'q — bosh sahifadagi blok yashirin") }}
        </p>

        <BookDialog
            v-model:open="formOpen"
            :book="editing"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('Kitobni o\'chirish')"
            :description="
                t('«:title» va uning muqovasi o\'chiriladi.', {
                    title: removing?.title ?? '',
                })
            "
        />
    </section>
</template>

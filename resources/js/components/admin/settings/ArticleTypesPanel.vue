<script setup lang="ts">
import { CalendarClock, FileText, PenLine, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { SettingsArticleType } from '@/types';
import ArticleTypeDialog from './ArticleTypeDialog.vue';
import DeleteDialog from './DeleteDialog.vue';
import { t, tc } from '@/lib/i18n';

/** Maqola turlari va nashr narxlari (TZ 4.2.8) */
defineProps<{ types: SettingsArticleType[]; storeUrl: string }>();

const editing = ref<SettingsArticleType | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsArticleType | null>(null);
const deleteOpen = ref(false);

function open(type: SettingsArticleType | null): void {
    editing.value = type;
    formOpen.value = true;
}

function remove(type: SettingsArticleType): void {
    removing.value = type;
    deleteOpen.value = true;
}
</script>

<template>
    <section>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Maqola turlari va narxlar') }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "Narx 0 bo'lsa — maqola to'lovsiz tahririyat navbatiga tushadi",
                        )
                    }}
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> {{ t("Tur qo'shish") }}
            </button>
        </header>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="type in types"
                :key="type.id"
                :class="
                    cn(
                        'group relative flex flex-col overflow-hidden rounded-xl border bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_34px_-20px_rgba(0,36,66,0.45)]',
                        type.isActive
                            ? 'border-line hover:border-brand-200'
                            : 'border-dashed border-navy-200 opacity-75',
                    )
                "
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 to-violet-500 opacity-0 transition-opacity group-hover:opacity-100"
                />
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                    >
                        <FileText class="size-5" />
                    </span>
                    <span
                        :class="
                            cn(
                                'rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                type.isActive
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-navy-50 text-navy-500 ring-line',
                            )
                        "
                        >{{ type.isActive ? t('Faol') : t('Nofaol') }}</span
                    >
                </div>
                <h3 class="mt-3 font-sans text-[15px] font-bold text-navy-950">
                    {{ type.name }}
                </h3>
                <p class="mt-1 line-clamp-2 min-h-8 text-xs text-navy-500">
                    {{ type.translations.description.uz || '—' }}
                </p>
                <p
                    class="mt-3 font-sans text-2xl font-bold text-navy-950 tabular-nums"
                >
                    {{ type.price > 0 ? formatSum(type.price) : t('Bepul') }}
                </p>
                <div
                    class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-navy-500"
                >
                    <span
                        v-if="type.reviewDays"
                        class="inline-flex items-center gap-1"
                        ><CalendarClock class="size-3.5" />
                        {{ t('~:days kun', { days: type.reviewDays }) }}</span
                    >
                    <span>{{ tc(':count maqola', type.articlesCount) }}</span>
                    <span class="font-mono text-[11px] text-navy-400">{{
                        type.slug
                    }}</span>
                </div>
                <div class="mt-4 flex gap-2 border-t border-line pt-3">
                    <button
                        type="button"
                        class="inline-flex h-8 flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-100"
                        @click="open(type)"
                    >
                        <PenLine class="size-3.5" /> {{ t('Tahrirlash') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600"
                        :aria-label="t('O\'chirish')"
                        @click="remove(type)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </article>
        </div>
        <p
            v-if="!types.length"
            class="rounded-xl border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-navy-400"
        >
            {{ t("Maqola turlari hali qo'shilmagan") }}
        </p>

        <ArticleTypeDialog
            v-model:open="formOpen"
            :type="editing"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('Maqola turini o\'chirish')"
            :description="
                t(
                    '«:name» yuborish formasidan olib tashlanadi. Avvalgi maqolalar va to\'lovlar o\'zgarmaydi.',
                    { name: removing?.name ?? '' },
                )
            "
        />
    </section>
</template>

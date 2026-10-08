<script setup lang="ts">
import {
    BookMarked,
    Download,
    ExternalLink,
    FileSignature,
    FileText,
    Files,
    PenLine,
    Plus,
    Trash2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import FileBadge from '@/components/web/content/FileBadge.vue';
import { formatDateTime, formatFileSize } from '@/lib/format';
import { t, tc } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { guidelines } from '@/routes';
import type { SettingsDocument, SettingsDocumentKind } from '@/types';
import DeleteDialog from './DeleteDialog.vue';
import DocumentDialog from './DocumentDialog.vue';

/** Mualliflar uchun fayllar (TZ 4.2.5) — tur bo'yicha guruhlangan ro'yxat */
const props = defineProps<{
    documents: SettingsDocument[];
    kinds: { value: SettingsDocumentKind; label: string }[];
    limits: { extensions: string[]; maxKb: number };
    storeUrl: string;
}>();

const icons: Record<SettingsDocumentKind, Component> = {
    template: FileText,
    guide: BookMarked,
    form: FileSignature,
    other: Files,
};

const groups = computed(() =>
    props.kinds.map((kind) => ({
        ...kind,
        items: props.documents.filter((d) => d.kind === kind.value),
    })),
);

/** Saytdagi tugmalarga ulanadigan shablon (birinchi faol) */
const activeTemplateId = computed(
    () =>
        props.documents.find((d) => d.kind === 'template' && d.isActive)?.id ??
        null,
);

const editing = ref<SettingsDocument | null>(null);
const formOpen = ref(false);
const newKind = ref<SettingsDocumentKind>('template');
const removing = ref<SettingsDocument | null>(null);
const deleteOpen = ref(false);

function open(
    document: SettingsDocument | null,
    kind: SettingsDocumentKind = 'template',
): void {
    editing.value = document;
    newKind.value = kind;
    formOpen.value = true;
}

function remove(document: SettingsDocument): void {
    removing.value = document;
    deleteOpen.value = true;
}
</script>

<template>
    <section class="grid grid-cols-1 gap-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Mualliflar uchun fayllar') }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "Maqola shabloni, yo'riqnoma va shakllar — «Yo'riqnoma» sahifasida va kabinetda yuklab olinadi",
                        )
                    }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    :href="`${guidelines().url}#downloads`"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-line bg-white px-3.5 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                >
                    <ExternalLink class="size-4" /> {{ t('Saytda') }}
                </a>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                    @click="open(null)"
                >
                    <Plus class="size-4" /> {{ t('Fayl yuklash') }}
                </button>
            </div>
        </header>

        <div v-for="group in groups" :key="group.value">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3
                    class="inline-flex items-center gap-2 text-[13px] font-bold text-navy-700"
                >
                    <component
                        :is="icons[group.value]"
                        class="size-4 text-brand-600"
                    />
                    {{ group.label }}
                    <span
                        class="rounded-full bg-[#eef3fa] px-2 text-[11px] text-navy-500 tabular-nums"
                        >{{ group.items.length }}</span
                    >
                </h3>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 transition-colors hover:text-brand-500"
                    @click="open(null, group.value)"
                >
                    <Plus class="size-3.5" /> {{ t("Qo'shish") }}
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                <article
                    v-for="doc in group.items"
                    :key="doc.id"
                    class="group flex items-start gap-3.5 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                >
                    <FileBadge
                        :extension="doc.extension"
                        :class="
                            cn(
                                'transition-transform duration-300 group-hover:-rotate-3',
                                !doc.isActive && 'opacity-50 grayscale',
                            )
                        "
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <p
                                class="text-[13px] leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                            >
                                {{ doc.name }}
                            </p>
                            <span
                                v-if="doc.id === activeTemplateId"
                                class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200"
                                >{{ t('Saytdagi shablon') }}</span
                            >
                            <span
                                v-if="!doc.isActive"
                                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600"
                                >{{ t('Nofaol') }}</span
                            >
                        </div>
                        <p class="mt-0.5 truncate text-[11px] text-navy-500">
                            {{ doc.originalName }} ·
                            {{ formatFileSize(doc.size) }}
                        </p>
                        <p
                            class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-navy-400"
                        >
                            <span class="inline-flex items-center gap-1">
                                <Download class="size-3" />
                                {{
                                    tc(':count marta yuklangan', doc.downloads)
                                }}
                            </span>
                            <span v-if="doc.updatedAt">
                                {{ formatDateTime(doc.updatedAt) }}
                                <template v-if="doc.updatedBy">
                                    · {{ doc.updatedBy }}</template
                                >
                            </span>
                        </p>
                    </div>
                    <span class="-mt-1 -mr-1 inline-flex shrink-0 gap-0.5">
                        <a
                            :href="doc.urls.download"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            :aria-label="t('Yuklab olish')"
                        >
                            <Download class="size-4" />
                        </a>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            :aria-label="t('Tahrirlash')"
                            @click="open(doc)"
                        >
                            <PenLine class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                            :aria-label="t('O\'chirish')"
                            @click="remove(doc)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </span>
                </article>
            </div>
            <p
                v-if="!group.items.length"
                class="rounded-xl border border-dashed border-line bg-white px-4 py-6 text-center text-sm text-navy-400"
            >
                {{ t("Hali qo'shilmagan") }}
            </p>
        </div>

        <DocumentDialog
            v-model:open="formOpen"
            :document="editing"
            :kinds="kinds"
            :limits="limits"
            :default-kind="newKind"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('Faylni o\'chirish')"
            :description="
                t('«:name» fayli saytdan o\'chiriladi.', {
                    name: removing?.name ?? '',
                })
            "
        />
    </section>
</template>

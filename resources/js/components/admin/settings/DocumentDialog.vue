<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Eye, FilePlus2, FileUp, PenLine, RefreshCw, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { formatFileSize } from '@/lib/format';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type {
    SettingsDocument,
    SettingsDocumentKind,
    Translated,
} from '@/types';
import CheckCard from './CheckCard.vue';
import FileBadge from '@/components/web/content/FileBadge.vue';
import TranslatableField from './TranslatableField.vue';

/** Mualliflar uchun fayl: tur, fayl (yangisida majburiy), nom va izoh uch tilda */
const props = defineProps<{
    document: SettingsDocument | null;
    storeUrl: string;
    kinds: { value: SettingsDocumentKind; label: string }[];
    limits: { extensions: string[]; maxKb: number };
    defaultKind?: SettingsDocumentKind;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    kind: SettingsDocumentKind;
    title: Translated;
    description: Translated;
    file: File | null;
    is_active: boolean;
    sort_order: number;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    kind: props.defaultKind ?? 'template',
    title: empty(),
    description: empty(),
    file: null,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());
const localError = ref<string | null>(null);
const dragging = ref(false);
const input = ref<HTMLInputElement | null>(null);

watch(open, (value) => {
    if (!value) {
        return;
    }

    const d = props.document;
    form.defaults(
        d
            ? {
                  kind: d.kind,
                  title: { ...d.translations.title },
                  description: { ...d.translations.description },
                  file: null,
                  is_active: d.isActive,
                  sort_order: d.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
    localError.value = null;
});

const errors = computed(() => form.errors as Record<string, string>);
const accept = computed(() =>
    props.limits.extensions.map((ext) => `.${ext}`).join(','),
);
const fileError = computed(() => localError.value ?? errors.value.file);

function extensionOf(name: string): string {
    const dot = name.lastIndexOf('.');

    return dot > 0 ? name.slice(dot + 1).toLowerCase() : '';
}

function take(file: File | undefined): void {
    localError.value = null;

    if (!file) {
        return;
    }

    if (!props.limits.extensions.includes(extensionOf(file.name))) {
        localError.value = t('Ruxsat etilgan formatlar: :list', {
            list: props.limits.extensions.join(', '),
        });

        return;
    }

    if (file.size > props.limits.maxKb * 1024) {
        localError.value = t('Fayl hajmi :size dan oshmasligi kerak', {
            size: formatFileSize(props.limits.maxKb * 1024),
        });

        return;
    }

    form.file = file;

    // Nom bo'sh bo'lsa — fayl nomidan taklif
    if (!form.title.uz.trim()) {
        form.title.uz = file.name
            .replace(/\.[^.]+$/, '')
            .replace(/[_-]+/g, ' ')
            .trim()
            .slice(0, 150);
    }
}

function pick(event: Event): void {
    const target = event.target as HTMLInputElement;
    take(target.files?.[0]);
    target.value = '';
}

function drop(event: DragEvent): void {
    dragging.value = false;
    take(event.dataTransfer?.files[0]);
}

function submit(): void {
    if (!props.document && !form.file) {
        localError.value = t('Faylni tanlang');

        return;
    }

    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.document) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.document.urls.update,
            options,
        );
    } else {
        form.transform((data) => data).post(props.storeUrl, options);
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="document ? t('Faylni tahrirlash') : t('Yangi fayl')"
        :description="
            t(
                '«Mualliflar uchun yo\'riqnoma» sahifasida va kabinetda yuklab olish uchun ko\'rinadi.',
            )
        "
        :icon="document ? PenLine : FilePlus2"
        :confirm-text="document ? t('Saqlash') : t('Yuklash')"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4">
            <div
                class="grid grid-cols-2 gap-1 rounded-xl bg-[#eef3fa] p-1 sm:grid-cols-4"
                role="radiogroup"
                :aria-label="t('Turi')"
            >
                <button
                    v-for="item in kinds"
                    :key="item.value"
                    type="button"
                    role="radio"
                    :aria-checked="form.kind === item.value"
                    :class="
                        cn(
                            'inline-flex min-h-9 items-center justify-center rounded-lg px-2 py-1.5 text-center text-[12px] leading-tight font-semibold transition-all',
                            form.kind === item.value
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-navy-500 hover:text-navy-800',
                        )
                    "
                    @click="form.kind = item.value"
                >
                    {{ item.label }}
                </button>
            </div>
            <p
                v-if="form.kind === 'template'"
                class="-mt-2 text-xs text-navy-500"
            >
                {{
                    t(
                        'Birinchi faol shablon saytdagi va kabinetdagi «Shablonni yuklab olish» tugmalariga ulanadi.',
                    )
                }}
            </p>

            <!-- Fayl -->
            <div class="grid gap-1.5">
                <span class="text-[13px] font-semibold text-navy-800">
                    {{ t('Fayl') }}
                    <span v-if="!document" class="text-red-500">*</span>
                </span>

                <div
                    v-if="form.file || document"
                    class="flex items-center gap-3 rounded-xl border border-line bg-[#f8fafd] p-3"
                >
                    <FileBadge
                        :extension="
                            form.file
                                ? extensionOf(form.file.name)
                                : (document?.extension ?? '')
                        "
                    />
                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-[13px] font-semibold text-navy-900"
                            >{{
                                form.file?.name ?? document?.originalName
                            }}</span
                        >
                        <span class="block text-xs text-navy-500">
                            {{
                                formatFileSize(
                                    form.file?.size ?? document?.size ?? 0,
                                )
                            }}
                            <template v-if="form.file && document">
                                ·
                                {{
                                    t('yangi fayl — saqlanganda almashtiriladi')
                                }}
                            </template>
                        </span>
                    </span>
                    <button
                        v-if="form.file"
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600"
                        :aria-label="t('Faylni olib tashlash')"
                        @click="form.file = null"
                    >
                        <X class="size-4" />
                    </button>
                    <button
                        v-else
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-white px-3 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                        @click="input?.click()"
                    >
                        <RefreshCw class="size-3.5" />
                        {{ t('Almashtirish') }}
                    </button>
                </div>

                <label
                    v-else
                    :class="
                        cn(
                            'group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-7 text-center transition-all',
                            dragging
                                ? 'border-brand-400 bg-brand-50'
                                : 'border-navy-200 bg-[#fafbfd] hover:border-brand-300 hover:bg-brand-50/50',
                        )
                    "
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="drop"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-line transition-transform duration-300 group-hover:-translate-y-0.5"
                    >
                        <FileUp class="size-5" />
                    </span>
                    <span class="text-[13px] font-semibold text-navy-800">
                        {{ t('Faylni tanlang yoki shu yerga tashlang') }}
                    </span>
                    <span class="text-xs text-navy-500">
                        {{ limits.extensions.join(', ').toUpperCase() }} ·
                        {{
                            t(':size gacha', {
                                size: formatFileSize(limits.maxKb * 1024),
                            })
                        }}
                    </span>
                    <input
                        type="file"
                        :accept="accept"
                        class="sr-only"
                        @change="pick"
                    />
                </label>
                <input
                    ref="input"
                    type="file"
                    :accept="accept"
                    class="hidden"
                    @change="pick"
                />
                <p v-if="fileError" class="text-xs text-red-600">
                    {{ fileError }}
                </p>
            </div>

            <TranslatableField
                v-model="form.title"
                :label="t('Nomi')"
                field="title"
                :errors="errors"
                required
                :maxlength="150"
                :placeholder="t('Maqola shabloni (Word)')"
            />
            <TranslatableField
                v-model="form.description"
                :label="t('Izoh')"
                field="description"
                :errors="errors"
                :maxlength="300"
                :placeholder="
                    t('Maqola shu shablon asosida rasmiylashtiriladi')
                "
            />

            <div
                class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[minmax(0,1fr)_7rem]"
            >
                <CheckCard
                    v-model="form.is_active"
                    :label="t('Faol')"
                    :hint="t('Belgilanmasa — saytda ko\'rinmaydi')"
                    :icon="Eye"
                />
                <FormField
                    :label="t('Tartib')"
                    for="document-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="document-order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
        </div>
    </ActionDialog>
</template>

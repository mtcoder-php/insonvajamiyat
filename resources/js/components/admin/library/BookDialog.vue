<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    BookPlus,
    Eye,
    FileText,
    FileUp,
    PenLine,
    Star,
    Trash2,
    Undo2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CheckCard from '@/components/admin/settings/CheckCard.vue';
import ImagePicker from '@/components/admin/settings/ImagePicker.vue';
import TranslatableField from '@/components/admin/settings/TranslatableField.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { formatFileSize } from '@/lib/format';
import { inputClass } from '@/lib/formStyles';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { AdminBook, LibraryLanguage, Translated } from '@/types';

/**
 * Kutubxona → kitob: muqova (vertikal), nomi va annotatsiya (uz/ru/en), muallif, nashriyot,
 * yil, betlar, ISBN, til, yo'nalish, PDF fayl va/yoki havola, "Tavsiya etilgan", "Faol", tartib.
 */
const props = defineProps<{
    book: AdminBook | null;
    storeUrl: string;
    subjects: { value: number; label: string }[];
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    title: Translated;
    description: Translated;
    subject_id: number | '';
    author: string;
    publisher: string;
    year: number | '';
    pages: number | '';
    isbn: string;
    language: LibraryLanguage;
    url: string;
    cover: File | null;
    remove_cover: boolean;
    file: File | null;
    remove_file: boolean;
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
};

const blank = (): Form => ({
    title: { uz: '', ru: '', en: '' },
    description: { uz: '', ru: '', en: '' },
    subject_id: '',
    author: '',
    publisher: '',
    year: '',
    pages: '',
    isbn: '',
    language: 'uz',
    url: '',
    cover: null,
    remove_cover: false,
    file: null,
    remove_file: false,
    is_featured: false,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());
const maxYear = new Date().getFullYear() + 1;
const fileInput = ref<HTMLInputElement | null>(null);

const languages = computed(() => [
    { value: 'uz' as const, label: t("O'zbekcha") },
    { value: 'ru' as const, label: t('Ruscha') },
    { value: 'en' as const, label: t('Inglizcha') },
]);

watch(open, (value) => {
    if (!value) {
        return;
    }

    const b = props.book;
    form.defaults(
        b
            ? {
                  title: { ...b.translations.title },
                  description: { ...b.translations.description },
                  subject_id: b.subjectId ?? '',
                  author: b.author,
                  publisher: b.publisher ?? '',
                  year: b.year ?? '',
                  pages: b.pages ?? '',
                  isbn: b.isbn ?? '',
                  language: b.language,
                  url: b.url ?? '',
                  cover: null,
                  remove_cover: false,
                  file: null,
                  remove_file: false,
                  is_featured: b.isFeatured,
                  is_active: b.isActive,
                  sort_order: b.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);

/** Joriy PDF: yangi tanlangan fayl → saqlangan fayl (o'chirish belgilanmagan bo'lsa) */
const currentFile = computed(() => {
    if (form.file) {
        return { name: form.file.name, size: form.file.size, url: null };
    }

    if (props.book?.file && !form.remove_file) {
        return props.book.file;
    }

    return null;
});

function pickFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
    form.remove_file = false;
    input.value = '';
}

function clearFile(): void {
    if (form.file) {
        form.file = null;

        return;
    }

    form.remove_file = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.book) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.book.urls.update,
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
        :title="book ? t('Kitobni tahrirlash') : t('Yangi kitob')"
        :description="
            t(
                'Kitob saytdagi «Kutubxona» bo\'limida ko\'rinadi. «Tavsiya etilgan» belgilanganlari bosh sahifada ham chiqadi.',
            )
        "
        :icon="book ? PenLine : BookPlus"
        :confirm-text="book ? t('Saqlash') : t('Qo\'shish')"
        :processing="form.processing"
        size="xl"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[12rem_minmax(0,1fr)]">
            <div class="flex flex-col gap-4">
                <ImagePicker
                    v-model:file="form.cover"
                    v-model:removed="form.remove_cover"
                    :current-url="book?.coverUrl ?? null"
                    :error="errors.cover"
                    aspect-class="aspect-[3/4]"
                    :hint="t('Muqova · kamida 150×200')"
                    :prepare="{ maxSide: 1000, maxBytes: 600_000 }"
                />

                <!-- PDF fayl -->
                <div>
                    <p class="mb-1.5 text-xs font-semibold text-navy-700">
                        {{ t('PDF fayl') }}
                    </p>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="application/pdf,.pdf"
                        class="hidden"
                        @change="pickFile"
                    />
                    <div
                        v-if="currentFile"
                        class="flex items-start gap-2 rounded-lg border border-line bg-[#fafbfd] p-2.5"
                    >
                        <FileText class="mt-0.5 size-4 shrink-0 text-red-600" />
                        <span class="min-w-0 flex-1">
                            <component
                                :is="currentFile.url ? 'a' : 'span'"
                                :href="currentFile.url ?? undefined"
                                target="_blank"
                                rel="noopener"
                                class="block truncate text-xs font-semibold text-navy-800 hover:text-brand-700"
                                >{{ currentFile.name ?? 'PDF' }}</component
                            >
                            <span
                                v-if="currentFile.size"
                                class="block text-[11px] text-navy-400"
                                >{{ formatFileSize(currentFile.size) }}</span
                            >
                        </span>
                        <button
                            type="button"
                            class="rounded-md p-1 text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600"
                            :aria-label="t('Faylni olib tashlash')"
                            @click="clearFile"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                    <button
                        v-else
                        type="button"
                        class="flex w-full flex-col items-center gap-1 rounded-lg border border-dashed border-navy-200 px-3 py-4 text-center text-xs text-navy-500 transition-all hover:-translate-y-px hover:border-brand-300 hover:bg-brand-50/40 hover:text-brand-700"
                        @click="fileInput?.click()"
                    >
                        <FileUp class="size-5" />
                        <span class="font-semibold">{{
                            t('PDF yuklash')
                        }}</span>
                        <span class="text-[11px] text-navy-400">{{
                            t('100 MB gacha')
                        }}</span>
                    </button>
                    <button
                        v-if="form.remove_file && book?.file"
                        type="button"
                        class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 hover:underline"
                        @click="form.remove_file = false"
                    >
                        <Undo2 class="size-3" /> {{ t('Bekor qilish') }}
                    </button>
                    <p v-if="errors.file" class="mt-1 text-xs text-red-600">
                        {{ errors.file }}
                    </p>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 content-start gap-4">
                <TranslatableField
                    v-model="form.title"
                    :label="t('Kitob nomi')"
                    field="title"
                    :errors="errors"
                    required
                    :maxlength="255"
                />
                <TranslatableField
                    v-model="form.description"
                    :label="t('Annotatsiya')"
                    field="description"
                    :errors="errors"
                    multiline
                    :rows="4"
                    :maxlength="5000"
                    :placeholder="
                        t('Kitob haqida qisqacha: mavzusi, kimlar uchun…')
                    "
                />

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <FormField
                        :label="t('Muallif')"
                        for="book-author"
                        :error="errors.author"
                        required
                    >
                        <input
                            id="book-author"
                            v-model="form.author"
                            type="text"
                            maxlength="255"
                            :class="inputClass"
                            placeholder="A. Karimov"
                        />
                    </FormField>
                    <FormField
                        :label="t('Nashriyot')"
                        for="book-publisher"
                        :error="errors.publisher"
                    >
                        <input
                            id="book-publisher"
                            v-model="form.publisher"
                            type="text"
                            maxlength="255"
                            :class="inputClass"
                        />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <FormField
                        :label="t('Yili')"
                        for="book-year"
                        :error="errors.year"
                    >
                        <input
                            id="book-year"
                            v-model.number="form.year"
                            type="number"
                            min="1800"
                            :max="maxYear"
                            :class="cn(inputClass, 'tabular-nums')"
                            placeholder="2024"
                        />
                    </FormField>
                    <FormField
                        :label="t('Betlar')"
                        for="book-pages"
                        :error="errors.pages"
                    >
                        <input
                            id="book-pages"
                            v-model.number="form.pages"
                            type="number"
                            min="1"
                            :class="cn(inputClass, 'tabular-nums')"
                        />
                    </FormField>
                    <FormField
                        label="ISBN"
                        for="book-isbn"
                        :error="errors.isbn"
                    >
                        <input
                            id="book-isbn"
                            v-model.trim="form.isbn"
                            type="text"
                            maxlength="20"
                            :class="cn(inputClass, 'tabular-nums')"
                            placeholder="978-9943-…"
                        />
                    </FormField>
                    <FormField
                        :label="t('Til')"
                        for="book-language"
                        :error="errors.language"
                    >
                        <SelectInput id="book-language" v-model="form.language">
                            <option
                                v-for="lang in languages"
                                :key="lang.value"
                                :value="lang.value"
                            >
                                {{ lang.label }}
                            </option>
                        </SelectInput>
                    </FormField>
                </div>

                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_6rem]"
                >
                    <FormField
                        :label="t('Yo\'nalish')"
                        for="book-subject"
                        :error="errors.subject_id"
                    >
                        <SelectInput
                            id="book-subject"
                            v-model="form.subject_id"
                        >
                            <option value="">{{ t('Tanlanmagan') }}</option>
                            <option
                                v-for="subject in subjects"
                                :key="subject.value"
                                :value="subject.value"
                            >
                                {{ subject.label }}
                            </option>
                        </SelectInput>
                    </FormField>
                    <FormField
                        :label="t('Havola')"
                        for="book-url"
                        :error="errors.url"
                        :hint="t('Tashqi sahifa (ixtiyoriy)')"
                    >
                        <input
                            id="book-url"
                            v-model.trim="form.url"
                            type="text"
                            maxlength="500"
                            :class="inputClass"
                            placeholder="https://…"
                        />
                    </FormField>
                    <FormField
                        :label="t('Tartib')"
                        for="book-order"
                        :error="errors.sort_order"
                    >
                        <input
                            id="book-order"
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            :class="cn(inputClass, 'tabular-nums')"
                        />
                    </FormField>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <CheckCard
                        v-model="form.is_featured"
                        :label="t('Tavsiya etilgan')"
                        :hint="
                            t(
                                'Bosh sahifadagi «Tavsiya etilgan kitoblar» blokida',
                            )
                        "
                        :icon="Star"
                    />
                    <CheckCard
                        v-model="form.is_active"
                        :label="t('Faol')"
                        :hint="t('Saytdagi kutubxonada ko\'rinadi')"
                        :icon="Eye"
                    />
                </div>
            </div>
        </div>
    </ActionDialog>
</template>

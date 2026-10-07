<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BookPlus, Eye, PenLine } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { SettingsBook, Translated } from '@/types';
import CheckCard from './CheckCard.vue';
import ImagePicker from './ImagePicker.vue';
import TranslatableField from './TranslatableField.vue';

/** Tavsiya etilgan kitob: muqova (vertikal), nomi, muallif, yil, havola, tartib */
const props = defineProps<{
    book: SettingsBook | null;
    storeUrl: string;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    title: Translated;
    author: string;
    year: number | '';
    url: string;
    cover: File | null;
    remove_cover: boolean;
    is_active: boolean;
    sort_order: number;
};

const blank = (): Form => ({
    title: { uz: '', ru: '', en: '' },
    author: '',
    year: '',
    url: '',
    cover: null,
    remove_cover: false,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());
const maxYear = new Date().getFullYear() + 1;

watch(open, (value) => {
    if (!value) {
        return;
    }

    const b = props.book;
    form.defaults(
        b
            ? {
                  title: { ...b.translations.title },
                  author: b.author,
                  year: b.year ?? '',
                  url: b.url ?? '',
                  cover: null,
                  remove_cover: false,
                  is_active: b.isActive,
                  sort_order: b.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);

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
        :title="book ? 'Kitobni tahrirlash' : 'Yangi kitob'"
        description="Bosh sahifaning o'ng ustunidagi «Tavsiya etilgan kitoblar» blokida ko'rinadi."
        :icon="book ? PenLine : BookPlus"
        :confirm-text="book ? 'Saqlash' : 'Qo\'shish'"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-[10rem_minmax(0,1fr)]">
            <ImagePicker
                v-model:file="form.cover"
                v-model:removed="form.remove_cover"
                :current-url="book?.coverUrl ?? null"
                :error="errors.cover"
                aspect-class="aspect-[3/4]"
                hint="Muqova · kamida 150×200"
                :prepare="{ maxSide: 1000, maxBytes: 600_000 }"
            />

            <div class="grid min-w-0 grid-cols-1 content-start gap-4">
                <TranslatableField
                    v-model="form.title"
                    label="Kitob nomi"
                    field="title"
                    :errors="errors"
                    required
                    :maxlength="255"
                />
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_7rem]"
                >
                    <FormField
                        label="Muallif"
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
                        label="Yili"
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
                </div>
            </div>
        </div>

        <div
            class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_7rem]"
        >
            <FormField
                label="Havola"
                for="book-url"
                :error="errors.url"
                hint="Kitob sahifasi yoki PDF: https://… yoki /…"
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
                label="Tartib"
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
        <div class="mt-4">
            <CheckCard
                v-model="form.is_active"
                label="Faol"
                hint="Bosh sahifada birinchi 3 ta faol kitob ko'rsatiladi"
                :icon="Eye"
            />
        </div>
    </ActionDialog>
</template>

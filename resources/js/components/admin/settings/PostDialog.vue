<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Eye,
    Megaphone,
    Newspaper,
    PenLine,
    Pin,
    SquarePen,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { SettingsPost, Translated } from '@/types';
import CheckCard from './CheckCard.vue';
import ImagePicker from './ImagePicker.vue';
import TranslatableField from './TranslatableField.vue';

/**
 * Yangilik yoki e'lon: tur, rasm, sarlavha, qisqa mazmun, matn (xatboshilar bo'sh qator bilan),
 * nashr holati va sanasi. Kelajakdagi sana — rejalashtirilgan nashr.
 */
const props = defineProps<{
    post: SettingsPost | null;
    storeUrl: string;
    defaultType?: 'news' | 'announcement';
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    type: 'news' | 'announcement';
    title: Translated;
    excerpt: Translated;
    body: Translated;
    image: File | null;
    remove_image: boolean;
    is_published: boolean;
    is_pinned: boolean;
    published_at: string;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    type: props.defaultType ?? 'news',
    title: empty(),
    excerpt: empty(),
    body: empty(),
    image: null,
    remove_image: false,
    is_published: true,
    is_pinned: false,
    published_at: '',
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const p = props.post;
    form.defaults(
        p
            ? {
                  type: p.type,
                  title: { ...p.translations.title },
                  excerpt: { ...p.translations.excerpt },
                  body: { ...p.translations.body },
                  image: null,
                  remove_image: false,
                  is_published: p.isPublished,
                  is_pinned: p.isPinned,
                  published_at: p.publishedAt ?? '',
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);

const types = [
    { value: 'news', label: 'Yangilik', icon: Newspaper },
    { value: 'announcement', label: "E'lon", icon: Megaphone },
] as const;

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.post) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.post.urls.update,
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
        :title="post ? 'Xabarni tahrirlash' : 'Yangi xabar'"
        description="Saytdagi «Yangiliklar» bo'limida va bosh sahifada ko'rinadi."
        :icon="post ? PenLine : SquarePen"
        :confirm-text="post ? 'Saqlash' : 'Qo\'shish'"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4">
            <div
                class="grid grid-cols-2 gap-1 rounded-xl bg-[#eef3fa] p-1"
                role="radiogroup"
                aria-label="Turi"
            >
                <button
                    v-for="item in types"
                    :key="item.value"
                    type="button"
                    role="radio"
                    :aria-checked="form.type === item.value"
                    :class="
                        cn(
                            'inline-flex h-9 items-center justify-center gap-2 rounded-lg text-[13px] font-semibold transition-all',
                            form.type === item.value
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-navy-500 hover:text-navy-800',
                        )
                    "
                    @click="form.type = item.value"
                >
                    <component :is="item.icon" class="size-4" />
                    {{ item.label }}
                </button>
            </div>

            <ImagePicker
                v-model:file="form.image"
                v-model:removed="form.remove_image"
                :current-url="post?.imageUrl ?? null"
                :error="errors.image"
                aspect-class="aspect-[16/6]"
                hint="JPG, PNG, WEBP · kamida 600×300 · tavsiya 1600×800"
            />

            <TranslatableField
                v-model="form.title"
                label="Sarlavha"
                field="title"
                :errors="errors"
                required
                :maxlength="255"
            />
            <TranslatableField
                v-model="form.excerpt"
                label="Qisqa mazmun"
                field="excerpt"
                :errors="errors"
                multiline
                :rows="2"
                :maxlength="500"
                placeholder="Ro'yxatda sarlavha ostida chiqadi"
            />
            <TranslatableField
                v-model="form.body"
                label="To'liq matn"
                field="body"
                :errors="errors"
                multiline
                :rows="8"
                :maxlength="20000"
                placeholder="Xatboshilarni bo'sh qator bilan ajrating"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <CheckCard
                    v-model="form.is_published"
                    label="Chop etilgan"
                    hint="Belgilanmasa — qoralama (saytda ko'rinmaydi)"
                    :icon="Eye"
                />
                <CheckCard
                    v-model="form.is_pinned"
                    label="Qadab qo'yish"
                    hint="Ro'yxat boshida turadi"
                    :icon="Pin"
                />
            </div>
            <FormField
                label="Nashr sanasi va vaqti"
                for="post-date"
                :error="errors.published_at"
                hint="Bo'sh qoldirilsa — saqlangan vaqt. Kelajakdagi sana — rejalashtirilgan nashr."
            >
                <input
                    id="post-date"
                    v-model="form.published_at"
                    type="datetime-local"
                    :class="cn(inputClass, 'sm:max-w-64')"
                />
            </FormField>
        </div>
    </ActionDialog>
</template>

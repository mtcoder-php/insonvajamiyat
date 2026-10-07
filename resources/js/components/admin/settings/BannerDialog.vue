<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlus, LoaderCircle, PenLine } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { prepareImage } from '@/lib/image';
import { cn } from '@/lib/utils';
import type { SettingsBanner, Translated } from '@/types';
import TranslatableField from './TranslatableField.vue';

/**
 * Bosh sahifa banneri: rasm (kamida 1200×400, keng format), sarlavha, izoh, tugma, havola, muddat.
 * Katta rasm brauzerda 2400 px gacha kichraytiriladi.
 */
const props = defineProps<{
    banner: SettingsBanner | null;
    storeUrl: string;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    title: Translated;
    subtitle: Translated;
    button_text: Translated;
    link_url: string;
    image: File | null;
    is_active: boolean;
    sort_order: number;
    starts_at: string;
    ends_at: string;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    title: empty(),
    subtitle: empty(),
    button_text: empty(),
    link_url: '',
    image: null,
    is_active: true,
    sort_order: 0,
    starts_at: '',
    ends_at: '',
});

const form = useForm<Form>(blank());
const preview = ref<string | null>(null);
const preparing = ref(false);
const input = ref<HTMLInputElement | null>(null);

function revoke(): void {
    if (preview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(preview.value);
    }
}

watch(open, (value) => {
    if (!value) {
        return;
    }

    const b = props.banner;
    form.defaults(
        b
            ? {
                  title: { ...b.translations.title },
                  subtitle: { ...b.translations.subtitle },
                  button_text: { ...b.translations.button_text },
                  link_url: b.linkUrl ?? '',
                  image: null,
                  is_active: b.isActive,
                  sort_order: b.sortOrder,
                  starts_at: b.startsAt ?? '',
                  ends_at: b.endsAt ?? '',
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
    revoke();
    preview.value = b?.imageUrl ?? null;
});

onBeforeUnmount(revoke);

async function pick(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    preparing.value = true;
    const prepared = await prepareImage(file, {
        maxSide: 2400,
        maxBytes: 2_500_000,
    });
    preparing.value = false;

    form.image = prepared;
    revoke();
    preview.value = URL.createObjectURL(prepared);
}

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.banner) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.banner.urls.update,
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
        :title="banner ? 'Bannerni tahrirlash' : 'Yangi banner'"
        description="Bosh sahifadagi slayderda ko'rinadi. Faol bannerlar bo'lsa, standart slaydlar o'rnini egallaydi."
        :icon="banner ? PenLine : ImagePlus"
        :confirm-text="banner ? 'Saqlash' : 'Qo\'shish'"
        :processing="form.processing || preparing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <div>
                <button
                    type="button"
                    :class="
                        cn(
                            'group relative flex aspect-[16/6] w-full items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition-colors',
                            errors.image
                                ? 'border-red-300 bg-red-50/40'
                                : 'border-line bg-[#fafcff] hover:border-brand-300',
                        )
                    "
                    @click="input?.click()"
                >
                    <img
                        v-if="preview"
                        :src="preview"
                        alt=""
                        class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <span
                        :class="
                            cn(
                                'relative flex flex-col items-center gap-1.5 rounded-lg px-4 py-3 text-center text-xs',
                                preview
                                    ? 'bg-navy-950/60 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100'
                                    : 'text-navy-500',
                            )
                        "
                    >
                        <LoaderCircle
                            v-if="preparing"
                            class="size-6 animate-spin"
                        />
                        <ImagePlus v-else class="size-6" />
                        <span class="font-semibold">{{
                            preview ? 'Rasmni almashtirish' : 'Rasm tanlang'
                        }}</span>
                        <span
                            >JPG, PNG, WEBP · kamida 1200×400 · tavsiya
                            1920×720</span
                        >
                    </span>
                </button>
                <p
                    v-if="errors.image"
                    class="mt-1 text-xs font-medium text-red-600"
                >
                    {{ errors.image }}
                </p>
                <input
                    ref="input"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="pick"
                />
            </div>

            <TranslatableField
                v-model="form.title"
                label="Sarlavha"
                field="title"
                :errors="errors"
                required
                :maxlength="200"
            />
            <TranslatableField
                v-model="form.subtitle"
                label="Izoh"
                field="subtitle"
                :errors="errors"
                multiline
                :maxlength="400"
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <TranslatableField
                    v-model="form.button_text"
                    label="Tugma matni"
                    field="button_text"
                    :errors="errors"
                    :maxlength="40"
                    placeholder="Batafsil"
                />
                <FormField
                    label="Havola"
                    for="banner-link"
                    :error="errors.link_url"
                    hint="https://… yoki /articles"
                >
                    <input
                        id="banner-link"
                        v-model.trim="form.link_url"
                        type="text"
                        maxlength="500"
                        :class="inputClass"
                        placeholder="/articles"
                    />
                </FormField>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <FormField
                    label="Ko'rsatish boshlanishi"
                    for="banner-from"
                    :error="errors.starts_at"
                >
                    <input
                        id="banner-from"
                        v-model="form.starts_at"
                        type="date"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    label="Tugashi"
                    for="banner-to"
                    :error="errors.ends_at"
                >
                    <input
                        id="banner-to"
                        v-model="form.ends_at"
                        type="date"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    label="Tartib"
                    for="banner-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="banner-order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <label
                class="flex cursor-pointer items-center gap-2 text-[13px] text-navy-800"
            >
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="size-4 accent-brand-600"
                />
                Faol (muddat bo'sh bo'lsa — doim ko'rinadi)
            </label>
        </div>
    </ActionDialog>
</template>

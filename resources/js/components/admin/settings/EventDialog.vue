<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarPlus, Eye, PenLine } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import type { SettingsEvent, Translated } from '@/types';
import CheckCard from './CheckCard.vue';
import ImagePicker from './ImagePicker.vue';
import TranslatableField from './TranslatableField.vue';

/**
 * Tadbir: nomi, joyi, vaqti (boshlanish / tugash), ro'yxatdan o'tish havolasi, rasm, tavsif.
 */
const props = defineProps<{
    event: SettingsEvent | null;
    storeUrl: string;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    title: Translated;
    description: Translated;
    location: Translated;
    starts_at: string;
    ends_at: string;
    registration_url: string;
    image: File | null;
    remove_image: boolean;
    is_published: boolean;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    title: empty(),
    description: empty(),
    location: empty(),
    starts_at: '',
    ends_at: '',
    registration_url: '',
    image: null,
    remove_image: false,
    is_published: true,
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const e = props.event;
    form.defaults(
        e
            ? {
                  title: { ...e.translations.title },
                  description: { ...e.translations.description },
                  location: { ...e.translations.location },
                  starts_at: e.startsAt,
                  ends_at: e.endsAt ?? '',
                  registration_url: e.registrationUrl ?? '',
                  image: null,
                  remove_image: false,
                  is_published: e.isPublished,
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

    if (props.event) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.event.urls.update,
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
        :title="event ? 'Tadbirni tahrirlash' : 'Yangi tadbir'"
        description="Saytdagi «Tadbirlar» sahifasida va bosh sahifada (kelgusi tadbirlar) ko'rinadi."
        :icon="event ? PenLine : CalendarPlus"
        :confirm-text="event ? 'Saqlash' : 'Qo\'shish'"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4">
            <TranslatableField
                v-model="form.title"
                label="Tadbir nomi"
                field="title"
                :errors="errors"
                required
                :maxlength="255"
                placeholder="Xalqaro ilmiy-amaliy konferensiya"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <FormField
                    label="Boshlanishi"
                    for="event-start"
                    :error="errors.starts_at"
                    required
                >
                    <input
                        id="event-start"
                        v-model="form.starts_at"
                        type="datetime-local"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    label="Tugashi"
                    for="event-end"
                    :error="errors.ends_at"
                    hint="Bir kunlik tadbir uchun bo'sh qoldiring"
                >
                    <input
                        id="event-end"
                        v-model="form.ends_at"
                        type="datetime-local"
                        :min="form.starts_at || undefined"
                        :class="inputClass"
                    />
                </FormField>
            </div>

            <TranslatableField
                v-model="form.location"
                label="O'tkaziladigan joy"
                field="location"
                :errors="errors"
                :maxlength="255"
                placeholder="Toshkent, O'zMU yoki Onlayn (Zoom)"
            />

            <FormField
                label="Ro'yxatdan o'tish havolasi"
                for="event-reg"
                :error="errors.registration_url"
                hint="https://… (Google Forms, sayt sahifasi)"
            >
                <input
                    id="event-reg"
                    v-model.trim="form.registration_url"
                    type="url"
                    maxlength="500"
                    :class="inputClass"
                    placeholder="https://forms.gle/…"
                />
            </FormField>

            <ImagePicker
                v-model:file="form.image"
                v-model:removed="form.remove_image"
                :current-url="event?.imageUrl ?? null"
                :error="errors.image"
                aspect-class="aspect-[16/6]"
                hint="Afisha yoki rasm · kamida 600×300 · ixtiyoriy"
            />

            <TranslatableField
                v-model="form.description"
                label="Tavsif"
                field="description"
                :errors="errors"
                multiline
                :rows="6"
                :maxlength="5000"
                placeholder="Dastur, sho'balar, talablar… Xatboshilarni bo'sh qator bilan ajrating"
            />

            <CheckCard
                v-model="form.is_published"
                label="Saytda ko'rsatish"
                hint="Belgilanmasa — faqat admin panelda ko'rinadi"
                :icon="Eye"
            />
        </div>
    </ActionDialog>
</template>

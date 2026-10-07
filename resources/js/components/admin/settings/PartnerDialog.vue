<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Database, Eye, Handshake, PenLine } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { SettingsPartner, SettingsPartnerType, Translated } from '@/types';
import CheckCard from './CheckCard.vue';
import ImagePicker from './ImagePicker.vue';
import TranslatableField from './TranslatableField.vue';

/** Hamkor tashkilot yoki indekslash bazasi: tur, logo (shaffof PNG tavsiya), nom, izoh, sayt */
const props = defineProps<{
    partner: SettingsPartner | null;
    storeUrl: string;
    types: { value: SettingsPartnerType; label: string }[];
    defaultType?: SettingsPartnerType;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    type: SettingsPartnerType;
    name: Translated;
    subtitle: Translated;
    url: string;
    logo: File | null;
    remove_logo: boolean;
    is_active: boolean;
    sort_order: number;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    type: props.defaultType ?? 'partner',
    name: empty(),
    subtitle: empty(),
    url: '',
    logo: null,
    remove_logo: false,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const p = props.partner;
    form.defaults(
        p
            ? {
                  type: p.type,
                  name: { ...p.translations.name },
                  subtitle: { ...p.translations.subtitle },
                  url: p.url ?? '',
                  logo: null,
                  remove_logo: false,
                  is_active: p.isActive,
                  sort_order: p.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);
const icons = { partner: Handshake, indexing: Database };

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.partner) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.partner.urls.update,
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
        :title="partner ? 'Hamkorni tahrirlash' : 'Yangi hamkor'"
        description="Bosh sahifa pastidagi «Hamkorlar va indekslash bazalari» qatorida ko'rinadi."
        :icon="partner ? PenLine : Handshake"
        :confirm-text="partner ? 'Saqlash' : 'Qo\'shish'"
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
                    <component :is="icons[item.value]" class="size-4" />
                    {{ item.label }}
                </button>
            </div>

            <div
                class="grid grid-cols-1 gap-4 sm:grid-cols-[12rem_minmax(0,1fr)]"
            >
                <ImagePicker
                    v-model:file="form.logo"
                    v-model:removed="form.remove_logo"
                    :current-url="partner?.logoUrl ?? null"
                    :error="errors.logo"
                    aspect-class="aspect-[3/2]"
                    fit="contain"
                    :prepare="null"
                    hint="Logo · PNG/WEBP · 2 MB gacha"
                />
                <div class="grid min-w-0 grid-cols-1 content-start gap-4">
                    <TranslatableField
                        v-model="form.name"
                        label="Nomi"
                        field="name"
                        :errors="errors"
                        required
                        :maxlength="150"
                        :placeholder="
                            form.type === 'indexing'
                                ? 'Google Scholar'
                                : 'O\'zbekiston Milliy universiteti'
                        "
                    />
                    <TranslatableField
                        v-model="form.subtitle"
                        label="Izoh"
                        field="subtitle"
                        :errors="errors"
                        :maxlength="150"
                        :placeholder="
                            form.type === 'indexing'
                                ? 'Indekslangan'
                                : 'Hamkorlik memorandumi'
                        "
                    />
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_7rem]"
            >
                <FormField
                    label="Sayt manzili"
                    for="partner-url"
                    :error="errors.url"
                    hint="https://…"
                >
                    <input
                        id="partner-url"
                        v-model.trim="form.url"
                        type="url"
                        maxlength="500"
                        :class="inputClass"
                        placeholder="https://scholar.google.com/…"
                    />
                </FormField>
                <FormField
                    label="Tartib"
                    for="partner-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="partner-order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <CheckCard
                v-model="form.is_active"
                label="Faol"
                hint="Belgilanmasa — saytda ko'rinmaydi"
                :icon="Eye"
            />
        </div>
    </ActionDialog>
</template>

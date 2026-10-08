<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BadgeDollarSign, FilePlus2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { SettingsArticleType, Translated } from '@/types';
import TranslatableField from './TranslatableField.vue';
import { t } from '@/lib/i18n';

/** Maqola turi va nashr narxi (0 — bepul) */
const props = defineProps<{
    type: SettingsArticleType | null;
    storeUrl: string;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    name: Translated;
    description: Translated;
    price: number;
    review_days: number | null;
    is_active: boolean;
    sort_order: number;
};

const blank = (): Form => ({
    name: { uz: '', ru: '', en: '' },
    description: { uz: '', ru: '', en: '' },
    price: 0,
    review_days: null,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const t = props.type;
    form.defaults(
        t
            ? {
                  name: { ...t.translations.name },
                  description: { ...t.translations.description },
                  price: t.price,
                  review_days: t.reviewDays,
                  is_active: t.isActive,
                  sort_order: t.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);
const priceChanged = computed(
    () => props.type !== null && Number(form.price) !== props.type.price,
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.type) {
        form.put(props.type.urls.update, options);
    } else {
        form.post(props.storeUrl, options);
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="type ? t('Maqola turini tahrirlash') : t('Yangi maqola turi')"
        :description="
            t(
                'Muallif maqola yuborishda turni tanlaydi; narx nashr to\'lovi summasi bo\'ladi.',
            )
        "
        :icon="type ? BadgeDollarSign : FilePlus2"
        :confirm-text="type ? t('Saqlash') : t('Qo\'shish')"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <TranslatableField
                v-model="form.name"
                :label="t('Nomi')"
                field="name"
                :errors="errors"
                required
                :maxlength="150"
                :placeholder="t('Masalan: Ilmiy maqola')"
            />
            <TranslatableField
                v-model="form.description"
                :label="t('Tavsif')"
                field="description"
                :errors="errors"
                multiline
                :maxlength="1000"
                :placeholder="t('Hajmi, talablar, muddat…')"
            />
            <div class="grid gap-3 sm:grid-cols-3">
                <FormField
                    :label="t('Narx (so\'m)')"
                    for="type-price"
                    required
                    :error="errors.price"
                    :hint="
                        Number(form.price) > 0
                            ? formatSum(Number(form.price))
                            : t('Bepul — to\'lovsiz')
                    "
                >
                    <input
                        id="type-price"
                        v-model.number="form.price"
                        type="number"
                        min="0"
                        step="1000"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('Ko\'rib chiqish (kun)')"
                    for="type-days"
                    :error="errors.review_days"
                    :hint="t('Taxminiy muddat')"
                >
                    <input
                        id="type-days"
                        v-model.number="form.review_days"
                        type="number"
                        min="1"
                        max="365"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('Tartib')"
                    for="type-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="type-order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <p
                v-if="priceChanged"
                class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
            >
                {{
                    t(
                        "Yangi narx faqat bundan keyin yuboriladigan maqolalarga tatbiq etiladi; o'zgarish audit logga yoziladi.",
                    )
                }}
            </p>
            <label
                class="flex cursor-pointer items-center gap-2 text-[13px] text-navy-800"
            >
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="size-4 accent-brand-600"
                />
                {{ t('Faol (maqola yuborish formasida tanlash mumkin)') }}
            </label>
        </div>
    </ActionDialog>
</template>

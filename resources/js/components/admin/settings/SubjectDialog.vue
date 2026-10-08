<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FolderPen, FolderPlus } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { SettingsSubject, Translated } from '@/types';
import TranslatableField from './TranslatableField.vue';
import { t } from '@/lib/i18n';

/** Ilmiy yo'nalish qo'shish / tahrirlash */
const props = defineProps<{
    subject: SettingsSubject | null;
    storeUrl: string;
    parents: SettingsSubject[];
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    name: Translated;
    code: string;
    parent_id: number | null;
    is_active: boolean;
    sort_order: number;
};

const blank = (): Form => ({
    name: { uz: '', ru: '', en: '' },
    code: '',
    parent_id: null,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const s = props.subject;
    form.defaults(
        s
            ? {
                  name: { ...s.translations },
                  code: s.code ?? '',
                  parent_id: s.parentId,
                  is_active: s.isActive,
                  sort_order: s.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);
const parentOptions = computed(() =>
    props.parents.filter(
        (p) => p.parentId === null && p.id !== props.subject?.id,
    ),
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.subject) {
        form.put(props.subject.urls.update, options);
    } else {
        form.post(props.storeUrl, options);
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="subject ? t('Yo\'nalishni tahrirlash') : t('Yangi yo\'nalish')"
        :description="
            t(
                'Saytdagi filtrlar, maqola yuborish formasi va hisobotlarda ko\'rinadi.',
            )
        "
        :icon="subject ? FolderPen : FolderPlus"
        :confirm-text="subject ? t('Saqlash') : t('Qo\'shish')"
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
                :placeholder="t('Masalan: Etnologiya')"
            />
            <div class="grid gap-3 sm:grid-cols-3">
                <FormField
                    :label="t('Shifr (OAK)')"
                    for="subject-code"
                    :error="errors.code"
                    :hint="t('Ixtiyoriy, masalan 07.00.07')"
                >
                    <input
                        id="subject-code"
                        v-model.trim="form.code"
                        type="text"
                        maxlength="30"
                        :class="cn(inputClass, 'font-mono text-[13px]')"
                    />
                </FormField>
                <FormField
                    :label="t('Yuqori yo\'nalish')"
                    for="subject-parent"
                    :error="errors.parent_id"
                >
                    <SelectInput id="subject-parent" v-model="form.parent_id">
                        <option :value="null">{{ t("— Yo'q —") }}</option>
                        <option
                            v-for="p in parentOptions"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.name }}
                        </option>
                    </SelectInput>
                </FormField>
                <FormField
                    :label="t('Tartib')"
                    for="subject-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="subject-order"
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
                {{ t("Faol (saytda va yuborish formasida ko'rinadi)") }}
            </label>
        </div>
    </ActionDialog>
</template>

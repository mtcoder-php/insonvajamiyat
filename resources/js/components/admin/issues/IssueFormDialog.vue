<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BookPlus, PenLine } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { IssueFormData } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Jurnal sonini yaratish yoki tahrirlash: yil, jild, raqam, DOI, nom, tavsif.
 */
const props = defineProps<{
    url: string;
    method: 'post' | 'put';
    initial: IssueFormData;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm<IssueFormData>({ ...props.initial });

watch(open, (value) => {
    if (value) {
        form.defaults({ ...props.initial });
        form.reset();
        form.clearErrors();
    }
});

const errors = computed(() => form.errors as Record<string, string>);
const isCreate = computed(() => props.method === 'post');

function submit(): void {
    form.submit(props.method, props.url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="isCreate ? t('Yangi jurnal soni') : t('Son ma\'lumotlari')"
        :description="
            isCreate
                ? t(
                      'Son qoralama sifatida yaratiladi. Keyin maqolalarni biriktirasiz, muqova va PDF yuklaysiz.',
                  )
                : undefined
        "
        :icon="isCreate ? BookPlus : PenLine"
        :confirm-text="isCreate ? t('Yaratish') : t('Saqlash')"
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <div class="grid grid-cols-3 gap-3">
                <FormField
                    :label="t('Yil')"
                    for="year"
                    required
                    :error="errors.year"
                >
                    <input
                        id="year"
                        v-model.number="form.year"
                        type="number"
                        min="2000"
                        max="2100"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('Jild')"
                    for="volume"
                    :error="errors.volume"
                >
                    <input
                        id="volume"
                        v-model.number="form.volume"
                        type="number"
                        min="1"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('Son №')"
                    for="number"
                    required
                    :error="errors.number"
                >
                    <input
                        id="number"
                        v-model.number="form.number"
                        type="number"
                        min="1"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <FormField
                label="DOI"
                for="issue-doi"
                :hint="t('Masalan: 10.5281/insonvajamiyat.2026.3')"
                :error="errors.doi"
            >
                <input
                    id="issue-doi"
                    v-model.trim="form.doi"
                    type="text"
                    :class="cn(inputClass, 'font-mono text-[13px]')"
                />
            </FormField>
            <FormField
                :label="t('Maxsus nom (ixtiyoriy)')"
                for="issue-title"
                :error="errors.title"
            >
                <input
                    id="issue-title"
                    v-model="form.title"
                    type="text"
                    maxlength="255"
                    :placeholder="
                        t(
                            'Masalan: Amir Temur tavalludining 690 yilligiga bag\'ishlangan son',
                        )
                    "
                    :class="inputClass"
                />
            </FormField>
            <FormField
                :label="t('Tavsif')"
                for="issue-description"
                :error="errors.description"
            >
                <textarea
                    id="issue-description"
                    v-model="form.description"
                    rows="3"
                    maxlength="2000"
                    :class="textareaClass"
                />
            </FormField>
        </div>
    </ActionDialog>
</template>

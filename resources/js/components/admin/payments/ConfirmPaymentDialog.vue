<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BadgeCheck, Paperclip, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AwaitingPaymentItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Nashr to'lovini qo'lda tasdiqlash: summa (maqola turi narxi bilan to'ldiriladi),
 * to'lov sanasi, to'lov hujjati raqami, izoh va kvitansiya.
 */
const props = defineProps<{ article: AwaitingPaymentItem | null }>();

const open = defineModel<boolean>('open', { default: false });

const pad = (n: number): string => String(n).padStart(2, '0');
const nowLocal = (): string => {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const form = useForm<{
    amount: number | string;
    paid_at: string;
    reference: string;
    note: string;
    proof: File | null;
}>({
    amount: '',
    paid_at: nowLocal(),
    reference: '',
    note: '',
    proof: null,
});

const maxDate = ref(nowLocal());

// Servis xatosi (masalan, maqola allaqachon to'langan)
const articleError = computed(
    () => (form.errors as Record<string, string>).article,
);

watch(open, (value) => {
    if (value && props.article) {
        form.clearErrors();
        form.amount = props.article.amountDue;
        form.paid_at = nowLocal();
        form.reference = '';
        form.note = '';
        form.proof = null;
        maxDate.value = nowLocal();
    }
});

function pick(event: Event): void {
    form.proof = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    if (!props.article) {
        return;
    }

    form.post(props.article.urls.confirm, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('To\'lovni tasdiqlash')"
        :description="
            article
                ? t('«:title» — :name. Kutilgan summa: :amount.', {
                      title: article.title,
                      name: article.author.name,
                      amount: formatSum(article.amountDue),
                  })
                : undefined
        "
        :icon="BadgeCheck"
        :confirm-text="t('Tasdiqlash')"
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <p
                v-if="articleError"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ articleError }}
            </p>
            <div class="grid gap-4">
                <FormField
                    :label="t('Summa (so\'m)')"
                    for="pay-amount"
                    required
                    :error="form.errors.amount"
                >
                    <input
                        id="pay-amount"
                        v-model="form.amount"
                        type="number"
                        min="1"
                        step="1"
                        :aria-invalid="!!form.errors.amount"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('To\'lov sanasi')"
                    for="pay-date"
                    required
                    :error="form.errors.paid_at"
                >
                    <input
                        id="pay-date"
                        v-model="form.paid_at"
                        type="datetime-local"
                        :max="maxDate"
                        :aria-invalid="!!form.errors.paid_at"
                        :class="inputClass"
                    />
                </FormField>
            </div>
            <FormField
                :label="t('To\'lov hujjati raqami')"
                for="pay-ref"
                :hint="
                    t(
                        'Bank to\'lov topshiriqnomasi yoki kvitansiya raqami (ixtiyoriy)',
                    )
                "
                :error="form.errors.reference"
            >
                <input
                    id="pay-ref"
                    v-model="form.reference"
                    type="text"
                    maxlength="191"
                    :class="inputClass"
                />
            </FormField>
            <FormField
                :label="t('Izoh')"
                for="pay-note"
                :error="form.errors.note"
            >
                <textarea
                    id="pay-note"
                    v-model="form.note"
                    rows="2"
                    maxlength="1000"
                    :class="cn(textareaClass, 'min-h-16')"
                />
            </FormField>
            <FormField
                :label="t('Kvitansiya')"
                :hint="t('PDF, JPG yoki PNG, 5 MB gacha (ixtiyoriy)')"
                :error="form.errors.proof"
            >
                <div
                    v-if="form.proof"
                    class="flex items-center gap-2 rounded-lg border border-line bg-[#f8fafd] px-3 py-2 text-[13px]"
                >
                    <Paperclip class="size-4 text-navy-400" />
                    <span class="flex-1 truncate text-navy-800">{{
                        form.proof.name
                    }}</span>
                    <button
                        type="button"
                        class="rounded p-0.5 text-navy-400 hover:text-red-600"
                        :aria-label="t('Faylni olib tashlash')"
                        @click="form.proof = null"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <label
                    v-else
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-navy-200 px-3 py-2.5 text-[13px] font-medium text-navy-600 transition-colors hover:border-brand-300 hover:bg-brand-50/50 hover:text-brand-700"
                >
                    <Paperclip class="size-4" />
                    {{ t('Fayl tanlash') }}
                    <input
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="sr-only"
                        @change="pick"
                    />
                </label>
            </FormField>
        </div>
    </ActionDialog>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { HandCoins } from '@lucide/vue';
import { watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { textareaClass } from '@/lib/formStyles';
import type { AwaitingPaymentItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Maqolani nashr to'lovidan ozod qilish (sabab muallifga holat tarixida ko'rinadi).
 */
const props = defineProps<{ article: AwaitingPaymentItem | null }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ reason: '' });

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
    }
});

function submit(): void {
    if (props.article) {
        form.post(props.article.urls.waive, {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('To\'lovdan ozod qilish')"
        :description="
            article
                ? t(
                      '«:title» to\'lovsiz tahririyat navbatiga o\'tadi. Sabab muallifga ko\'rinadi.',
                      { title: article.title },
                  )
                : undefined
        "
        :icon="HandCoins"
        :confirm-text="t('Ozod qilish')"
        :processing="form.processing"
        @confirm="submit"
    >
        <FormField
            :label="t('Sabab')"
            for="waive-reason"
            required
            :error="form.errors.reason"
        >
            <textarea
                id="waive-reason"
                v-model="form.reason"
                rows="3"
                maxlength="500"
                :placeholder="
                    t('Masalan: tahririyat taklifi bilan yozilgan maqola')
                "
                :aria-invalid="!!form.errors.reason"
                :class="textareaClass"
            />
        </FormField>
    </ActionDialog>
</template>

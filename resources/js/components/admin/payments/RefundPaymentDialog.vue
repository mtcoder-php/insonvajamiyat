<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Building2, Info, Undo2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import ProviderBadge from '@/components/admin/payments/ProviderBadge.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { formatSum } from '@/lib/format';
import { t } from '@/lib/i18n';
import type { PaymentListItem } from '@/types';

/**
 * To'lovni qaytarish (TZ 4.1.4): Click — API orqali darhol, Payme — so'rov ochiladi va
 * Payme biznes kabinetida bekor qilinadi, qo'lda tasdiqlangan — bank hujjati raqami bilan.
 */
const props = defineProps<{ payment: PaymentListItem | null }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ reason: '', reference: '', confirm: false });

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
    }
});

const steps = computed(() => {
    switch (props.payment?.provider) {
        case 'click':
            return [
                t("Click Merchant API orqali to'lov darhol bekor qilinadi."),
                t(
                    "Mablag' muallif kartasiga qaytadi (bank muddati 1–10 ish kuni).",
                ),
                t(
                    "Click joriy oy to'lovlarini qaytaradi; o'tgan oy to'lovi — faqat oyning 1-kuni.",
                ),
            ];
        case 'payme':
            return [
                t("Bu yerda qaytarish so'rovi ochiladi."),
                t(
                    'Payme biznes kabinetida (business.payme.uz) shu tranzaksiyani bekor qiling.',
                ),
                t(
                    "Payme serverimizga xabar beradi — to'lov va maqola holati avtomatik yangilanadi.",
                ),
            ];
        default:
            return [
                t("Pulni muallifga bank orqali o'tkazing."),
                t(
                    "To'lov topshiriqnomasi raqamini kiriting — qaytarish darhol yakunlanadi.",
                ),
            ];
    }
});

function submit(): void {
    if (props.payment?.refund) {
        form.post(props.payment.refund.url, {
            preserveScroll: true,
            onSuccess: () => (open.value = false),
        });
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('To\'lovni qaytarish')"
        :description="
            payment
                ? t(':receipt · :user', {
                      receipt: payment.receipt,
                      user: payment.user.name,
                  })
                : undefined
        "
        :icon="Undo2"
        tone="danger"
        :confirm-text="
            payment?.provider === 'payme' ? t('So\'rov ochish') : t('Qaytarish')
        "
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div v-if="payment" class="grid gap-4">
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-[#f8fafd] px-4 py-3"
            >
                <div class="min-w-0">
                    <p class="text-xs text-navy-500">
                        {{ t('Qaytariladigan summa') }}
                    </p>
                    <p class="text-xl font-bold text-navy-950 tabular-nums">
                        {{ formatSum(payment.amount) }}
                    </p>
                    <p
                        v-if="payment.article"
                        class="mt-0.5 line-clamp-1 text-xs text-navy-500"
                    >
                        {{ payment.article.title }}
                    </p>
                </div>
                <ProviderBadge
                    :provider="payment.provider"
                    :label="payment.providerLabel"
                />
            </div>

            <ol
                class="grid gap-2 rounded-xl border border-brand-100 bg-brand-50/50 p-4 text-[13px] text-navy-700"
            >
                <li
                    v-for="(step, index) in steps"
                    :key="index"
                    class="flex items-start gap-2.5"
                >
                    <span
                        class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-bold text-brand-700 ring-1 ring-brand-200"
                        >{{ index + 1 }}</span
                    >
                    {{ step }}
                </li>
            </ol>

            <FormField
                :label="t('Sabab')"
                for="refund-reason"
                required
                :error="form.errors.reason"
                :hint="t('Muallif bilan kelishuv — audit logda saqlanadi')"
            >
                <textarea
                    id="refund-reason"
                    v-model="form.reason"
                    rows="3"
                    maxlength="1000"
                    :placeholder="
                        t(
                            'Masalan: maqola rad etildi, muallif bilan qaytarish kelishildi',
                        )
                    "
                    :aria-invalid="!!form.errors.reason"
                    :class="textareaClass"
                />
            </FormField>

            <FormField
                v-if="payment.provider === 'manual'"
                :label="t('Bank hujjati raqami')"
                for="refund-reference"
                required
                :error="form.errors.reference"
            >
                <div class="relative">
                    <Building2
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        id="refund-reference"
                        v-model.trim="form.reference"
                        maxlength="100"
                        placeholder="TP-2026-0187"
                        :class="[inputClass, 'pl-9']"
                    />
                </div>
            </FormField>

            <label
                class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-red-200 bg-red-50/60 px-4 py-3 text-[13px] text-red-800 transition-colors hover:bg-red-50"
            >
                <input
                    v-model="form.confirm"
                    type="checkbox"
                    class="mt-0.5 size-4 rounded border-red-300 text-red-600 focus:ring-red-200"
                />
                <span>
                    {{
                        t(
                            "Tasdiqlayman: to'lov qaytariladi va bu amalni bekor qilib bo'lmaydi.",
                        )
                    }}
                    <span
                        v-if="form.errors.confirm"
                        class="mt-1 block text-xs font-semibold"
                        >{{ form.errors.confirm }}</span
                    >
                </span>
            </label>

            <p class="flex items-start gap-2 text-xs text-navy-500">
                <Info class="mt-px size-3.5 shrink-0" />
                {{
                    t(
                        "Qaytarilgach maqolaning to'lov holati «Qaytarilgan» bo'ladi va muallifga xabar yuboriladi.",
                    )
                }}
            </p>
        </div>
    </ActionDialog>
</template>

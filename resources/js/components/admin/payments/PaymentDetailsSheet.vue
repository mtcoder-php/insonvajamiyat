<script setup lang="ts">
import { Download, ReceiptText } from '@lucide/vue';
import ProviderBadge from '@/components/admin/payments/ProviderBadge.vue';
import PaymentStatusPill from '@/components/admin/payments/PaymentStatusPill.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { formatDateTime, formatSum } from '@/lib/format';
import type { PaymentListItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "To'lov tafsilotlari" — o'ngdan ochiladigan panel.
 */
defineProps<{ payment: PaymentListItem | null }>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            class="w-full gap-0 overflow-y-auto border-line bg-white p-0 text-navy-900 sm:max-w-md [&>button:last-child]:p-1 [&>button:last-child]:text-white [&>button:last-child]:data-[state=open]:bg-white/10"
        >
            <template v-if="payment">
                <SheetHeader
                    class="border-b border-line bg-navy-950 px-6 py-5 text-left text-white"
                >
                    <p
                        class="flex items-center gap-2 text-xs font-medium tracking-wider text-gold-300 uppercase"
                    >
                        <ReceiptText class="size-4" />
                        {{ t("To'lov tafsilotlari") }}
                    </p>
                    <SheetTitle
                        class="mt-1 font-sans text-xl font-bold text-white tabular-nums"
                    >
                        {{ payment.receipt }}
                    </SheetTitle>
                    <SheetDescription class="text-2xl font-bold text-white">
                        {{ formatSum(payment.amount) }}
                    </SheetDescription>
                </SheetHeader>

                <dl class="grid gap-4 px-6 py-5 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-navy-500">{{ t('Holat') }}</dt>
                        <dd>
                            <PaymentStatusPill
                                :status="payment.status"
                                :label="payment.statusLabel"
                            />
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-navy-500">{{ t("To'lov usuli") }}</dt>
                        <dd>
                            <ProviderBadge
                                :provider="payment.provider"
                                :label="payment.providerLabel"
                            />
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-navy-500">{{ t('Xizmat') }}</dt>
                        <dd class="text-right font-medium">
                            {{ payment.purposeLabel }}
                        </dd>
                    </div>
                    <div v-if="payment.article" class="grid gap-1">
                        <dt class="text-navy-500">{{ t('Maqola') }}</dt>
                        <dd class="font-semibold text-navy-950">
                            {{ payment.article.title }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-navy-500">{{ t("To'lovchi") }}</dt>
                        <dd class="text-right">
                            <span class="block font-medium">{{
                                payment.user.name
                            }}</span>
                            <span class="text-xs text-navy-500">{{
                                payment.user.email
                            }}</span>
                        </dd>
                    </div>
                    <div
                        v-if="payment.reference"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-navy-500">
                            {{ t('Hujjat / tranzaksiya') }}
                        </dt>
                        <dd class="font-mono text-xs">
                            {{ payment.reference }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-navy-500">{{ t("To'langan sana") }}</dt>
                        <dd class="tabular-nums">
                            {{ formatDateTime(payment.paidAt) || '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-navy-500">{{ t('Yaratilgan') }}</dt>
                        <dd class="tabular-nums">
                            {{ formatDateTime(payment.createdAt) }}
                        </dd>
                    </div>
                    <div
                        v-if="payment.confirmedBy"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-navy-500">{{ t('Tasdiqlagan') }}</dt>
                        <dd class="font-medium">{{ payment.confirmedBy }}</dd>
                    </div>
                    <div v-if="payment.note" class="grid gap-1">
                        <dt class="text-navy-500">{{ t('Izoh') }}</dt>
                        <dd
                            class="rounded-lg bg-[#f8fafd] px-3 py-2 leading-relaxed whitespace-pre-line"
                        >
                            {{ payment.note }}
                        </dd>
                    </div>
                    <div v-if="payment.items.length" class="grid gap-1.5">
                        <dt class="text-navy-500">{{ t("To'lov tarkibi") }}</dt>
                        <dd
                            v-for="(item, i) in payment.items"
                            :key="i"
                            class="flex justify-between gap-3 rounded-lg border border-line px-3 py-2"
                        >
                            <span>{{ item.name }} × {{ item.quantity }}</span>
                            <span class="font-semibold tabular-nums">
                                {{ formatSum(item.total) }}
                            </span>
                        </dd>
                    </div>
                </dl>

                <div v-if="payment.proofUrl" class="px-6 pb-6">
                    <a
                        :href="payment.proofUrl"
                        class="group flex items-center justify-center gap-2 rounded-lg border border-line px-4 py-2.5 text-[13px] font-semibold text-navy-800 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-700"
                    >
                        <Download
                            class="size-4 transition-transform group-hover:translate-y-0.5"
                        />
                        {{
                            t('Kvitansiya: :name', { name: payment.proofName })
                        }}
                    </a>
                </div>
            </template>
        </SheetContent>
    </Sheet>
</template>

<script setup lang="ts">
import {
    Check,
    CircleCheck,
    CircleX,
    Copy,
    Eye,
    ScrollText,
    ShieldAlert,
    ShieldCheck,
    ShieldQuestion,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ProviderBadge from '@/components/admin/payments/ProviderBadge.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTime } from '@/lib/format';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { PaymentLogFilter, PaymentLogItem } from '@/types';

/**
 * Admin → To'lovlar → "So'rovlar jurnali": Click / Payme webhook so'rovlari va javoblari.
 * Filtr: hammasi / xato javoblar / imzosi noto'g'ri. "Ko'rish" — so'rov va javob JSON.
 */
const props = defineProps<{
    logs: PaymentLogItem[];
    filter: PaymentLogFilter;
    searching: boolean;
}>();

const emit = defineEmits<{ filter: [value: PaymentLogFilter] }>();

const filters = computed<{ key: PaymentLogFilter; label: string }[]>(() => [
    { key: 'all', label: t('Hammasi') },
    { key: 'errors', label: t('Xato javoblar') },
    { key: 'signature', label: t("Imzo noto'g'ri") },
]);

const selected = ref<PaymentLogItem | null>(null);
const copied = ref<'request' | 'response' | null>(null);

function pretty(value: unknown): string {
    return JSON.stringify(value, null, 2);
}

async function copy(kind: 'request' | 'response'): Promise<void> {
    const value =
        kind === 'request' ? selected.value?.request : selected.value?.response;

    try {
        await navigator.clipboard.writeText(pretty(value));
        copied.value = kind;
        setTimeout(() => (copied.value = null), 1500);
    } catch {
        // Clipboard ruxsati yo'q — e'tiborsiz
    }
}

function signature(log: PaymentLogItem): {
    icon: typeof ShieldCheck;
    class: string;
    label: string;
} {
    if (log.signatureValid === true) {
        return {
            icon: ShieldCheck,
            class: 'text-emerald-600',
            label: t("Imzo to'g'ri"),
        };
    }

    if (log.signatureValid === false) {
        return {
            icon: ShieldAlert,
            class: 'text-red-600',
            label: t("Imzo noto'g'ri"),
        };
    }

    return {
        icon: ShieldQuestion,
        class: 'text-navy-300',
        label: t('Tekshirilmagan'),
    };
}
</script>

<template>
    <div>
        <div
            class="flex flex-wrap items-center gap-2 border-b border-line bg-[#fafbfd] px-5 py-3"
        >
            <button
                v-for="item in filters"
                :key="item.key"
                type="button"
                :class="
                    cn(
                        'rounded-full px-3 py-1 text-xs font-semibold transition-all duration-200',
                        props.filter === item.key
                            ? 'bg-navy-900 text-white shadow-[0_8px_18px_-12px_rgba(0,30,60,0.9)]'
                            : 'bg-white text-navy-600 ring-1 ring-line hover:-translate-y-px hover:text-navy-950 hover:ring-brand-200',
                    )
                "
                @click="emit('filter', item.key)"
            >
                {{ item.label }}
            </button>
            <p class="ml-auto text-[11px] text-navy-400">
                {{
                    t(
                        "Click va Payme'dan kelgan har bir so'rov va tizim javobi. 2 yil saqlanadi.",
                    )
                }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table
                v-if="logs.length"
                class="w-full min-w-[980px] text-left text-[13px]"
            >
                <thead>
                    <tr
                        class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >
                        <th class="py-3 pr-4 pl-5">{{ t('Vaqt') }}</th>
                        <th class="py-3 pr-4">{{ t('Tizim') }}</th>
                        <th class="py-3 pr-4">{{ t('Amal') }}</th>
                        <th class="py-3 pr-4">{{ t('Chek') }}</th>
                        <th class="py-3 pr-4">{{ t('Natija') }}</th>
                        <th class="py-3 pr-4">{{ t('Imzo') }}</th>
                        <th class="py-3 pr-4">IP</th>
                        <th class="py-3 pr-4 text-right">
                            {{ t('Vaqti (ms)') }}
                        </th>
                        <th class="py-3 pr-5 text-right" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr
                        v-for="log in logs"
                        :key="log.id"
                        :class="
                            cn(
                                'group transition-colors hover:bg-brand-50/40',
                                log.signatureValid === false && 'bg-red-50/40',
                            )
                        "
                    >
                        <td
                            class="py-3 pr-4 pl-5 whitespace-nowrap text-navy-700 tabular-nums"
                        >
                            {{ formatDateTime(log.createdAt) }}
                        </td>
                        <td class="py-3 pr-4">
                            <ProviderBadge
                                :provider="log.provider"
                                :label="log.providerLabel"
                            />
                        </td>
                        <td class="py-3 pr-4">
                            <code
                                class="rounded-md bg-navy-50 px-1.5 py-0.5 font-mono text-[12px] text-navy-800"
                                >{{ log.action }}</code
                            >
                        </td>
                        <td class="py-3 pr-4">
                            <p
                                v-if="log.payment"
                                class="font-semibold whitespace-nowrap text-navy-900 tabular-nums"
                            >
                                {{ log.payment.receipt }}
                            </p>
                            <p
                                v-if="log.payment?.transaction"
                                class="max-w-40 truncate font-mono text-[11px] text-navy-400"
                            >
                                {{ log.payment.transaction }}
                            </p>
                            <span v-if="!log.payment" class="text-navy-300"
                                >—</span
                            >
                        </td>
                        <td class="py-3 pr-4">
                            <span
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap',
                                        log.ok
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-red-50 text-red-700',
                                    )
                                "
                            >
                                <CircleCheck v-if="log.ok" class="size-3" />
                                <CircleX v-else class="size-3" />
                                {{
                                    log.ok
                                        ? t('Muvaffaqiyatli')
                                        : t('Xato :code', {
                                              code: log.errorCode ?? '',
                                          })
                                }}
                            </span>
                        </td>
                        <td class="py-3 pr-4">
                            <component
                                :is="signature(log).icon"
                                :class="cn('size-4', signature(log).class)"
                                :aria-label="signature(log).label"
                            />
                        </td>
                        <td
                            class="py-3 pr-4 font-mono text-[12px] whitespace-nowrap text-navy-500"
                        >
                            {{ log.ip ?? '—' }}
                        </td>
                        <td
                            class="py-3 pr-4 text-right text-navy-500 tabular-nums"
                        >
                            {{ log.durationMs ?? '—' }}
                        </td>
                        <td class="py-3 pr-5 text-right">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700 hover:shadow-sm"
                                @click="selected = log"
                            >
                                <Eye class="size-3.5" />
                                {{ t("Ko'rish") }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-else
                class="flex flex-col items-center gap-2 py-14 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-navy-50 text-navy-500"
                >
                    <ScrollText class="size-6" />
                </span>
                <p class="text-sm font-semibold text-navy-900">
                    {{
                        searching || filter !== 'all'
                            ? t("Shartlarga mos so'rov topilmadi")
                            : t("Hali Click yoki Payme'dan so'rov kelmagan")
                    }}
                </p>
            </div>
        </div>

        <Dialog
            :open="selected !== null"
            @update:open="(open: boolean) => !open && (selected = null)"
        >
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader v-if="selected">
                    <DialogTitle class="flex items-center gap-2">
                        <ProviderBadge
                            :provider="selected.provider"
                            :label="selected.providerLabel"
                        />
                        <code class="font-mono text-base">{{
                            selected.action
                        }}</code>
                    </DialogTitle>
                    <DialogDescription>
                        {{ formatDateTime(selected.createdAt) }} ·
                        {{ selected.ip ?? '—' }} ·
                        {{ signature(selected).label }}
                        <template v-if="selected.payment">
                            · {{ selected.payment.receipt }}</template
                        >
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selected" class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="kind in ['request', 'response'] as const"
                        :key="kind"
                        class="min-w-0"
                    >
                        <div class="mb-1.5 flex items-center justify-between">
                            <p
                                class="text-[11px] font-bold tracking-wide text-navy-500 uppercase"
                            >
                                {{
                                    kind === 'request'
                                        ? t("So'rov")
                                        : t('Javob')
                                }}
                            </p>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-semibold text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                @click="copy(kind)"
                            >
                                <Check
                                    v-if="copied === kind"
                                    class="size-3 text-emerald-600"
                                />
                                <Copy v-else class="size-3" />
                                {{
                                    copied === kind
                                        ? t('Nusxalandi')
                                        : t('Nusxalash')
                                }}
                            </button>
                        </div>
                        <pre
                            class="max-h-96 scrollbar-thin overflow-auto rounded-xl bg-navy-950 p-3.5 font-mono text-[12px] leading-relaxed text-gold-200"
                            >{{
                                selected[kind] === null
                                    ? '—'
                                    : pretty(selected[kind])
                            }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { BadgeCheck, Copy, CreditCard, Hourglass } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatDate, formatSum, formatTime } from '@/lib/format';
import type { AuthorArticlePayment } from '@/types';

/**
 * Maqola sahifasidagi nashr to'lovi bloki: kutilayotgan summa va bank rekvizitlari
 * yoki tasdiqlangan to'lov (chek raqami, sana).
 */
const props = defineProps<{ payment: AuthorArticlePayment }>();

const labels: Record<string, string> = {
    recipient: 'Qabul qiluvchi',
    bank: 'Bank',
    account: 'Hisob raqami',
    mfo: 'MFO',
    inn: 'STIR (INN)',
};

const requisites = computed(() =>
    Object.entries(props.payment.requisites).filter(
        (entry): entry is [string, string] => typeof entry[1] === 'string',
    ),
);

const copied = ref<string | null>(null);

async function copy(key: string, value: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;
        setTimeout(() => (copied.value = null), 1500);
    } catch {
        copied.value = null;
    }
}
</script>

<template>
    <section
        v-if="payment.awaiting"
        class="relative isolate overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <h2
            class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <Hourglass class="size-[18px] text-amber-600" />
            Nashr to'lovi kutilmoqda
        </h2>
        <p class="mt-3 font-sans text-2xl font-bold text-navy-950 tabular-nums">
            {{ formatSum(payment.amount) }}
        </p>
        <p class="mt-1 text-xs leading-relaxed text-navy-600">
            To'lov tahririyat tomonidan tasdiqlangach, maqola ko'rib chiqish
            navbatiga qo'shiladi.
        </p>

        <dl v-if="requisites.length" class="mt-4 grid gap-2 text-[13px]">
            <div
                v-for="[key, value] in requisites"
                :key="key"
                class="group flex items-center gap-2 rounded-lg bg-white px-3 py-2 ring-1 ring-line"
            >
                <dt class="w-24 shrink-0 text-[11px] text-navy-500">
                    {{ labels[key] ?? key }}
                </dt>
                <dd class="min-w-0 flex-1 truncate font-medium text-navy-900">
                    {{ value }}
                </dd>
                <button
                    type="button"
                    class="rounded p-1 text-navy-300 transition-colors hover:bg-brand-50 hover:text-brand-600"
                    :aria-label="`${labels[key] ?? key} — nusxalash`"
                    @click="copy(key, value)"
                >
                    <BadgeCheck
                        v-if="copied === key"
                        class="size-3.5 text-emerald-600"
                    />
                    <Copy v-else class="size-3.5" />
                </button>
            </div>
            <div
                v-if="payment.purpose"
                class="rounded-lg bg-white px-3 py-2 ring-1 ring-line"
            >
                <dt class="text-[11px] text-navy-500">To'lov maqsadi</dt>
                <dd class="mt-0.5 font-medium text-navy-900">
                    {{ payment.purpose }}
                </dd>
            </div>
        </dl>
        <p
            v-else
            class="mt-4 flex items-start gap-2 rounded-lg bg-white px-3 py-2.5 text-xs leading-relaxed text-navy-600 ring-1 ring-line"
        >
            <CreditCard class="mt-0.5 size-4 shrink-0 text-navy-400" />
            To'lov tartibi bo'yicha tahririyat bilan bog'laning. Onlayn to'lov
            (Click, Payme) tez orada ishga tushiriladi.
        </p>
    </section>

    <section
        v-else
        class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <h2
            class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <BadgeCheck class="size-[18px] text-emerald-600" />
            Nashr to'lovi
        </h2>
        <dl class="mt-3 grid gap-1.5 text-[13px]">
            <div class="flex justify-between gap-3">
                <dt class="text-navy-500">Summa</dt>
                <dd class="font-semibold text-navy-950 tabular-nums">
                    {{ formatSum(payment.amount) }}
                </dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-navy-500">Holat</dt>
                <dd class="font-medium text-emerald-700">
                    {{ payment.statusLabel }}
                </dd>
            </div>
            <div v-if="payment.receipt" class="flex justify-between gap-3">
                <dt class="text-navy-500">Chek</dt>
                <dd class="font-mono text-xs font-semibold">
                    {{ payment.receipt }}
                </dd>
            </div>
            <div v-if="payment.provider" class="flex justify-between gap-3">
                <dt class="text-navy-500">To'lov usuli</dt>
                <dd>{{ payment.provider }}</dd>
            </div>
            <div v-if="payment.paidAt" class="flex justify-between gap-3">
                <dt class="text-navy-500">Sana</dt>
                <dd class="tabular-nums">
                    {{ formatDate(payment.paidAt) }}
                    {{ formatTime(payment.paidAt) }}
                </dd>
            </div>
        </dl>
    </section>
</template>

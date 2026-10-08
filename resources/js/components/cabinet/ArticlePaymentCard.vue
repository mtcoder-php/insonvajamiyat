<script setup lang="ts">
import { router, usePoll } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BadgeCheck,
    Copy,
    CreditCard,
    Hourglass,
    Landmark,
    LoaderCircle,
    ShieldCheck,
    TriangleAlert,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { formatDate, formatSum, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AuthorArticlePayment } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Maqola sahifasidagi nashr to'lovi bloki:
 *  - kutilayotgan summa, Click / Payme tugmalari va bank rekvizitlari;
 *  - to'lov sahifasidan qaytganda holat avtomatik tekshiriladi (tasdiq kelguncha);
 *  - tasdiqlangan to'lov (chek raqami, usul, sana).
 */
const props = defineProps<{ payment: AuthorArticlePayment }>();

const online = computed(() => props.payment.online);

/** Tizim ranglari — tugmani tanib olish uchun (logotip ishlatilmaydi) */
const brand: Record<string, { ring: string; dot: string; hint: string }> = {
    click: {
        ring: 'hover:border-[#00a0e3] hover:shadow-[0_14px_30px_-16px_rgba(0,160,227,0.75)]',
        dot: 'bg-[#00a0e3]',
        hint: tk('Uzcard · Humo · Click hamyon'),
    },
    payme: {
        ring: 'hover:border-[#00baba] hover:shadow-[0_14px_30px_-16px_rgba(0,186,186,0.75)]',
        dot: 'bg-[#00baba]',
        hint: tk('Uzcard · Humo · Payme ilovasi'),
    },
};

const starting = ref<string | null>(null);
const error = ref<string | null>(null);

function pay(provider: string): void {
    if (!online.value || starting.value) {
        return;
    }

    error.value = null;
    starting.value = provider;

    router.post(
        online.value.payUrl,
        { provider },
        {
            preserveScroll: true,
            onError: (errors) =>
                (error.value =
                    errors.provider ??
                    t(
                        "To'lovni boshlab bo'lmadi. Birozdan keyin qayta urinib ko'ring.",
                    )),
            onFinish: () => (starting.value = null),
        },
    );
}

const cancelledAttempt = computed(() => {
    const attempt = online.value?.lastAttempt;

    return attempt && ['cancelled', 'failed'].includes(attempt.status)
        ? attempt
        : null;
});

/* To'lov tizimidan qaytgach: tasdiq kelguncha har 4 soniyada tekshirish */
const watching = computed(
    () =>
        props.payment.awaiting &&
        !!online.value?.returned &&
        !cancelledAttempt.value,
);

const { start, stop } = usePoll(
    4000,
    { only: ['payment'] },
    { autoStart: false },
);
let deadline: ReturnType<typeof setTimeout> | null = null;
const timedOut = ref(false);

watch(
    watching,
    (active) => {
        if (active && !timedOut.value) {
            start();
            deadline ??= setTimeout(
                () => {
                    timedOut.value = true;
                    stop();
                },
                (online.value?.pollSeconds ?? 120) * 1000,
            );
        } else {
            stop();
        }
    },
    { immediate: true },
);

// To'lov tasdiqlandi — sahifadagi holat, tarix va bosqichlar yangilanadi
watch(
    () => props.payment.awaiting,
    (awaiting, was) => {
        if (was && !awaiting) {
            stop();
            router.reload();
        }
    },
);

onBeforeUnmount(() => {
    stop();

    if (deadline) {
        clearTimeout(deadline);
    }
});

const checking = computed(() => watching.value && !timedOut.value);

const labels: Record<string, string> = {
    recipient: tk('Qabul qiluvchi'),
    bank: tk('Bank'),
    account: tk('Hisob raqami'),
    mfo: 'MFO',
    inn: tk('STIR (INN)'),
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
            {{ t("Nashr to'lovi kutilmoqda") }}
        </h2>
        <p class="mt-3 font-sans text-2xl font-bold text-navy-950 tabular-nums">
            {{ formatSum(payment.amount) }}
        </p>
        <p class="mt-1 text-xs leading-relaxed text-navy-600">
            {{
                online?.providers.length
                    ? "Onlayn to'lovdan so'ng maqola darhol tahririyat navbatiga qo'shiladi."
                    : "To'lov tahririyat tomonidan tasdiqlangach, maqola ko'rib chiqish navbatiga qo'shiladi."
            }}
        </p>

        <!-- To'lov tizimidan tasdiq kutilmoqda -->
        <div
            v-if="checking"
            class="mt-4 flex items-start gap-3 rounded-lg border border-brand-100 bg-brand-50/70 px-3 py-3"
            role="status"
        >
            <LoaderCircle
                class="mt-0.5 size-4 shrink-0 animate-spin text-brand-600"
            />
            <div class="text-xs leading-relaxed text-navy-700">
                <p class="font-semibold text-navy-900">
                    {{ t("To'lov holati tekshirilmoqda…") }}
                </p>
                <p>
                    {{
                        t(
                            "To'lov tizimidan tasdiq kelishi bilan sahifa avtomatik yangilanadi.",
                        )
                    }}
                </p>
            </div>
        </div>
        <div
            v-else-if="watching && timedOut"
            class="mt-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-xs leading-relaxed text-navy-700"
        >
            <Hourglass class="mt-0.5 size-4 shrink-0 text-amber-600" />
            <p>
                {{
                    t(
                        "Tasdiq hali kelmadi. Agar kartangizdan pul yechilgan bo'lsa, bir necha daqiqadan so'ng sahifani yangilang yoki tahririyatga yozing.",
                    )
                }}
            </p>
        </div>
        <div
            v-else-if="online?.processing"
            class="mt-4 flex items-start gap-3 rounded-lg border border-brand-100 bg-brand-50/60 px-3 py-3 text-xs leading-relaxed text-navy-700"
        >
            <Hourglass class="mt-0.5 size-4 shrink-0 text-brand-600" />
            <p>
                {{
                    t(
                        "Tugallanmagan to'lov bor (:provider). Agar to'lagan bo'lsangiz, tasdiq bir necha daqiqada keladi — qayta to'lamang.",
                        { provider: online.lastAttempt?.provider },
                    )
                }}
            </p>
        </div>
        <div
            v-else-if="cancelledAttempt"
            class="mt-4 flex items-start gap-3 rounded-lg border border-red-100 bg-red-50/70 px-3 py-3 text-xs leading-relaxed text-navy-700"
        >
            <TriangleAlert class="mt-0.5 size-4 shrink-0 text-red-500" />
            <p>
                {{
                    t(
                        "Oxirgi urinish (:provider): :status. Qaytadan to'lashingiz mumkin.",
                        {
                            provider: cancelledAttempt.provider,
                            status: cancelledAttempt.statusLabel.toLowerCase(),
                        },
                    )
                }}
            </p>
        </div>

        <!-- Click / Payme -->
        <div
            v-if="online?.providers.length && !checking"
            class="mt-4 grid gap-2"
        >
            <button
                v-for="provider in online.providers"
                :key="provider.value"
                type="button"
                :disabled="starting !== null"
                :class="
                    cn(
                        'group flex w-full items-center gap-3 rounded-xl border border-line bg-white px-3.5 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 disabled:translate-y-0 disabled:opacity-60',
                        brand[provider.value]?.ring,
                    )
                "
                @click="pay(provider.value)"
            >
                <span
                    :class="
                        cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg text-white shadow-sm transition-transform duration-200 group-hover:scale-105',
                            brand[provider.value]?.dot,
                        )
                    "
                >
                    <LoaderCircle
                        v-if="starting === provider.value"
                        class="size-4 animate-spin"
                    />
                    <CreditCard v-else class="size-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-bold text-navy-950">{{
                        t(":provider orqali to'lash", {
                            provider: provider.label,
                        })
                    }}</span>
                    <span class="block text-[11px] text-navy-500">{{
                        t(brand[provider.value]?.hint ?? '')
                    }}</span>
                </span>
                <ArrowUpRight
                    class="size-4 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-navy-700"
                />
            </button>
            <p class="flex items-center gap-1.5 text-[11px] text-navy-500">
                <ShieldCheck class="size-3.5 text-emerald-600" />
                {{
                    t(
                        "Karta ma'lumotlari faqat to'lov tizimi sahifasida kiritiladi.",
                    )
                }}
            </p>
            <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        </div>

        <p
            v-if="online?.providers.length && requisites.length"
            class="mt-4 flex items-center gap-2 text-[11px] font-semibold tracking-wide text-navy-400 uppercase"
        >
            <Landmark class="size-3.5" /> {{ t('yoki bank orqali') }}
        </p>

        <dl
            v-if="requisites.length"
            class="mt-4 grid grid-cols-1 gap-2 text-[13px]"
        >
            <div
                v-for="[key, value] in requisites"
                :key="key"
                class="group flex items-center gap-2 rounded-lg bg-white px-3 py-2 ring-1 ring-line"
            >
                <dt class="w-24 shrink-0 text-[11px] text-navy-500">
                    {{ t(labels[key] ?? key) }}
                </dt>
                <dd
                    class="min-w-0 flex-1 font-medium [overflow-wrap:anywhere] text-navy-900"
                >
                    {{ value }}
                </dd>
                <button
                    type="button"
                    class="rounded p-1 text-navy-300 transition-colors hover:bg-brand-50 hover:text-brand-600"
                    :aria-label="
                        t(':label — nusxalash', {
                            label: t(labels[key] ?? key),
                        })
                    "
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
                <dt class="text-[11px] text-navy-500">
                    {{ t("To'lov maqsadi") }}
                </dt>
                <dd class="mt-0.5 font-medium text-navy-900">
                    {{ payment.purpose }}
                </dd>
            </div>
        </dl>
        <p
            v-else-if="!online?.providers.length"
            class="mt-4 flex items-start gap-2 rounded-lg bg-white px-3 py-2.5 text-xs leading-relaxed text-navy-600 ring-1 ring-line"
        >
            <CreditCard class="mt-0.5 size-4 shrink-0 text-navy-400" />
            {{ t("To'lov tartibi bo'yicha tahririyat bilan bog'laning.") }}
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
            {{ t("Nashr to'lovi") }}
        </h2>
        <dl class="mt-3 grid gap-1.5 text-[13px]">
            <div class="flex justify-between gap-3">
                <dt class="text-navy-500">{{ t('Summa') }}</dt>
                <dd class="font-semibold text-navy-950 tabular-nums">
                    {{ formatSum(payment.amount) }}
                </dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-navy-500">{{ t('Holat') }}</dt>
                <dd class="font-medium text-emerald-700">
                    {{ payment.statusLabel }}
                </dd>
            </div>
            <div v-if="payment.receipt" class="flex justify-between gap-3">
                <dt class="text-navy-500">{{ t('Chek') }}</dt>
                <dd class="font-mono text-xs font-semibold">
                    {{ payment.receipt }}
                </dd>
            </div>
            <div v-if="payment.provider" class="flex justify-between gap-3">
                <dt class="text-navy-500">{{ t("To'lov usuli") }}</dt>
                <dd>{{ payment.provider }}</dd>
            </div>
            <div v-if="payment.paidAt" class="flex justify-between gap-3">
                <dt class="text-navy-500">{{ t('Sana') }}</dt>
                <dd class="tabular-nums">
                    {{ formatDate(payment.paidAt) }}
                    {{ formatTime(payment.paidAt) }}
                </dd>
            </div>
        </dl>
    </section>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CircleAlert,
    CreditCard,
    Hourglass,
    LoaderCircle,
    Send,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { primaryButtonClass, secondaryButtonClass } from '@/lib/formStyles';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ArticleDraft, CabinetLinks, WizardOptions } from '@/types';

/**
 * 7-bosqich: narx va keyingi qadamlar, muallif roziliklari, yuborish.
 * Pullik turda maqola "To'lov kutilmoqda" holatiga o'tadi (to'lovni admin tasdiqlaydi).
 */
const props = defineProps<{
    article: ArticleDraft;
    options: WizardOptions;
    consents: string[];
    isComplete: boolean;
    links: CabinetLinks;
    prevHref: string | null;
    reviewHref: string | null;
}>();

const labels: Record<string, string> = {
    originality:
        "Maqola mualliflarning o'z ishi ekanini, unda plagiat va boshqalarning ma'lumotlaridan ruxsatsiz foydalanish yo'qligini tasdiqlayman.",
    exclusivity:
        'Maqola boshqa nashrda chop etilmagan va hozirda boshqa jurnalga yuborilmagan.',
    rules: "Jurnalning mualliflar uchun yo'riqnomasi va nashr shartlari bilan tanishdim hamda ularga roziman.",
};

const form = useForm({
    consents: Object.fromEntries(
        props.consents.map((key) => [key, false]),
    ) as Record<string, boolean>,
});

const type = computed(() =>
    props.options.types.find((item) => item.id === props.article.articleTypeId),
);
const isPaid = computed(() => (type.value?.price ?? 0) > 0);
const allAccepted = computed(() =>
    props.consents.every((key) => form.consents[key]),
);
const errors = computed(() => form.errors as Record<string, string>);
const consentError = computed(() =>
    props.consents.map((key) => errors.value[`consents.${key}`]).find(Boolean),
);

const nextSteps = computed(() => [
    {
        icon: isPaid.value ? CreditCard : BadgeCheck,
        title: isPaid.value ? "Nashr to'lovi" : "To'lov talab qilinmaydi",
        text: isPaid.value
            ? "Maqola \"To'lov kutilmoqda\" holatiga o'tadi. To'lov tasdiqlangach, tahririyat navbatiga qo'shiladi."
            : "Tanlangan maqola turi bepul — maqola darhol tahririyat navbatiga qo'shiladi.",
    },
    {
        icon: UsersRound,
        title: "Muharrir ko'rigi va taqriz",
        text: "Muharrir maqolani dastlabki tekshiruvdan o'tkazadi va mustaqil taqrizchilarga yuboradi.",
    },
    {
        icon: Hourglass,
        title: 'Natija',
        text: type.value?.reviewDays
            ? `Ko'rib chiqish taxminan ${type.value.reviewDays} kun davom etadi. Har bir bosqich haqida kabinetingizda xabar olasiz.`
            : 'Har bir bosqich haqida kabinetingizda xabar olasiz.',
    },
]);

function submit(): void {
    form.post(props.article.urls.submit, { preserveScroll: true });
}
</script>

<template>
    <div class="grid gap-6">
        <section
            class="relative isolate overflow-hidden rounded-xl bg-navy-950 p-5 text-white shadow-[0_16px_36px_-22px_rgba(0,30,60,0.9)]"
        >
            <div
                class="absolute inset-0 -z-10 bg-girih opacity-[0.07]"
                aria-hidden="true"
            />
            <div
                class="absolute -top-20 -right-10 -z-10 size-56 rounded-full bg-brand-500/30 blur-3xl"
                aria-hidden="true"
            />
            <p
                class="text-xs font-medium tracking-wider text-gold-300 uppercase"
            >
                Tanlangan maqola turi
            </p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                <h3 class="font-sans text-xl font-bold text-white">
                    {{ type?.name ?? '—' }}
                </h3>
                <p class="font-sans text-2xl font-bold tabular-nums">
                    {{ isPaid && type ? formatSum(type.price) : 'Bepul' }}
                </p>
            </div>
            <p v-if="type?.description" class="mt-1 text-sm text-white/70">
                {{ type.description }}
            </p>
        </section>

        <section>
            <h3 class="mb-3 text-sm font-bold text-navy-950">
                Yuborilgandan keyin
            </h3>
            <ol class="grid gap-3 md:grid-cols-3">
                <li
                    v-for="(item, index) in nextSteps"
                    :key="item.title"
                    class="group rounded-xl border border-line bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_12px_26px_-20px_rgba(0,36,66,0.5)]"
                >
                    <span class="flex items-center gap-2">
                        <span
                            class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 transition-transform group-hover:scale-110"
                        >
                            <component :is="item.icon" class="size-4" />
                        </span>
                        <span class="text-[11px] font-semibold text-navy-400"
                            >{{ index + 1 }}-qadam</span
                        >
                    </span>
                    <p class="mt-2 text-[13px] font-bold text-navy-900">
                        {{ item.title }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-navy-600">
                        {{ item.text }}
                    </p>
                </li>
            </ol>
        </section>

        <div
            v-if="!isComplete"
            class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
            <CircleAlert class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <p>
                Formaning ayrim bosqichlari to'ldirilmagan.
                <Link
                    v-if="reviewHref"
                    :href="reviewHref"
                    class="font-semibold underline underline-offset-2"
                    >Tekshirish bosqichida</Link
                >
                kamchiliklarni ko'ring.
            </p>
        </div>

        <fieldset class="grid gap-2.5">
            <legend class="mb-2 text-sm font-bold text-navy-950">
                Muallif roziligi
            </legend>
            <label
                v-for="key in consents"
                :key="key"
                :class="
                    cn(
                        'flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3 text-[13px] leading-relaxed transition-colors',
                        form.consents[key]
                            ? 'border-brand-300 bg-brand-50/50 text-navy-900'
                            : 'border-line bg-white text-navy-700 hover:border-brand-200',
                    )
                "
            >
                <input
                    v-model="form.consents[key]"
                    type="checkbox"
                    class="mt-0.5 size-4 shrink-0 accent-brand-600"
                />
                <span>{{ labels[key] ?? key }}</span>
            </label>
            <p v-if="consentError" class="text-xs font-medium text-red-600">
                {{ consentError }}
            </p>
            <p
                v-if="errors.submit"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ errors.submit }}
            </p>
        </fieldset>

        <div
            class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <Link
                v-if="prevHref"
                :href="prevHref"
                :class="cn(secondaryButtonClass, 'group')"
            >
                <ArrowLeft
                    class="size-4 transition-transform group-hover:-translate-x-0.5"
                />
                Orqaga
            </Link>
            <span v-else />
            <button
                type="button"
                :class="cn(primaryButtonClass, 'group h-11 px-6')"
                :disabled="!isComplete || !allAccepted || form.processing"
                @click="submit"
            >
                <LoaderCircle
                    v-if="form.processing"
                    class="size-4 animate-spin"
                />
                <Send
                    v-else
                    class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                />
                Maqolani yuborish
            </button>
        </div>
    </div>
</template>

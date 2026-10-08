<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BookOpenCheck,
    CalendarClock,
    CircleCheck,
    Download,
    ExternalLink,
    FilePenLine,
    Hourglass,
    LoaderCircle,
    PartyPopper,
    ShieldAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate, formatDateTime, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AuthorProduction } from '@/types';
import { t, tp } from '@/lib/i18n';

/**
 * Muallif kabineti — "Nashrga tayyorlash": korrektura (yakuniy PDF) ni ko'rish,
 * tasdiqlash yoki tuzatish so'rash; DOI va jurnal soni.
 */
const props = defineProps<{ production: AuthorProduction }>();

const approving = ref(false);

// Muddatgacha qolgan vaqt: "2 kun 5 soat" / "3 soat"
const timeLeft = computed(() => {
    if (!props.production.dueAt) {
        return null;
    }

    const ms = new Date(props.production.dueAt).getTime() - Date.now();

    if (ms <= 0) {
        return null;
    }

    const hours = Math.floor(ms / 3_600_000);
    const days = Math.floor(hours / 24);

    return days > 0
        ? t(':days kun :hours soat', { days, hours: hours % 24 })
        : t(':hours soat', { hours: Math.max(1, hours) });
});

// Muddat sanasi qalin (<b>) chiqadi — gap joy egasi atrofida bo'linadi
const overdueText = computed(() =>
    tp(
        "Javob muddati :date da tugadi. Iltimos, hoziroq javob bering — aks holda tahririyat maqolani o'z qarori bilan nashrga yuborishi mumkin.",
        'date',
    ),
);
const dueText = computed(() =>
    tp(
        'Korrekturani :date gacha tasdiqlang yoki tuzatishlarni yozing.',
        'date',
    ),
);

const awaiting = computed(
    () =>
        props.production.canRespond &&
        (props.production.state === 'pending' ||
            props.production.state === 'overdue'),
);
const changesOpen = ref(false);
const changesForm = useForm({ comment: '' });

function approve(): void {
    approving.value = true;
    router.post(
        props.production.approveUrl,
        {},
        { preserveScroll: true, onFinish: () => (approving.value = false) },
    );
}

function sendChanges(): void {
    changesForm.post(props.production.changesUrl, {
        preserveScroll: true,
        onSuccess: () => {
            changesOpen.value = false;
            changesForm.reset();
        },
    });
}
</script>

<template>
    <section
        id="nashr"
        class="scroll-mt-24 overflow-hidden rounded-xl border border-teal-200 bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_14px_34px_-20px_rgba(13,148,136,0.45)]"
    >
        <header
            class="flex items-start gap-3 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-emerald-50/40 px-5 py-4"
        >
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-white shadow-[0_8px_18px_-8px_rgba(13,148,136,0.9)]"
            >
                <BookOpenCheck class="size-5" />
            </span>
            <div class="min-w-0">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Nashrga tayyorlash') }}
                </h2>
                <p class="mt-0.5 text-xs text-navy-600">
                    {{
                        t(
                            'Maqolangiz qabul qilindi va jurnal formatida maketlanmoqda.',
                        )
                    }}
                </p>
            </div>
        </header>

        <div class="grid gap-4 p-5">
            <dl
                v-if="production.doi || production.issue"
                class="grid gap-2 rounded-lg bg-[#f5f8fc] p-3 text-[13px] sm:grid-cols-2"
            >
                <div v-if="production.issue">
                    <dt class="text-[11px] text-navy-500">
                        {{ t('Jurnal soni') }}
                    </dt>
                    <dd class="font-semibold text-navy-900">
                        {{ production.issue }}
                        <template v-if="production.pages"
                            >·
                            {{
                                t(':pages-betlar', { pages: production.pages })
                            }}</template
                        >
                    </dd>
                </div>
                <div v-if="production.doi">
                    <dt class="text-[11px] text-navy-500">DOI</dt>
                    <dd class="font-mono text-xs font-semibold text-navy-900">
                        {{ production.doi }}
                    </dd>
                </div>
            </dl>

            <!-- Korrektura -->
            <div v-if="production.proof" class="grid gap-3">
                <div
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line px-3 py-2.5"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-lg bg-red-50 text-[10px] font-bold text-red-600"
                        >PDF</span
                    >
                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-[13px] font-semibold text-navy-900"
                            >{{ production.proof.name }}</span
                        >
                        <span class="text-[11px] text-navy-500"
                            >{{ t('Korrektura') }} ·
                            {{ formatFileSize(production.proof.size) }} ·
                            {{ formatDate(production.proof.uploadedAt) }}</span
                        >
                    </span>
                    <a
                        :href="production.proof.viewUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                    >
                        <ExternalLink class="size-4" /> {{ t('Ochish') }}
                    </a>
                    <a
                        :href="production.proof.downloadUrl"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-navy-700 transition-colors hover:bg-navy-50"
                    >
                        <Download class="size-4" /> {{ t('Yuklab olish') }}
                    </a>
                </div>
                <div
                    v-if="awaiting"
                    :class="
                        cn(
                            'flex flex-wrap items-center gap-3 rounded-lg border px-3 py-2.5 text-[13px]',
                            production.state === 'overdue'
                                ? 'border-red-200 bg-red-50 text-red-800'
                                : 'border-amber-200 bg-amber-50 text-amber-900',
                        )
                    "
                >
                    <ShieldAlert
                        v-if="production.state === 'overdue'"
                        class="size-5 shrink-0"
                    />
                    <CalendarClock v-else class="size-5 shrink-0" />
                    <span class="flex-1">
                        <template v-if="production.state === 'overdue'">
                            {{ overdueText[0]
                            }}<b>{{ formatDateTime(production.dueAt) }}</b
                            >{{ overdueText[1] }}
                        </template>
                        <template v-else>
                            {{ dueText[0]
                            }}<b>{{ formatDateTime(production.dueAt) }}</b
                            >{{ dueText[1] }}
                        </template>
                    </span>
                    <span
                        v-if="timeLeft"
                        class="rounded-full bg-white/80 px-2.5 py-0.5 text-xs font-bold tabular-nums"
                        >{{ t(':time qoldi', { time: timeLeft }) }}</span
                    >
                </div>

                <details
                    v-if="awaiting"
                    class="group rounded-lg border border-line bg-[#fafcff] px-3 py-2.5 text-[13px] text-navy-700"
                >
                    <summary
                        class="cursor-pointer list-none font-semibold text-navy-900 marker:hidden"
                    >
                        {{ t('Korrekturada nimalarni tuzattirish mumkin?') }}
                        <span class="text-brand-700 group-open:hidden">{{
                            t("Ko'rish")
                        }}</span>
                    </summary>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="mb-1 text-xs font-bold text-emerald-700">
                                {{ t('Mumkin — maketdagi xatolar') }}
                            </p>
                            <ul class="list-disc space-y-0.5 pl-4 text-xs">
                                <li>
                                    {{
                                        t(
                                            'imlo, harf va tinish belgilari xatolari',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{ t('ism-familiya, ish joyi, ORCID') }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'formula, jadval va rasmlarning buzilishi',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{ t("adabiyotlar ro'yxatidagi xatolar") }}
                                </li>
                            </ul>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-bold text-red-700">
                                {{ t("Mumkin emas — mazmun o'zgarishi") }}
                            </p>
                            <ul class="list-disc space-y-0.5 pl-4 text-xs">
                                <li>
                                    {{
                                        t(
                                            "yangi bo'lim, natija yoki xulosa qo'shish",
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            "taqrizdan o'tgan matnni qayta yozish",
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t("muallif qo'shish yoki olib tashlash")
                                    }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </details>

                <iframe
                    :src="production.proof.viewUrl"
                    :title="production.proof.name"
                    class="hidden h-[520px] w-full rounded-lg border border-line bg-[#eef2f8] md:block"
                />

                <div
                    v-if="production.publicUrl"
                    class="flex flex-wrap items-center gap-3 rounded-lg bg-emerald-50 px-3 py-2.5 text-[13px] font-semibold text-emerald-800"
                >
                    <PartyPopper class="size-5 shrink-0" />
                    <span class="flex-1">{{
                        t('Maqolangiz :date da chop etildi!', {
                            date: formatDate(production.publishedAt),
                        })
                    }}</span>
                    <a
                        :href="production.publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs text-white transition-colors hover:bg-emerald-500"
                    >
                        <ExternalLink class="size-3.5" />
                        {{ t("Saytda ko'rish") }}
                    </a>
                </div>
                <div
                    v-else-if="production.readyForPublication"
                    class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2.5 text-[13px] font-semibold text-emerald-800"
                >
                    <BadgeCheck class="size-5 shrink-0" />
                    {{
                        t(
                            "Maqolangiz nashrga tasdiqlandi. Jurnal soni chop etilgach saytda e'lon qilinadi.",
                        )
                    }}
                </div>
                <div
                    v-else-if="production.approved"
                    class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2.5 text-[13px] text-emerald-800"
                >
                    <CircleCheck class="size-5 shrink-0" />
                    {{
                        t(
                            "Siz korrekturani :date da tasdiqladingiz. Bosh muharrir tasdig'i kutilmoqda.",
                            { date: formatDate(production.approvedAt) },
                        )
                    }}
                </div>
                <div
                    v-else-if="production.state === 'waived'"
                    class="flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-2.5 text-[13px] text-sky-900"
                >
                    <BadgeCheck class="size-5 shrink-0" />
                    {{
                        t(
                            "Javob muddati o'tgani sababli korrektura :date da tahririyat qarori bilan tasdiqlandi. Xato sezsangiz, darhol tuzatish so'rang.",
                            { date: formatDate(production.waivedAt) },
                        )
                    }}
                </div>
                <div
                    v-else-if="production.changes"
                    class="rounded-lg border-l-4 border-orange-400 bg-orange-50/70 px-3 py-2.5 text-[13px] text-navy-800"
                >
                    <b class="block text-xs text-orange-800">{{
                        t('Yuborgan tuzatishlaringiz — yangi PDF kutilmoqda:')
                    }}</b>
                    <span class="whitespace-pre-line">{{
                        production.changes
                    }}</span>
                </div>

                <div
                    v-if="production.canRespond && !production.approved"
                    class="flex flex-wrap items-center justify-end gap-2"
                >
                    <p class="mr-auto text-xs text-navy-500">
                        {{
                            t(
                                'Matn, mualliflar, jadval va rasmlarni diqqat bilan tekshiring. Javob berish uchun :days kun beriladi.',
                                { days: production.deadlineDays },
                            )
                        }}
                    </p>
                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-line px-4 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-orange-300 hover:text-orange-700"
                        @click="changesOpen = true"
                    >
                        <FilePenLine class="size-4" /> {{ t('Tuzatish kerak') }}
                    </button>
                    <button
                        type="button"
                        :disabled="approving"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(5,150,105,0.9)] transition-all hover:-translate-y-px hover:bg-emerald-500 disabled:opacity-60"
                        @click="approve"
                    >
                        <LoaderCircle
                            v-if="approving"
                            class="size-4 animate-spin"
                        />
                        <CircleCheck v-else class="size-4" />
                        {{ t('Tasdiqlayman') }}
                    </button>
                </div>
            </div>
            <div
                v-else
                :class="
                    cn(
                        'flex items-center gap-3 rounded-lg bg-[#f5f8fc] px-4 py-3 text-[13px] text-navy-700',
                    )
                "
            >
                <Hourglass class="size-5 shrink-0 text-teal-600" />
                {{
                    t(
                        "Maket tayyorlanmoqda. Yakuniy PDF (korrektura) tayyor bo'lganda sizga xabar beramiz — uni tekshirib tasdiqlashingiz kerak bo'ladi.",
                    )
                }}
            </div>
        </div>

        <ActionDialog
            v-model:open="changesOpen"
            :title="t('Korrektura bo\'yicha tuzatishlar')"
            :description="
                t(
                    'Faqat maketdagi xatolarni yozing (bet, satr, to\'g\'ri variant). Maqola mazmunini o\'zgartirish bu bosqichda mumkin emas. Xabar tahririyatga yuboriladi.',
                )
            "
            :icon="FilePenLine"
            :confirm-text="t('Yuborish')"
            :processing="changesForm.processing"
            @confirm="sendChanges"
        >
            <textarea
                v-model="changesForm.comment"
                rows="5"
                maxlength="3000"
                :placeholder="
                    t(
                        'Masalan: 3-bet, 2-xatboshi — «tadqiqot» so\'zi ikki marta yozilgan.',
                    )
                "
                :class="textareaClass"
            />
            <p
                v-if="changesForm.errors.comment"
                class="mt-1 text-xs text-red-600"
            >
                {{ changesForm.errors.comment }}
            </p>
        </ActionDialog>
    </section>
</template>

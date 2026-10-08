<script setup lang="ts">
import {
    Download,
    FileSpreadsheet,
    FileText,
    Printer,
    ReceiptText,
    UserCheck,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { ReportExportLink } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Hisobotlar" tabi: tanlangan davr va yo'nalish bo'yicha yuklab olinadigan hisobotlar.
 */
defineProps<{
    exports: ReportExportLink[];
    printUrl: string;
    period: string;
}>();

const meta: Record<string, { icon: Component; text: string; tint: string }> = {
    articles: {
        icon: FileText,
        text: t(
            "Kod, sarlavha, mualliflar, holat, sanalar, DOI, ko'rishlar va yuklab olishlar",
        ),
        tint: 'bg-brand-50 text-brand-600',
    },
    payments: {
        icon: ReceiptText,
        text: t("Kvitansiya, to'lov tizimi, summa, to'lovchi, maqola va sana"),
        tint: 'bg-emerald-50 text-emerald-600',
    },
    reviewers: {
        icon: UserCheck,
        text: t(
            "Takliflar, topshirilgan xulosalar, rad etishlar, o'rtacha muddat",
        ),
        tint: 'bg-amber-50 text-amber-600',
    },
    authors: {
        icon: UsersRound,
        text: t('Mualliflar, tashkilot, mamlakat, ORCID va maqolalar soni'),
        tint: 'bg-violet-50 text-violet-600',
    },
};
</script>

<template>
    <div class="flex flex-col gap-5">
        <p class="text-sm text-navy-500">
            {{ t('Hisobotlar') }}
            <b class="text-navy-900">{{ period }}</b> davri uchun tuziladi. CSV
            fayllar Excel'da to'g'ridan-to'g'ri ochiladi (UTF-8, ";" ajratgich).
        </p>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <a
                v-for="item in exports"
                :key="item.type"
                :href="item.url"
                class="group flex flex-col rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_18px_36px_-18px_rgba(0,36,66,0.35)]"
            >
                <span class="flex items-start justify-between gap-3">
                    <span
                        :class="[
                            'flex size-11 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110',
                            meta[item.type]?.tint ?? 'bg-navy-50 text-navy-600',
                        ]"
                    >
                        <component
                            :is="meta[item.type]?.icon ?? FileSpreadsheet"
                            class="size-5"
                        />
                    </span>
                    <span
                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold tracking-wide text-emerald-700 uppercase"
                    >
                        {{ t('Excel · CSV') }}
                    </span>
                </span>
                <span class="mt-4 text-[15px] font-bold text-navy-950">
                    {{ item.label }}
                </span>
                <span class="mt-1 flex-1 text-[13px] text-navy-500">
                    {{ meta[item.type]?.text }}
                </span>
                <span
                    class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-semibold text-brand-700"
                >
                    <Download
                        class="size-4 transition-transform group-hover:translate-y-0.5"
                    />
                    {{ t('Yuklab olish') }}
                </span>
            </a>

            <a
                :href="printUrl"
                target="_blank"
                rel="noopener"
                class="group flex flex-col rounded-xl border border-dashed border-red-200 bg-red-50/40 p-5 transition-all duration-300 hover:-translate-y-1 hover:bg-red-50 hover:shadow-[0_18px_36px_-18px_rgba(150,20,20,0.35)]"
            >
                <span class="flex items-start justify-between gap-3">
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-white text-red-600 transition-transform duration-300 group-hover:scale-110"
                    >
                        <Printer class="size-5" />
                    </span>
                    <span
                        class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold tracking-wide text-red-700 uppercase"
                    >
                        PDF
                    </span>
                </span>
                <span class="mt-4 text-[15px] font-bold text-navy-950">
                    {{ t('Umumiy statistik hisobot') }}
                </span>
                <span class="mt-1 flex-1 text-[13px] text-navy-500">
                    {{
                        t(
                            "Ko'rsatkichlar, yo'nalishlar, mamlakatlar, daromad, faol mualliflar va taqrizchilar — A4 sahifa, brauzerda PDF sifatida saqlanadi.",
                        )
                    }}
                </span>
                <span
                    class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-semibold text-red-700"
                >
                    <Printer class="size-4" /> {{ t('Ochish va chop etish') }}
                </span>
            </a>
        </section>
    </div>
</template>

import { ChartColumnIncreasing, FilePenLine, Languages } from '@lucide/vue';
import type { Component } from 'vue';
import { t, tk } from '@/lib/i18n';
import type {
    AiRequestStatusValue,
    AiRequestTypeValue,
    AnalysisMetricKey,
    ProofIssueType,
} from '@/types';

/** AI Studio: xizmatlar, holatlar va taklif turlari uchun umumiy matn/rang/ikonlar */
export const studios: Record<
    AiRequestTypeValue,
    {
        tab: 'proofreader' | 'translator' | 'analytics';
        name: string;
        short: string;
        description: string;
        icon: Component;
        gradient: string;
        soft: string;
    }
> = {
    spell_check: {
        tab: 'proofreader',
        name: 'AI Proofreader',
        short: tk('Tahrirlash (Proofreader)'),
        description: tk(
            'Matndagi imlo, grammatik va uslubiy xatolarni aniqlash va takliflar berish.',
        ),
        icon: FilePenLine,
        gradient: 'from-[#2f8cff] to-[#0057d9]',
        soft: 'bg-brand-50 text-brand-600',
    },
    translation: {
        tab: 'translator',
        name: 'AI Translator',
        short: tk('Tarjima (Translator)'),
        description: tk(
            "Ilmiy matnlarni o'zbek, rus va ingliz tillariga ilmiy uslubni saqlab tarjima qilish.",
        ),
        icon: Languages,
        gradient: 'from-[#22c08f] to-[#0b8a63]',
        soft: 'bg-emerald-50 text-emerald-600',
    },
    analysis: {
        tab: 'analytics',
        name: 'AI Analytics',
        short: tk('Tahlil (Analytics)'),
        description: tk(
            'Matnning ilmiy uslubini, tuzilishi va aniqligini tahlil qilish va baholash.',
        ),
        icon: ChartColumnIncreasing,
        gradient: 'from-[#ffa53d] to-[#f06a14]',
        soft: 'bg-orange-50 text-orange-600',
    },
};

export const statusTone: Record<AiRequestStatusValue, string> = {
    queued: 'bg-navy-50 text-navy-600 ring-navy-100',
    processing: 'bg-amber-50 text-amber-700 ring-amber-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    failed: 'bg-red-50 text-red-700 ring-red-200',
};

export const issueMeta: Record<
    ProofIssueType,
    { label: string; dot: string; mark: string }
> = {
    spelling: {
        label: tk('Imlo xatosi'),
        dot: 'bg-red-500',
        mark: 'decoration-red-500 bg-red-50 text-red-700',
    },
    grammar: {
        label: tk('Grammatik xato'),
        dot: 'bg-rose-500',
        mark: 'decoration-rose-500 bg-rose-50 text-rose-700',
    },
    punctuation: {
        label: tk('Tinish belgisi'),
        dot: 'bg-fuchsia-500',
        mark: 'decoration-fuchsia-500 bg-fuchsia-50 text-fuchsia-700',
    },
    style: {
        label: tk('Uslubiy taklif'),
        dot: 'bg-amber-500',
        mark: 'decoration-amber-500 bg-amber-50 text-amber-800',
    },
    terminology: {
        label: tk('Terminologiya'),
        dot: 'bg-violet-500',
        mark: 'decoration-violet-500 bg-violet-50 text-violet-700',
    },
};

export const metricLabels: Record<AnalysisMetricKey, string> = {
    academic_style: tk('Ilmiy uslub'),
    clarity: tk('Aniqlik'),
    structure: tk('Tuzilish'),
    terminology: tk('Terminologiya'),
    coherence: tk("Bog'liqlik"),
};

export function qualityLabel(score: number | null): string {
    if (score === null) {
        return t('Baholanmadi');
    }

    if (score >= 90) {
        return t("A'lo");
    }

    if (score >= 75) {
        return t('Juda yaxshi');
    }

    if (score >= 60) {
        return t('Yaxshi');
    }

    return t('Qayta ishlash kerak');
}

export async function copyText(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        return false;
    }
}

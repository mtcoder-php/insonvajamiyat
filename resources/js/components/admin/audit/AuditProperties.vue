<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';

/**
 * Audit yozuvi tafsilotlari: {old, new} bo'lsa — "oldin → keyin" jadvali,
 * aks holda kalit/qiymat ro'yxati.
 */
const props = defineProps<{ properties: Record<string, unknown> | null }>();

const LABELS: Record<string, string> = {
    name: t('Ism'),
    email: t('Email'),
    roles: t('Rollar'),
    email_verified: t('Email tasdiqlangan'),
    avatar: t('Avatar'),
    reason: t('Sabab'),
    from: t('Oldingi holat'),
    to: t('Yangi holat'),
    comment: t('Izoh'),
    remember: t('Eslab qolish'),
    amount: t('Summa'),
    paid_at: t("To'langan sana"),
    reference: t('Tranzaksiya'),
    article: t('Maqola'),
    reviewers: t('Taqrizchilar'),
    reviewer: t('Taqrizchi'),
    round: t('Raund'),
    due_days: t('Muddat (kun)'),
    editor: t('Muharrir'),
    file: t('Fayl'),
    size: 'Hajm',
    articles: t('Maqolalar soni'),
    type: 'Turi',
    format: 'Format',
    filters: t('Filtrlar'),
    subject: t("Yo'nalish"),
    review: t('Taqriz'),
};

const label = (key: string): string => LABELS[key] ?? key;

function show(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? t('Ha') : t("Yo'q");
    }

    if (Array.isArray(value)) {
        return value.map((v) => show(v)).join(', ');
    }

    if (typeof value === 'object') {
        return Object.entries(value as Record<string, unknown>)
            .map(([k, v]) => `${label(k)}: ${show(v)}`)
            .join('; ');
    }

    return String(value);
}

const diff = computed(() => {
    const p = props.properties;

    if (
        !p ||
        typeof p.old !== 'object' ||
        typeof p.new !== 'object' ||
        !p.old ||
        !p.new
    ) {
        return null;
    }

    const before = p.old as Record<string, unknown>;
    const after = p.new as Record<string, unknown>;

    return Object.keys({ ...before, ...after }).map((key) => ({
        key,
        before: before[key],
        after: after[key],
    }));
});

const rest = computed(() =>
    Object.entries(props.properties ?? {}).filter(
        ([key]) => !(diff.value && (key === 'old' || key === 'new')),
    ),
);
</script>

<template>
    <div class="grid gap-3">
        <table v-if="diff" class="w-full max-w-2xl text-[12px]">
            <thead>
                <tr
                    class="text-left text-[10px] tracking-wide text-navy-400 uppercase"
                >
                    <th class="py-1 pr-3 font-semibold">{{ t('Maydon') }}</th>
                    <th class="py-1 pr-3 font-semibold">{{ t('Oldin') }}</th>
                    <th class="py-1 font-semibold">{{ t('Keyin') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in diff"
                    :key="row.key"
                    class="border-t border-line/70"
                >
                    <td class="py-1.5 pr-3 font-medium text-navy-600">
                        {{ label(row.key) }}
                    </td>
                    <td
                        class="py-1.5 pr-3 text-red-700 line-through decoration-red-300"
                    >
                        {{ show(row.before) }}
                    </td>
                    <td class="py-1.5 font-medium text-emerald-700">
                        {{ show(row.after) }}
                    </td>
                </tr>
            </tbody>
        </table>
        <dl
            v-if="rest.length"
            class="grid max-w-2xl grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-[12px]"
        >
            <template v-for="[key, value] in rest" :key="key">
                <dt class="font-medium text-navy-500">{{ label(key) }}</dt>
                <dd class="break-words text-navy-900">{{ show(value) }}</dd>
            </template>
        </dl>
    </div>
</template>

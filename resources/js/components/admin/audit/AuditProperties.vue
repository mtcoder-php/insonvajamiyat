<script setup lang="ts">
import { computed } from 'vue';

/**
 * Audit yozuvi tafsilotlari: {old, new} bo'lsa — "oldin → keyin" jadvali,
 * aks holda kalit/qiymat ro'yxati.
 */
const props = defineProps<{ properties: Record<string, unknown> | null }>();

const LABELS: Record<string, string> = {
    name: 'Ism',
    email: 'Email',
    roles: 'Rollar',
    email_verified: 'Email tasdiqlangan',
    avatar: 'Avatar',
    reason: 'Sabab',
    from: 'Oldingi holat',
    to: 'Yangi holat',
    comment: 'Izoh',
    remember: 'Eslab qolish',
    amount: 'Summa',
    paid_at: "To'langan sana",
    reference: 'Tranzaksiya',
    article: 'Maqola',
    reviewers: 'Taqrizchilar',
    reviewer: 'Taqrizchi',
    round: 'Raund',
    due_days: 'Muddat (kun)',
    editor: 'Muharrir',
    file: 'Fayl',
    size: 'Hajm',
    articles: 'Maqolalar soni',
    type: 'Turi',
    format: 'Format',
    filters: 'Filtrlar',
    subject: "Yo'nalish",
    review: 'Taqriz',
};

const label = (key: string): string => LABELS[key] ?? key;

function show(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ha' : "Yo'q";
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
                    <th class="py-1 pr-3 font-semibold">Maydon</th>
                    <th class="py-1 pr-3 font-semibold">Oldin</th>
                    <th class="py-1 font-semibold">Keyin</th>
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

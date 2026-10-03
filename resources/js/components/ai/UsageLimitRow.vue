<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, LoaderCircle } from '@lucide/vue';
import { ref, watch } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiUsageRow } from '@/types';

/** Foydalanuvchining oylik sarfi va shaxsiy limiti (bo'sh — rol bo'yicha standart, 0 — cheklanmagan) */
const props = defineProps<{ row: AiUsageRow }>();

const value = ref<string>(
    props.row.personalLimit === null ? '' : String(props.row.personalLimit),
);
const saving = ref(false);
const saved = ref(false);

watch(
    () => props.row.personalLimit,
    (limit) => (value.value = limit === null ? '' : String(limit)),
);

function save(): void {
    saving.value = true;
    router.put(
        props.row.updateUrl,
        { limit: value.value === '' ? null : Number(value.value) },
        {
            preserveScroll: true,
            only: ['settings'],
            onSuccess: () => {
                saved.value = true;
                setTimeout(() => (saved.value = false), 1500);
            },
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <tr class="transition-colors hover:bg-brand-50/30">
        <td class="px-4 py-2.5">
            <span class="block font-medium text-navy-900">{{ row.name }}</span>
            <span class="block text-[11px] text-navy-400"
                >{{ row.email }} · {{ row.staff ? 'Xodim' : 'Muallif' }}</span
            >
        </td>
        <td class="px-4 py-2.5 text-right tabular-nums">
            {{ formatNumber(row.requests) }}
        </td>
        <td class="w-48 px-4 py-2.5">
            <div
                class="flex justify-between text-[11px] text-navy-600 tabular-nums"
            >
                <span>{{ formatNumber(row.used) }}</span>
                <span>{{ row.limit > 0 ? formatNumber(row.limit) : '∞' }}</span>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-[#edf2f8]">
                <div
                    :class="
                        cn(
                            'h-full rounded-full',
                            (row.percent ?? 0) >= 90
                                ? 'bg-red-500'
                                : (row.percent ?? 0) >= 70
                                  ? 'bg-amber-500'
                                  : 'bg-brand-500',
                        )
                    "
                    :style="{ width: `${row.percent ?? 0}%` }"
                />
            </div>
        </td>
        <td class="px-4 py-2.5">
            <form
                class="flex items-center justify-end gap-1.5"
                @submit.prevent="save"
            >
                <input
                    v-model="value"
                    type="number"
                    min="0"
                    step="1000"
                    placeholder="Standart"
                    class="h-8 w-28 rounded-md border border-line px-2 text-right text-[12px] tabular-nums outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100"
                    aria-label="Shaxsiy limit"
                />
                <button
                    type="submit"
                    :disabled="saving"
                    class="inline-flex size-8 items-center justify-center rounded-md bg-brand-50 text-brand-700 transition-colors hover:bg-brand-100 disabled:opacity-60"
                    aria-label="Limitni saqlash"
                >
                    <LoaderCircle v-if="saving" class="size-4 animate-spin" />
                    <Check
                        v-else
                        :class="cn('size-4', saved && 'text-emerald-600')"
                    />
                </button>
            </form>
        </td>
    </tr>
</template>

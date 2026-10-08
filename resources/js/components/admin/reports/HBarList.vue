<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import type { ReportShareItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Gorizontal ustunlar ro'yxati (masalan, mamlakatlar bo'yicha mualliflar).
 * Bir rang — miqdor uzunlik bilan ko'rsatiladi; qiymat va ulush har qatorda yozilgan.
 */
const props = withDefaults(
    defineProps<{
        items: ReportShareItem[];
        total: number;
        color?: string;
        emptyText?: string;
    }>(),
    { color: '#1a82f7', emptyText: undefined },
);

const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));

const badge = (key: string): string =>
    key === 'other' ? '…' : key === 'none' ? '?' : key;
</script>

<template>
    <p
        v-if="items.length === 0"
        class="py-10 text-center text-sm text-navy-400"
    >
        {{ emptyText ?? t("Tanlangan davrda ma'lumot yo'q") }}
    </p>
    <ul v-else class="space-y-2.5">
        <li
            v-for="item in items"
            :key="item.key"
            class="group grid grid-cols-[2rem_minmax(0,8rem)_minmax(0,1fr)_auto] items-center gap-2.5 text-[13px]"
        >
            <span
                class="flex h-5 w-7 items-center justify-center rounded border border-line bg-surface-muted text-[10px] font-bold text-navy-600"
            >
                {{ badge(item.key) }}
            </span>
            <span class="truncate text-navy-700" :title="item.label">
                {{ item.label }}
            </span>
            <span class="h-2.5 overflow-hidden rounded-full bg-[#eef2f7]">
                <span
                    class="block h-full rounded-full transition-all duration-500 group-hover:brightness-110"
                    :style="{
                        width: `${(item.value / max) * 100}%`,
                        background: item.key === 'other' ? '#9aabbd' : color,
                    }"
                />
            </span>
            <span class="w-16 text-right tabular-nums">
                <span class="font-semibold text-navy-950">{{
                    formatNumber(item.value)
                }}</span>
                <span class="ml-1 text-[11px] text-navy-400">
                    {{
                        total > 0 ? Math.round((item.value / total) * 100) : 0
                    }}%
                </span>
            </span>
        </li>
    </ul>
</template>

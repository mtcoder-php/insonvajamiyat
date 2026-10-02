<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { SimpleMeta } from '@/types';

/**
 * Sahifalash (partial reload uchun): "1–8 / 120 ta" va sahifa raqamlari,
 * ko'p sahifada "…" bilan qisqartiriladi. Tanlangan sahifa — `go` hodisasi.
 */
const props = defineProps<{ meta: SimpleMeta }>();

const emit = defineEmits<{ go: [page: number] }>();

const pages = computed<(number | null)[]>(() => {
    const { currentPage: current, lastPage: last } = props.meta;

    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const set = new Set([
        1,
        2,
        last - 1,
        last,
        current - 1,
        current,
        current + 1,
    ]);
    const sorted = [...set]
        .filter((p) => p >= 1 && p <= last)
        .sort((a, b) => a - b);
    const result: (number | null)[] = [];

    sorted.forEach((page, i) => {
        if (i > 0 && page - sorted[i - 1] > 1) {
            result.push(null);
        }

        result.push(page);
    });

    return result;
});

const item =
    'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-[13px] font-medium transition-all';
</script>

<template>
    <nav
        v-if="meta.total > 0"
        class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        aria-label="Sahifalar"
    >
        <p class="text-xs text-navy-500 tabular-nums">
            {{ meta.from }}–{{ meta.to }} / {{ meta.total }} ta
        </p>
        <div v-if="meta.lastPage > 1" class="flex flex-wrap items-center gap-1">
            <button
                type="button"
                :disabled="meta.currentPage <= 1"
                :class="
                    cn(
                        item,
                        'text-navy-700 hover:bg-surface-muted disabled:text-navy-300 disabled:hover:bg-transparent',
                    )
                "
                aria-label="Oldingi sahifa"
                @click="emit('go', meta.currentPage - 1)"
            >
                <ChevronLeft class="size-4" />
            </button>
            <template v-for="(page, i) in pages" :key="i">
                <span v-if="page === null" :class="cn(item, 'text-navy-400')"
                    >…</span
                >
                <button
                    v-else
                    type="button"
                    :class="
                        cn(
                            item,
                            'tabular-nums',
                            page === meta.currentPage
                                ? 'bg-brand-600 text-white shadow-[0_6px_14px_-8px_rgba(0,108,246,0.9)]'
                                : 'text-navy-700 hover:bg-surface-muted',
                        )
                    "
                    :aria-current="
                        page === meta.currentPage ? 'page' : undefined
                    "
                    @click="emit('go', page)"
                >
                    {{ page }}
                </button>
            </template>
            <button
                type="button"
                :disabled="meta.currentPage >= meta.lastPage"
                :class="
                    cn(
                        item,
                        'text-navy-700 hover:bg-surface-muted disabled:text-navy-300 disabled:hover:bg-transparent',
                    )
                "
                aria-label="Keyingi sahifa"
                @click="emit('go', meta.currentPage + 1)"
            >
                <ChevronRight class="size-4" />
            </button>
        </div>
    </nav>
</template>

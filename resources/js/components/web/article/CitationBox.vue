<script setup lang="ts">
import { Check, Copy, Quote } from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Iqtibos formatlari: APA va GOST, bir bosishda nusxalash.
 */
const props = defineProps<{ citations: { apa: string; gost: string } }>();

type Format = 'apa' | 'gost';

const format = ref<Format>('apa');
const copied = ref<Format | null>(null);

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.citations[format.value]);
        copied.value = format.value;
        window.setTimeout(() => (copied.value = null), 2000);
    } catch {
        copied.value = null;
    }
}
</script>

<template>
    <div>
        <p
            class="mb-2 flex items-center gap-2 text-[13px] font-bold text-navy-950"
        >
            <Quote class="size-4 text-brand-600" /> Iqtibos formatlari
        </p>
        <div
            class="mb-2 inline-flex rounded-lg bg-[#f2f5fa] p-1"
            role="tablist"
        >
            <button
                v-for="item in ['apa', 'gost'] as const"
                :key="item"
                type="button"
                role="tab"
                :aria-selected="format === item"
                :class="
                    cn(
                        'rounded-md px-3 py-1 text-xs font-semibold uppercase transition-all',
                        format === item
                            ? 'bg-white text-brand-700 shadow-sm'
                            : 'text-navy-500 hover:text-navy-900',
                    )
                "
                @click="format = item"
            >
                {{ item }}
            </button>
        </div>
        <p
            class="rounded-lg border border-line bg-[#fbfcfe] p-3 text-xs leading-relaxed break-words text-navy-700"
        >
            {{ citations[format] }}
        </p>
        <button
            type="button"
            class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
            @click="copy"
        >
            <Check v-if="copied === format" class="size-3.5 text-emerald-600" />
            <Copy v-else class="size-3.5" />
            {{ copied === format ? 'Nusxalandi' : 'Nusxalash' }}
        </button>
    </div>
</template>

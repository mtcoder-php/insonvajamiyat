<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

/**
 * Kalit so'zlar kiritish: Enter, vergul yoki nuqtali vergul — yangi teg;
 * Backspace (bo'sh maydonda) — oxirgisini o'chiradi; bir nechta so'zni vergul bilan qo'yish mumkin.
 */
const props = defineProps<{
    max: number;
    maxLength: number;
    invalid?: boolean;
    placeholder?: string;
    id?: string;
}>();

const model = defineModel<string[]>({ required: true });
const draft = ref('');

function add(raw: string): void {
    const words = raw
        .split(/[,;\n]/)
        .map((word) =>
            word.replace(/\s+/g, ' ').trim().slice(0, props.maxLength),
        )
        .filter(Boolean);

    for (const word of words) {
        const exists = model.value.some(
            (item) => item.toLowerCase() === word.toLowerCase(),
        );

        if (!exists && model.value.length < props.max) {
            model.value = [...model.value, word];
        }
    }

    draft.value = '';
}

function onKeydown(event: KeyboardEvent): void {
    if (['Enter', ',', ';'].includes(event.key)) {
        event.preventDefault();
        add(draft.value);
    } else if (
        event.key === 'Backspace' &&
        draft.value === '' &&
        model.value.length
    ) {
        model.value = model.value.slice(0, -1);
    }
}

function onPaste(event: ClipboardEvent): void {
    const text = event.clipboardData?.getData('text') ?? '';

    if (/[,;\n]/.test(text)) {
        event.preventDefault();
        add(text);
    }
}

function remove(index: number): void {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div
        :class="
            cn(
                'flex min-h-12 flex-wrap items-center gap-1.5 rounded-lg border bg-white px-2 py-1.5 transition focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-100 hover:border-navy-200',
                invalid ? 'border-red-400' : 'border-line',
            )
        "
    >
        <TransitionGroup name="tag">
            <span
                v-for="(word, index) in model"
                :key="word"
                class="group inline-flex items-center gap-1 rounded-md bg-brand-50 py-1 pr-1 pl-2.5 text-[13px] font-medium text-brand-800 ring-1 ring-brand-100 ring-inset"
            >
                {{ word }}
                <button
                    type="button"
                    class="flex size-5 items-center justify-center rounded text-brand-400 transition-colors hover:bg-brand-100 hover:text-brand-700"
                    :aria-label="t(':name — o\'chirish', { name: word })"
                    @click="remove(index)"
                >
                    <X class="size-3.5" />
                </button>
            </span>
        </TransitionGroup>
        <input
            :id="id"
            v-model="draft"
            type="text"
            :maxlength="maxLength"
            :disabled="model.length >= max"
            :placeholder="
                model.length >= max
                    ? t('Ko\'pi bilan :max ta', { max })
                    : (placeholder ?? t('So\'z kiriting va Enter bosing'))
            "
            class="h-8 min-w-40 flex-1 bg-transparent px-1 text-sm text-navy-900 outline-none placeholder:text-navy-300 disabled:cursor-not-allowed"
            @keydown="onKeydown"
            @paste="onPaste"
            @blur="draft.trim() && add(draft)"
        />
    </div>
</template>

<style scoped>
.tag-enter-active,
.tag-leave-active {
    transition: all 0.2s ease;
}

.tag-enter-from,
.tag-leave-to {
    opacity: 0;
    transform: scale(0.85);
}
</style>

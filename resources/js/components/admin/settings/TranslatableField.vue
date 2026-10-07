<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { Translated } from '@/types';

/**
 * Uch tilli maydon (UZ / RU / EN): o'zbekcha majburiy, boshqalari ixtiyoriy.
 * To'ldirilgan tillar yonida belgi chiqadi; xatolar `errors["name.uz"]` ko'rinishida.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        field: string;
        errors?: Record<string, string>;
        required?: boolean;
        multiline?: boolean;
        maxlength?: number;
        placeholder?: string;
    }>(),
    {
        errors: () => ({}),
        required: false,
        multiline: false,
        maxlength: 255,
        placeholder: '',
    },
);

const model = defineModel<Translated>({ required: true });

const langs = [
    { key: 'uz', label: 'UZ' },
    { key: 'ru', label: 'RU' },
    { key: 'en', label: 'EN' },
] as const;

const active = ref<'uz' | 'ru' | 'en'>('uz');

const error = computed(
    () =>
        props.errors[`${props.field}.uz`] ??
        props.errors[`${props.field}.ru`] ??
        props.errors[`${props.field}.en`] ??
        props.errors[props.field],
);

function hasError(lang: string): boolean {
    return !!props.errors[`${props.field}.${lang}`];
}
</script>

<template>
    <div class="grid content-start gap-1.5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-[13px] font-semibold text-navy-800">
                {{ label }}
                <span v-if="required" class="text-red-500" aria-hidden="true"
                    >*</span
                >
            </span>
            <div
                class="inline-flex rounded-md bg-[#eef3fa] p-0.5 text-[11px] font-bold"
                role="tablist"
            >
                <button
                    v-for="lang in langs"
                    :key="lang.key"
                    type="button"
                    role="tab"
                    :aria-selected="active === lang.key"
                    :class="
                        cn(
                            'inline-flex items-center gap-1 rounded px-2 py-0.5 transition-all',
                            active === lang.key
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-navy-500 hover:text-navy-800',
                            hasError(lang.key) && 'text-red-600',
                        )
                    "
                    @click="active = lang.key"
                >
                    {{ lang.label }}
                    <Check
                        v-if="model[lang.key].trim()"
                        class="size-3 text-emerald-600"
                    />
                </button>
            </div>
        </div>
        <template v-for="lang in langs" :key="lang.key">
            <textarea
                v-if="multiline"
                v-show="active === lang.key"
                v-model="model[lang.key]"
                rows="3"
                :maxlength="maxlength"
                :placeholder="
                    lang.key === 'uz'
                        ? placeholder
                        : `${placeholder ? placeholder + ' — ' : ''}${lang.label} (ixtiyoriy)`
                "
                :class="textareaClass"
                :aria-invalid="hasError(lang.key)"
            />
            <input
                v-else
                v-show="active === lang.key"
                v-model="model[lang.key]"
                type="text"
                :maxlength="maxlength"
                :placeholder="
                    lang.key === 'uz'
                        ? placeholder
                        : `${lang.label} (ixtiyoriy)`
                "
                :class="inputClass"
                :aria-invalid="hasError(lang.key)"
            />
        </template>
        <p v-if="error" class="text-xs font-medium text-red-600">{{ error }}</p>
    </div>
</template>

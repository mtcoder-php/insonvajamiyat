<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowLeftRight, Eraser, LoaderCircle, Sparkles } from '@lucide/vue';
import { computed, watch } from 'vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { textareaClass, primaryButtonClass } from '@/lib/formStyles';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiOption, AiRequestTypeValue } from '@/types';

/**
 * AI so'rovi formasi: matn, til(lar), Proofreader uchun tekshiruv turlari.
 * `initial` — qayta yuborish uchun oldingi matn (xato bo'lganda).
 */
const props = defineProps<{
    type: AiRequestTypeValue;
    url: string;
    languages: AiOption[];
    checks: AiOption[];
    maxChars: number;
    disabled?: boolean;
    initial?: { text: string; source: string; target: string | null } | null;
}>();

const form = useForm({
    type: props.type,
    text: props.initial?.text ?? '',
    source_language: props.initial?.source ?? 'uz',
    target_language:
        props.initial?.target ?? (props.type === 'translation' ? 'en' : null),
    checks: props.checks.map((c) => c.value),
});

watch(
    () => props.initial,
    (value) => {
        if (value) {
            form.text = value.text;
            form.source_language = value.source;
            form.target_language = value.target ?? form.target_language;
        }
    },
);

const length = computed(() => form.text.length);
const tooLong = computed(() => length.value > props.maxChars);

const labels: Record<
    AiRequestTypeValue,
    { button: string; placeholder: string }
> = {
    spell_check: {
        button: 'Tahlilni boshlash',
        placeholder: 'Tekshiriladigan matnni shu yerga joylashtiring…',
    },
    translation: {
        button: 'Tarjima qilish',
        placeholder: 'Tarjima qilinadigan matnni kiriting…',
    },
    analysis: {
        button: 'Tahlil qilish',
        placeholder: 'Baholanadigan ilmiy matnni joylashtiring…',
    },
};

function swap(): void {
    if (!form.target_language) {
        return;
    }

    const source = form.source_language;
    form.source_language = form.target_language;
    form.target_language = source;
}

function submit(): void {
    form.post(props.url, {
        preserveScroll: true,
        onSuccess: () => form.clearErrors(),
    });
}
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <!-- Til(lar) -->
        <div v-if="type === 'translation'" class="flex items-end gap-2">
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-semibold text-navy-600"
                    >Manba til</span
                >
                <SelectInput v-model="form.source_language">
                    <option
                        v-for="lang in languages"
                        :key="lang.value"
                        :value="lang.value"
                    >
                        {{ lang.label }}
                    </option>
                </SelectInput>
            </label>
            <button
                type="button"
                class="mb-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-line bg-white text-navy-500 transition-all hover:rotate-180 hover:border-brand-300 hover:text-brand-600"
                aria-label="Tillarni almashtirish"
                @click="swap"
            >
                <ArrowLeftRight class="size-4" />
            </button>
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-semibold text-navy-600"
                    >Tarjima tili</span
                >
                <SelectInput v-model="form.target_language">
                    <option
                        v-for="lang in languages"
                        :key="lang.value"
                        :value="lang.value"
                        :disabled="lang.value === form.source_language"
                    >
                        {{ lang.label }}
                    </option>
                </SelectInput>
            </label>
        </div>
        <label v-else>
            <span class="mb-1.5 block text-xs font-semibold text-navy-600"
                >Matn tili</span
            >
            <SelectInput v-model="form.source_language">
                <option
                    v-for="lang in languages"
                    :key="lang.value"
                    :value="lang.value"
                >
                    {{ lang.label }}
                </option>
            </SelectInput>
        </label>
        <p
            v-if="form.errors.target_language"
            class="-mt-2 text-xs text-red-600"
        >
            {{ form.errors.target_language }}
        </p>

        <!-- Matn -->
        <div>
            <div class="relative">
                <textarea
                    v-model="form.text"
                    :class="
                        cn(
                            textareaClass,
                            'min-h-64 resize-y pb-8 font-[inherit] text-[13px]',
                        )
                    "
                    :placeholder="labels[type].placeholder"
                    :aria-invalid="!!form.errors.text || tooLong"
                />
                <div
                    class="pointer-events-none absolute right-3 bottom-2 left-3 flex items-center justify-between text-[11px]"
                >
                    <button
                        v-if="form.text"
                        type="button"
                        class="pointer-events-auto inline-flex items-center gap-1 text-navy-400 transition-colors hover:text-red-600"
                        @click="form.text = ''"
                    >
                        <Eraser class="size-3.5" /> Tozalash
                    </button>
                    <span v-else />
                    <span
                        :class="
                            cn(
                                'tabular-nums',
                                tooLong
                                    ? 'font-semibold text-red-600'
                                    : 'text-navy-400',
                            )
                        "
                        >{{ formatNumber(length) }} /
                        {{ formatNumber(maxChars) }}</span
                    >
                </div>
            </div>
            <p v-if="form.errors.text" class="mt-1.5 text-xs text-red-600">
                {{ form.errors.text }}
            </p>
        </div>

        <!-- Tekshirish turlari -->
        <fieldset v-if="type === 'spell_check'" class="grid grid-cols-1 gap-2">
            <legend class="mb-2 text-xs font-semibold text-navy-600">
                Tekshirish turlari
            </legend>
            <label
                v-for="check in checks"
                :key="check.value"
                class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] text-navy-800 transition-colors hover:bg-brand-50/60"
            >
                <input
                    v-model="form.checks"
                    type="checkbox"
                    :value="check.value"
                    class="size-4 rounded border-line text-brand-600 accent-brand-600"
                />
                {{ check.label }}
            </label>
        </fieldset>

        <button
            type="submit"
            :disabled="
                disabled || form.processing || !form.text.trim() || tooLong
            "
            :class="cn(primaryButtonClass, 'h-11 w-full')"
        >
            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
            <Sparkles v-else class="size-4" />
            {{ labels[type].button }}
        </button>
    </form>
</template>

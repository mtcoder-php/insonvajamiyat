<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ChevronDown, LoaderCircle, RotateCcw, Save } from '@lucide/vue';
import { ref } from 'vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiPromptTemplate } from '@/types';
import { studios } from './aiMeta';
import { t } from '@/lib/i18n';

/**
 * AI ko'rsatmasi (system prompt) tahrirlovchisi. O'rinbosarlar: {source_language}, {target_language}, {checks}, {text}.
 */
const props = defineProps<{ prompt: AiPromptTemplate }>();

const open = ref(false);
const form = useForm({
    system_prompt: props.prompt.systemPrompt,
    user_prompt_template: props.prompt.userPromptTemplate ?? '',
    model: props.prompt.model ?? '',
    temperature: props.prompt.temperature,
    max_tokens: props.prompt.maxTokens,
    is_active: props.prompt.isActive,
});

function save(): void {
    form.put(props.prompt.updateUrl, {
        preserveScroll: true,
        only: ['settings'],
    });
}

function reset(): void {
    if (
        !confirm(
            t(
                "Shablon standart matnga qaytarilsinmi? Joriy o'zgarishlar yo'qoladi.",
            ),
        )
    ) {
        return;
    }

    router.post(
        props.prompt.resetUrl,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <article
        class="overflow-hidden rounded-xl border border-line bg-white transition-shadow hover:shadow-[0_10px_24px_-18px_rgba(0,36,66,0.4)]"
    >
        <button
            type="button"
            class="flex w-full items-center gap-3 px-4 py-3 text-left"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span
                :class="
                    cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        studios[prompt.type].soft,
                    )
                "
            >
                <component :is="studios[prompt.type].icon" class="size-4" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[13.5px] font-semibold text-navy-950">{{
                    prompt.name
                }}</span>
                <span class="block text-[11px] text-navy-400">
                    {{
                        prompt.isActive
                            ? t('Faol')
                            : "O'chirilgan (standart matn ishlatiladi)"
                    }}
                    <template v-if="prompt.updatedAt">
                        · {{ formatDateTime(prompt.updatedAt)
                        }}{{
                            prompt.updatedBy ? ` · ${prompt.updatedBy}` : ''
                        }}</template
                    >
                </span>
            </span>
            <ChevronDown
                :class="
                    cn(
                        'size-4 text-navy-400 transition-transform',
                        open && 'rotate-180',
                    )
                "
            />
        </button>

        <form
            v-if="open"
            class="grid gap-3 border-t border-line px-4 py-4"
            @submit.prevent="save"
        >
            <label>
                <span
                    class="mb-1.5 block text-xs font-semibold text-navy-600"
                    >{{ t("Ko'rsatma (system prompt)") }}</span
                >
                <textarea
                    v-model="form.system_prompt"
                    :class="
                        cn(
                            textareaClass,
                            'min-h-72 font-mono text-[12px] leading-relaxed',
                        )
                    "
                    :aria-invalid="!!form.errors.system_prompt"
                />
                <span
                    v-if="form.errors.system_prompt"
                    class="mt-1 block text-xs text-red-600"
                    >{{ form.errors.system_prompt }}</span
                >
                <span class="mt-1 block text-[11px] text-navy-400">
                    {{ t("O'rinbosarlar:") }}
                    <code>{source_language}</code>,
                    <code>{target_language}</code>, <code>{checks}</code>
                </span>
            </label>
            <label>
                <span
                    class="mb-1.5 block text-xs font-semibold text-navy-600"
                    >{{ t('Foydalanuvchi xabari shabloni') }}</span
                >
                <textarea
                    v-model="form.user_prompt_template"
                    :class="cn(textareaClass, 'min-h-16 font-mono text-[12px]')"
                />
                <span class="mt-1 block text-[11px] text-navy-400"
                    ><code>{text}</code>
                    {{ t("— matn bo'lagi shu joyga qo'yiladi") }}</span
                >
            </label>
            <div class="grid gap-3 sm:grid-cols-4">
                <label class="sm:col-span-2">
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Model (ixtiyoriy)') }}</span
                    >
                    <input
                        v-model="form.model"
                        :class="inputClass"
                        :placeholder="t('Umumiy sozlamadagi model')"
                    />
                    <span
                        v-if="form.errors.model"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.model }}</span
                    >
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Temperature') }}</span
                    >
                    <input
                        v-model.number="form.temperature"
                        type="number"
                        step="0.05"
                        min="0"
                        max="1"
                        :class="inputClass"
                    />
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Maks. token') }}</span
                    >
                    <input
                        v-model.number="form.max_tokens"
                        type="number"
                        min="256"
                        max="64000"
                        step="256"
                        :class="inputClass"
                    />
                </label>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label
                    class="flex cursor-pointer items-center gap-2 text-[13px] text-navy-800"
                >
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 accent-brand-600"
                    />
                    {{ t("Faol (o'chirilsa — standart matn ishlatiladi)") }}
                </label>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-lg border border-line bg-white px-3 text-[13px] font-semibold text-navy-600 transition-all hover:border-amber-300 hover:text-amber-700"
                        @click="reset"
                    >
                        <RotateCcw class="size-4" />
                        {{ t('Standartga qaytarish') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.isDirty"
                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-60"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <Save v-else class="size-4" /> {{ t('Saqlash') }}
                    </button>
                </div>
            </div>
        </form>
    </article>
</template>

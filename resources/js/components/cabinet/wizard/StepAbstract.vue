<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
import { computed, ref } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import { textareaClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type {
    ArticleDraft,
    LocaleCode,
    WizardLimits,
    WizardOptions,
} from '@/types';

/**
 * 3-bosqich: annotatsiya — maqola tilida majburiy, qolgan tillarda ixtiyoriy
 * (shu tillardagi sarlavha bilan birga); adabiyotlar ro'yxati.
 */
const props = defineProps<{
    article: ArticleDraft;
    options: WizardOptions;
    limits: WizardLimits;
    prevHref: string | null;
}>();

const main = props.article.language;

// Maqola tili birinchi
const languages = computed(() =>
    [...props.options.languages].sort(
        (a, b) => Number(b.code === main) - Number(a.code === main),
    ),
);

const active = ref<LocaleCode>(main);

const form = useForm({
    title: {
        uz: props.article.title.uz ?? '',
        ru: props.article.title.ru ?? '',
        en: props.article.title.en ?? '',
    },
    abstract: {
        uz: props.article.abstract.uz ?? '',
        ru: props.article.abstract.ru ?? '',
        en: props.article.abstract.en ?? '',
    },
    references: props.article.references ?? '',
});

const errors = computed(() => form.errors as Record<string, string>);

const filled = (code: LocaleCode): boolean =>
    form.abstract[code].trim().length > 0;

const hasError = (code: LocaleCode): boolean =>
    !!errors.value[`abstract.${code}`] || !!errors.value[`title.${code}`];

function save(stay: boolean): void {
    form.transform((data) => ({ ...data, stay })).put(
        props.article.urls.abstract,
        {
            preserveScroll: true,
            onError: (errs) => {
                const failed = languages.value.find(
                    (language) =>
                        errs[`abstract.${language.code}`] ||
                        errs[`title.${language.code}`],
                );

                if (failed) {
                    active.value = failed.code;
                }
            },
        },
    );
}
</script>

<template>
    <form class="grid gap-5" @submit.prevent="save(false)">
        <div
            class="flex flex-wrap gap-1 rounded-xl border border-line bg-[#f5f8fc] p-1"
            role="tablist"
        >
            <button
                v-for="language in languages"
                :key="language.code"
                type="button"
                role="tab"
                :aria-selected="active === language.code"
                :class="
                    cn(
                        'flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-[13px] font-semibold transition-all',
                        active === language.code
                            ? 'bg-white text-brand-700 shadow-[0_2px_8px_-3px_rgba(0,36,66,0.35)]'
                            : 'text-navy-500 hover:text-navy-900',
                    )
                "
                @click="active = language.code"
            >
                {{ language.label }}
                <span
                    v-if="language.code === main"
                    class="rounded bg-brand-600 px-1.5 py-px text-[10px] text-white"
                    >asosiy</span
                >
                <span
                    :class="
                        cn(
                            'size-1.5 rounded-full',
                            hasError(language.code)
                                ? 'bg-red-500'
                                : filled(language.code)
                                  ? 'bg-emerald-500'
                                  : 'bg-navy-200',
                        )
                    "
                    aria-hidden="true"
                />
            </button>
        </div>

        <template v-for="language in languages" :key="language.code">
            <div v-show="active === language.code" class="grid gap-5">
                <div
                    v-if="language.code === main"
                    class="rounded-lg border border-line bg-[#f8fafd] px-4 py-3"
                >
                    <p class="text-[11px] font-medium text-navy-500">
                        Sarlavha (1-bosqichda kiritilgan)
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-navy-900">
                        {{ article.title[main] }}
                    </p>
                </div>
                <FormField
                    v-else
                    :label="`Sarlavha (${language.label})`"
                    :error="errors[`title.${language.code}`]"
                    hint="Ixtiyoriy — sarlavhaning tarjimasi"
                >
                    <textarea
                        v-model="form.title[language.code]"
                        rows="2"
                        :maxlength="limits.titleMax"
                        :class="cn(textareaClass, 'min-h-16')"
                    />
                </FormField>

                <FormField
                    :label="`Annotatsiya (${language.label})`"
                    :required="language.code === main"
                    :error="errors[`abstract.${language.code}`]"
                >
                    <textarea
                        v-model="form.abstract[language.code]"
                        rows="9"
                        :maxlength="limits.abstractMax"
                        :aria-invalid="!!errors[`abstract.${language.code}`]"
                        :placeholder="
                            language.code === main
                                ? 'Tadqiqotning maqsadi, usullari, asosiy natijalari va xulosalari...'
                                : 'Ixtiyoriy'
                        "
                        :class="cn(textareaClass, 'min-h-52')"
                    />
                    <p
                        :class="
                            cn(
                                'text-right text-[11px] tabular-nums',
                                language.code === main &&
                                    form.abstract[language.code].trim().length <
                                        limits.abstractMin
                                    ? 'text-amber-600'
                                    : 'text-navy-400',
                            )
                        "
                    >
                        {{ form.abstract[language.code].trim().length }} /
                        {{ limits.abstractMax }}
                        <template v-if="language.code === main">
                            (kamida {{ limits.abstractMin }})
                        </template>
                    </p>
                </FormField>
            </div>
        </template>

        <p
            class="flex items-start gap-2 rounded-lg bg-brand-50/60 px-3 py-2.5 text-xs leading-relaxed text-brand-800"
        >
            <Languages class="mt-0.5 size-4 shrink-0" />
            Annotatsiyani uch tilda kiritish tavsiya etiladi — maqola xalqaro
            bazalarda va sayt katalogida shu tillarda ko'rinadi.
        </p>

        <FormField
            label="Adabiyotlar ro'yxati"
            :error="form.errors.references"
            hint="Ixtiyoriy. Har bir manba yangi qatordan. To'liq ro'yxat maqola faylida bo'lishi kerak."
        >
            <textarea
                v-model="form.references"
                rows="6"
                :maxlength="limits.referencesMax"
                :class="cn(textareaClass, 'min-h-36 font-mono text-[13px]')"
            />
        </FormField>

        <WizardFooter
            :prev-href="prevHref"
            :processing="form.processing"
            @next="save(false)"
            @draft="save(true)"
        />
    </form>
</template>

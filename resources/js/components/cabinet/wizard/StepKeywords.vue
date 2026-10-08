<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import KeywordInput from '@/components/cabinet/wizard/KeywordInput.vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import type { ArticleDraft, WizardLimits, WizardOptions } from '@/types';
import { t } from '@/lib/i18n';

/**
 * 4-bosqich: kalit so'zlar — maqola tilida 3–10 ta majburiy, boshqa tillarda ixtiyoriy.
 */
const props = defineProps<{
    article: ArticleDraft;
    options: WizardOptions;
    limits: WizardLimits;
    prevHref: string | null;
}>();

const main = props.article.language;

const languages = computed(() =>
    [...props.options.languages].sort(
        (a, b) => Number(b.code === main) - Number(a.code === main),
    ),
);

const form = useForm({
    keywords: {
        uz: [...props.article.keywords.uz],
        ru: [...props.article.keywords.ru],
        en: [...props.article.keywords.en],
    },
});

const errors = computed(() => form.errors as Record<string, string>);

const firstError = (code: string): string | undefined =>
    errors.value[`keywords.${code}`] ??
    Object.entries(errors.value).find(([key]) =>
        key.startsWith(`keywords.${code}.`),
    )?.[1];

function save(stay: boolean): void {
    form.transform((data) => ({ ...data, stay })).put(
        props.article.urls.keywords,
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="save(false)">
        <FormField
            v-for="language in languages"
            :key="language.code"
            :label="
                t('Kalit so\'zlar (:language)', { language: language.label })
            "
            :for="`keywords-${language.code}`"
            :required="language.code === main"
            :error="firstError(language.code)"
        >
            <KeywordInput
                :id="`keywords-${language.code}`"
                v-model="form.keywords[language.code]"
                :max="limits.keywordsMax"
                :max-length="limits.keywordMaxLength"
                :invalid="!!firstError(language.code)"
            />
            <p class="flex justify-between text-[11px] text-navy-400">
                <span>
                    {{
                        language.code === main
                            ? t("Kamida :min ta, ko'pi bilan :max ta", {
                                  min: limits.keywordsMin,
                                  max: limits.keywordsMax,
                              })
                            : t('Ixtiyoriy')
                    }}
                </span>
                <span class="tabular-nums">
                    {{ form.keywords[language.code].length }} /
                    {{ limits.keywordsMax }}
                </span>
            </p>
        </FormField>

        <WizardFooter
            :prev-href="prevHref"
            :processing="form.processing"
            @next="save(false)"
            @draft="save(true)"
        />
    </form>
</template>

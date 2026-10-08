<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarClock, Check } from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import { inputClass, textareaClass } from '@/lib/formStyles';
import { formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import { store } from '@/routes/cabinet/articles';
import type {
    ArticleDraft,
    LocaleCode,
    WizardLimits,
    WizardOptions,
} from '@/types';
import { t } from '@/lib/i18n';

/**
 * 1-bosqich: maqola turi (narxi bilan), ilmiy yo'nalish, til, sarlavha, UDK.
 * Birinchi saqlashda qoralama yaratiladi.
 */
const props = defineProps<{
    article: ArticleDraft | null;
    options: WizardOptions;
    limits: WizardLimits;
}>();

const form = useForm({
    article_type_id:
        props.article?.articleTypeId ?? props.options.types[0]?.id ?? null,
    subject_id: props.article?.subjectId ?? null,
    language: (props.article?.language ?? 'uz') as LocaleCode,
    title: props.article
        ? (props.article.title[props.article.language] ?? '')
        : '',
    udc: props.article?.udc ?? '',
});

const titleLength = computed(() => form.title.trim().length);

function save(stay: boolean): void {
    form.transform((data) => ({ ...data, stay }));

    if (props.article) {
        form.put(props.article.urls.details, { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="save(false)">
        <fieldset class="grid gap-2">
            <legend class="mb-2 text-[13px] font-semibold text-navy-800">
                {{ t('Maqola turi') }} <span class="text-red-500">*</span>
            </legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <label
                    v-for="type in options.types"
                    :key="type.id"
                    :class="
                        cn(
                            'group relative flex cursor-pointer flex-col gap-1.5 rounded-xl border p-4 transition-all duration-200 hover:-translate-y-0.5',
                            form.article_type_id === type.id
                                ? 'border-brand-500 bg-brand-50/60 shadow-[0_10px_24px_-16px_rgba(0,108,246,0.9)] ring-1 ring-brand-500'
                                : 'border-line bg-white hover:border-brand-200 hover:shadow-[0_10px_24px_-18px_rgba(0,36,66,0.4)]',
                        )
                    "
                >
                    <input
                        v-model="form.article_type_id"
                        type="radio"
                        name="article_type_id"
                        :value="type.id"
                        class="sr-only"
                    />
                    <span class="flex items-start justify-between gap-3">
                        <span class="text-sm font-bold text-navy-950">
                            {{ type.name }}
                        </span>
                        <span
                            :class="
                                cn(
                                    'flex size-5 shrink-0 items-center justify-center rounded-full border transition-colors',
                                    form.article_type_id === type.id
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-navy-200 bg-white',
                                )
                            "
                        >
                            <Check
                                v-if="form.article_type_id === type.id"
                                class="size-3"
                                :stroke-width="3"
                            />
                        </span>
                    </span>
                    <span
                        v-if="type.description"
                        class="text-xs leading-relaxed text-navy-600"
                    >
                        {{ type.description }}
                    </span>
                    <span
                        class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-xs"
                    >
                        <span
                            :class="
                                cn(
                                    'font-bold',
                                    type.price > 0
                                        ? 'text-navy-900'
                                        : 'text-emerald-600',
                                )
                            "
                        >
                            {{
                                type.price > 0
                                    ? formatSum(type.price)
                                    : t('Bepul')
                            }}
                        </span>
                        <span
                            v-if="type.reviewDays"
                            class="inline-flex items-center gap-1 text-navy-500"
                        >
                            <CalendarClock class="size-3.5" />
                            {{
                                t("~:days kunda ko'rib chiqiladi", {
                                    days: type.reviewDays,
                                })
                            }}
                        </span>
                    </span>
                </label>
            </div>
            <p
                v-if="form.errors.article_type_id"
                class="text-xs font-medium text-red-600"
            >
                {{ form.errors.article_type_id }}
            </p>
            <p v-if="!options.types.length" class="text-sm text-amber-700">
                {{
                    t(
                        "Maqola turlari hali sozlanmagan. Tahririyat bilan bog'laning.",
                    )
                }}
            </p>
        </fieldset>

        <div class="grid gap-5 md:grid-cols-2">
            <FormField
                :label="t('Ilmiy yo\'nalish')"
                for="subject_id"
                required
                :error="form.errors.subject_id"
            >
                <SelectInput
                    id="subject_id"
                    v-model="form.subject_id"
                    :aria-invalid="!!form.errors.subject_id"
                >
                    <option :value="null" disabled>
                        {{ t("Yo'nalishni tanlang") }}
                    </option>
                    <option
                        v-for="subject in options.subjects"
                        :key="subject.id"
                        :value="subject.id"
                    >
                        {{ subject.name }}
                    </option>
                </SelectInput>
            </FormField>

            <FormField
                :label="t('Maqola tili')"
                required
                :error="form.errors.language"
                :hint="
                    t(
                        'Maqola matni yozilgan til — annotatsiya va kalit so\'zlar shu tilda majburiy.',
                    )
                "
            >
                <div
                    class="grid grid-cols-3 gap-1 rounded-lg border border-line bg-[#f5f8fc] p-1"
                    role="radiogroup"
                >
                    <button
                        v-for="language in options.languages"
                        :key="language.code"
                        type="button"
                        role="radio"
                        :aria-checked="form.language === language.code"
                        :class="
                            cn(
                                'h-8 rounded-md text-[13px] font-semibold transition-all',
                                form.language === language.code
                                    ? 'bg-white text-brand-700 shadow-[0_2px_6px_-2px_rgba(0,36,66,0.3)]'
                                    : 'text-navy-500 hover:text-navy-900',
                            )
                        "
                        @click="form.language = language.code"
                    >
                        {{ language.label }}
                    </button>
                </div>
            </FormField>
        </div>

        <FormField
            :label="t('Maqola sarlavhasi')"
            for="title"
            required
            :error="form.errors.title"
        >
            <textarea
                id="title"
                v-model="form.title"
                rows="2"
                :maxlength="limits.titleMax"
                :placeholder="
                    t(
                        'Masalan: O\'rta asrlarda Movarounnahr shaharlarining ijtimoiy tuzilishi',
                    )
                "
                :aria-invalid="!!form.errors.title"
                :class="cn(textareaClass, 'min-h-20 font-medium')"
            />
            <p class="text-right text-[11px] text-navy-400 tabular-nums">
                {{ titleLength }} / {{ limits.titleMax }}
            </p>
        </FormField>

        <FormField
            label="UDK"
            for="udc"
            :error="form.errors.udc"
            :hint="
                t(
                    'Universal o\'nlik klassifikatsiya indeksi (ixtiyoriy), masalan: 94(575.1)',
                )
            "
            class="md:max-w-xs"
        >
            <input
                id="udc"
                v-model="form.udc"
                type="text"
                maxlength="50"
                :class="inputClass"
            />
        </FormField>

        <WizardFooter
            :processing="form.processing"
            :show-draft="!!article"
            :next-label="
                article
                    ? t('Saqlash va davom etish')
                    : t('Qoralamani yaratish va davom etish')
            "
            @next="save(false)"
            @draft="save(true)"
        />
    </form>
</template>

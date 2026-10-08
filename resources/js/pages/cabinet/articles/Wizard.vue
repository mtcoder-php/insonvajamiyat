<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { computed } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import StepAbstract from '@/components/cabinet/wizard/StepAbstract.vue';
import StepAuthors from '@/components/cabinet/wizard/StepAuthors.vue';
import StepDetails from '@/components/cabinet/wizard/StepDetails.vue';
import StepFiles from '@/components/cabinet/wizard/StepFiles.vue';
import StepKeywords from '@/components/cabinet/wizard/StepKeywords.vue';
import StepReview from '@/components/cabinet/wizard/StepReview.vue';
import StepSubmit from '@/components/cabinet/wizard/StepSubmit.vue';
import WizardAside from '@/components/cabinet/wizard/WizardAside.vue';
import WizardStepper from '@/components/cabinet/wizard/WizardStepper.vue';
import { create, index } from '@/routes/cabinet/articles';
import type { WizardProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Yangi maqola yuborish — 7 bosqichli forma
 * (App\Http\Controllers\Cabinet\ArticleSubmissionController).
 * Har bir bosqich o'z formasi bilan alohida saqlanadi; 1-bosqichdan keyin qoralama yaratiladi.
 */
const props = defineProps<WizardProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Yangi maqola yuborish', href: create() }],
    },
});

const descriptions: Record<number, string> = {
    1: tk("Maqola turi, ilmiy yo'nalishi, tili va sarlavhasini kiriting."),
    2: tk(
        "Barcha mualliflarni maqoladagi tartibda kiriting va aloqa uchun mas'ul muallifni belgilang.",
    ),
    3: tk('Annotatsiyani maqola tilida kiriting; boshqa tillarda — ixtiyoriy.'),
    4: tk("Maqola mavzusini ochib beruvchi kalit so'zlarni kiriting."),
    5: tk("Maqola faylini va kerak bo'lsa qo'shimcha materiallarni yuklang."),
    6: tk("Kiritilgan ma'lumotlarni tekshiring."),
    7: tk('Shartlarga rozilik bildiring va maqolani tahririyatga yuboring.'),
};

const current = computed(
    () =>
        props.steps.find((step) => step.number === props.step) ??
        props.steps[0],
);

const stepHref = (step: number): string | null =>
    props.article
        ? `${props.article.urls.edit}?step=${step}`
        : step === 1
          ? create.url()
          : null;

const prevHref = computed(() =>
    props.step > 1 ? stepHref(props.step - 1) : null,
);
const nextHref = computed(() =>
    props.step < props.steps.length ? stepHref(props.step + 1) : null,
);

/** Bosqich → to'liqmi (6 — 1–5 hammasi to'liq bo'lsa) */
const done = computed<Record<number, boolean>>(() => {
    const result: Record<number, boolean> = {};

    if (!props.checklist) {
        return result;
    }

    for (const step of props.steps) {
        const issues = props.checklist[String(step.number)];
        result[step.number] =
            issues !== undefined ? issues.length === 0 : false;
    }

    result[6] = props.isComplete;

    return result;
});
</script>

<template>
    <Head :title="t('Yangi maqola yuborish')" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            :title="t('Yangi maqola yuborish')"
            :description="
                t(
                    'Maqolangizni 7 bosqichda to\'ldiring. Ma\'lumotlar har bir bosqichda qoralama sifatida saqlanadi.',
                )
            "
            :icon="Send"
            :quote="false"
            :breadcrumbs="[
                { title: t('Mening maqolalarim'), href: index() },
                { title: t('Yangi maqola'), href: create() },
            ]"
        />

        <WizardStepper
            :steps="steps"
            :current="step"
            :done="done"
            :href="stepHref"
        />

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <DashCard class="sm:p-6">
                <header class="mb-6 flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 font-sans text-base font-bold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]"
                    >
                        {{ current.number }}
                    </span>
                    <div>
                        <h2 class="font-sans text-lg font-bold text-navy-950">
                            {{ current.label }}
                        </h2>
                        <p class="text-[13px] text-navy-600">
                            {{ t(descriptions[current.number] ?? '') }}
                        </p>
                    </div>
                </header>

                <StepDetails
                    v-if="step === 1 || !article"
                    :key="`details-${article?.updatedAt}`"
                    :article="article"
                    :options="options"
                    :limits="limits"
                />
                <StepAuthors
                    v-else-if="step === 2"
                    :key="`authors-${article.updatedAt}`"
                    :article="article"
                    :me="me"
                    :limits="limits"
                    :prev-href="prevHref"
                />
                <StepAbstract
                    v-else-if="step === 3"
                    :key="`abstract-${article.updatedAt}`"
                    :article="article"
                    :options="options"
                    :limits="limits"
                    :prev-href="prevHref"
                />
                <StepKeywords
                    v-else-if="step === 4"
                    :key="`keywords-${article.updatedAt}`"
                    :article="article"
                    :options="options"
                    :limits="limits"
                    :prev-href="prevHref"
                />
                <StepFiles
                    v-else-if="step === 5"
                    :article="article"
                    :limits="limits"
                    :prev-href="prevHref"
                    :next-href="nextHref"
                />
                <StepReview
                    v-else-if="step === 6"
                    :article="article"
                    :options="options"
                    :steps="steps"
                    :checklist="checklist ?? {}"
                    :step-href="stepHref"
                    :prev-href="prevHref"
                    :next-href="nextHref"
                />
                <StepSubmit
                    v-else
                    :article="article"
                    :options="options"
                    :consents="consents"
                    :is-complete="isComplete"
                    :links="links"
                    :prev-href="prevHref"
                    :review-href="stepHref(6)"
                />
            </DashCard>

            <WizardAside :step="step" :article="article" :links="links" />
        </div>
    </div>
</template>

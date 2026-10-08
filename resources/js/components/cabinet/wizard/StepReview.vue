<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, PenLine, Star } from '@lucide/vue';
import { computed } from 'vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import { formatFileSize, formatSum } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ArticleDraft, WizardOptions, WizardStepInfo } from '@/types';
import { t } from '@/lib/i18n';

/**
 * 6-bosqich: kiritilgan ma'lumotlarni ko'rib chiqish; to'ldirilmagan bosqichlar
 * kamchiliklari bilan ko'rsatiladi va ularga qaytish havolasi beriladi.
 */
const props = defineProps<{
    article: ArticleDraft;
    options: WizardOptions;
    steps: WizardStepInfo[];
    checklist: Record<string, string[]>;
    stepHref: (step: number) => string | null;
    prevHref: string | null;
    nextHref: string | null;
}>();

const main = props.article.language;
const type = computed(() =>
    props.options.types.find((item) => item.id === props.article.articleTypeId),
);
const subject = computed(
    () =>
        props.options.subjects.find(
            (item) => item.id === props.article.subjectId,
        )?.name,
);
const languageLabel = (code: string): string =>
    props.options.languages.find((item) => item.code === code)?.label ?? code;

const sections = computed(() => props.steps.filter((step) => step.number <= 5));
const issues = (step: number): string[] => props.checklist[String(step)] ?? [];

const otherLanguages = computed(() =>
    props.options.languages.filter((language) => language.code !== main),
);
</script>

<template>
    <div class="grid gap-4">
        <section
            v-for="step in sections"
            :key="step.key"
            :class="
                cn(
                    'rounded-xl border p-4 transition-shadow hover:shadow-[0_12px_28px_-22px_rgba(0,36,66,0.5)] sm:p-5',
                    issues(step.number).length
                        ? 'border-amber-200 bg-amber-50/30'
                        : 'border-line bg-white',
                )
            "
        >
            <header class="mb-3 flex items-center gap-2.5">
                <CircleAlert
                    v-if="issues(step.number).length"
                    class="size-5 text-amber-500"
                />
                <CircleCheck v-else class="size-5 text-emerald-500" />
                <h3 class="flex-1 text-sm font-bold text-navy-950">
                    {{ step.number }}. {{ step.label }}
                </h3>
                <Link
                    v-if="stepHref(step.number)"
                    :href="stepHref(step.number)!"
                    class="group inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                >
                    <PenLine
                        class="size-3.5 transition-transform group-hover:-rotate-12"
                    />
                    {{ t('Tahrirlash') }}
                </Link>
            </header>

            <ul
                v-if="issues(step.number).length"
                class="mb-3 grid gap-1 text-[13px] text-amber-800"
            >
                <li
                    v-for="issue in issues(step.number)"
                    :key="issue"
                    class="flex gap-1.5"
                >
                    <span aria-hidden="true">•</span>{{ issue }}
                </li>
            </ul>

            <!-- 1. Ma'lumotlar -->
            <dl
                v-if="step.number === 1"
                class="grid gap-x-6 gap-y-2 text-[13px] sm:grid-cols-2"
            >
                <div class="sm:col-span-2">
                    <dt class="text-[11px] text-navy-500">
                        {{ t('Sarlavha') }}
                    </dt>
                    <dd class="font-semibold text-navy-900">
                        {{ article.title[main] }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] text-navy-500">
                        {{ t('Maqola turi') }}
                    </dt>
                    <dd class="font-medium text-navy-900">
                        {{ type?.name ?? '—' }}
                        <span v-if="type" class="text-navy-500">
                            ·
                            {{
                                type.price > 0
                                    ? formatSum(type.price)
                                    : t('Bepul')
                            }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] text-navy-500">
                        {{ t("Ilmiy yo'nalish") }}
                    </dt>
                    <dd class="font-medium text-navy-900">
                        {{ subject ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] text-navy-500">
                        {{ t('Maqola tili') }}
                    </dt>
                    <dd class="font-medium text-navy-900">
                        {{ languageLabel(main) }}
                    </dd>
                </div>
                <div v-if="article.udc">
                    <dt class="text-[11px] text-navy-500">UDK</dt>
                    <dd class="font-medium text-navy-900">{{ article.udc }}</dd>
                </div>
            </dl>

            <!-- 2. Mualliflar -->
            <ol v-else-if="step.number === 2" class="grid gap-1.5 text-[13px]">
                <li
                    v-for="(author, index) in article.authors"
                    :key="index"
                    class="flex flex-wrap items-baseline gap-x-2"
                >
                    <span class="font-semibold text-navy-900 tabular-nums"
                        >{{ index + 1 }}.</span
                    >
                    <span class="font-semibold text-navy-900">
                        {{ author.last_name }} {{ author.first_name }}
                        {{ author.middle_name }}
                    </span>
                    <Star
                        v-if="author.is_corresponding"
                        class="size-3.5 self-center fill-gold-400 text-gold-500"
                        :aria-label="t('Aloqa uchun mas\'ul')"
                    />
                    <span class="text-navy-500">
                        {{
                            [author.organization, author.email]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </span>
                </li>
            </ol>

            <!-- 3. Annotatsiya -->
            <div v-else-if="step.number === 3" class="grid gap-2 text-[13px]">
                <p
                    v-if="article.abstract[main]"
                    class="line-clamp-4 leading-relaxed text-navy-700"
                >
                    {{ article.abstract[main] }}
                </p>
                <p class="flex flex-wrap gap-1.5 text-[11px]">
                    <span
                        v-for="language in otherLanguages"
                        :key="language.code"
                        :class="
                            cn(
                                'rounded px-1.5 py-0.5 font-medium',
                                article.abstract[language.code]
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-navy-50 text-navy-400',
                            )
                        "
                    >
                        {{ language.label }}:
                        {{ article.abstract[language.code] ? 'bor' : "yo'q" }}
                    </span>
                </p>
            </div>

            <!-- 4. Kalit so'zlar -->
            <div v-else-if="step.number === 4" class="flex flex-wrap gap-1.5">
                <template
                    v-for="language in options.languages"
                    :key="language.code"
                >
                    <span
                        v-for="word in article.keywords[language.code]"
                        :key="`${language.code}-${word}`"
                        class="rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-100 ring-inset"
                    >
                        {{ word }}
                    </span>
                </template>
            </div>

            <!-- 5. Fayllar -->
            <ul v-else-if="step.number === 5" class="grid gap-1 text-[13px]">
                <li
                    v-for="file in article.files"
                    :key="file.uuid"
                    class="flex flex-wrap gap-x-2"
                >
                    <span class="font-medium text-navy-900">{{
                        file.name
                    }}</span>
                    <span class="text-navy-500">
                        {{ file.typeLabel }} · {{ formatFileSize(file.size) }}
                    </span>
                </li>
            </ul>
        </section>

        <WizardFooter
            class="mt-2"
            :prev-href="prevHref"
            :show-draft="false"
            next-label="Yuborishga o'tish"
            @next="nextHref && router.visit(nextHref)"
        />
    </div>
</template>

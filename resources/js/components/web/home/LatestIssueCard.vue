<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, FileText, Files } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import IssueCover from '@/components/web/IssueCover.vue';
import { cn } from '@/lib/utils';
import { index as issuesIndex } from '@/routes/issues';
import type { LatestIssue } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "So'nggi son" (home.png): katta muqova, son raqami, nomi,
 * Mundarija / To'liq son / DOI havolalari (ikonkalari bilan), qisqa tavsif.
 * Fayl hali yuklanmagan bo'lsa — havola xira ko'rinishda, bosilmaydi.
 */
const props = defineProps<{ issue: LatestIssue | null }>();

type Resource = {
    key: string;
    label: string;
    href: string | null;
    icon?: Component;
    external?: boolean;
};

const resources = computed<Resource[]>(() => {
    const issue = props.issue;

    if (!issue) {
        return [];
    }

    const items: Resource[] = [
        {
            key: 'toc',
            label: t('Mundarija (PDF)'),
            href: issue.tocUrl,
            icon: FileText,
        },
        {
            key: 'pdf',
            label: t("To'liq son (PDF)"),
            href: issue.pdfUrl,
            icon: Files,
        },
    ];

    if (issue.doi) {
        items.push({
            key: 'doi',
            label: `DOI: ${issue.doi}`,
            href: `https://doi.org/${issue.doi}`,
            external: true,
        });
    }

    return items;
});
</script>

<template>
    <HomeCard size="lg" :title="t('So\'nggi son')" :href="issuesIndex()">
        <div
            v-if="issue"
            class="flex flex-col gap-7 sm:flex-row sm:items-start"
        >
            <!-- Muqova -->
            <Link
                :href="issue.url"
                class="group relative mx-auto block w-52 shrink-0 sm:mx-0 sm:w-56 xl:w-64"
                :aria-label="t(':label sonini ochish', { label: issue.label })"
            >
                <span
                    class="absolute inset-x-4 -bottom-3 h-6 rounded-full bg-navy-950/25 blur-xl transition-all duration-500 group-hover:inset-x-2 group-hover:bg-navy-950/35"
                    aria-hidden="true"
                />
                <IssueCover
                    :src="issue.coverUrl"
                    :number="issue.number"
                    :year="issue.year"
                    class="relative rounded-sm shadow-[0_14px_30px_-14px_rgba(0,30,60,0.55)] transition-all duration-500 ease-out group-hover:-translate-y-1.5 group-hover:shadow-[0_26px_44px_-16px_rgba(0,30,60,0.6)]"
                />
            </Link>

            <!-- Ma'lumot -->
            <div class="flex min-w-0 flex-1 flex-col sm:pt-1">
                <p
                    class="font-serif text-2xl font-bold text-navy-950 sm:text-[28px]"
                >
                    {{ issue.label }}
                </p>
                <h3
                    class="mt-2 font-serif text-lg leading-snug font-normal text-navy-800 sm:text-xl"
                >
                    <Link
                        :href="issue.url"
                        class="transition-colors hover:text-brand-700"
                    >
                        {{
                            issue.title ||
                            t('«:name» ilmiy jurnali', {
                                name: $page.props.journal.name,
                            })
                        }}
                    </Link>
                </h3>

                <ul class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <li v-for="item in resources" :key="item.key">
                        <component
                            :is="item.href ? 'a' : 'span'"
                            :href="item.href ?? undefined"
                            :target="item.href ? '_blank' : undefined"
                            :rel="
                                item.href
                                    ? item.external
                                        ? 'noopener noreferrer'
                                        : 'noopener'
                                    : undefined
                            "
                            :title="
                                item.href
                                    ? undefined
                                    : t('Fayl tez orada yuklanadi')
                            "
                            :class="
                                cn(
                                    'group/res inline-flex items-center gap-2 text-[13px] text-navy-800 transition-colors',
                                    item.href
                                        ? 'hover:text-brand-700'
                                        : 'cursor-default opacity-60',
                                )
                            "
                        >
                            <span
                                v-if="item.icon"
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-navy-800 shadow-[0_2px_6px_-2px_rgba(0,30,60,0.25)] ring-1 ring-navy-100 transition-all duration-300 group-hover/res:-translate-y-0.5 group-hover/res:text-brand-700 group-hover/res:ring-brand-200"
                            >
                                <component :is="item.icon" class="size-4" />
                            </span>
                            <span
                                v-else
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy-900 text-[10px] font-bold tracking-tight text-white shadow-[0_2px_6px_-2px_rgba(0,30,60,0.45)] transition-all duration-300 group-hover/res:-translate-y-0.5 group-hover/res:bg-brand-700"
                                aria-hidden="true"
                            >
                                doi
                            </span>
                            {{ item.label }}
                        </component>
                    </li>
                </ul>

                <p
                    v-if="issue.description"
                    class="mt-5 line-clamp-3 max-w-xl font-serif text-[15px] leading-relaxed text-navy-700 sm:text-base"
                >
                    {{ issue.description }}
                </p>

                <div class="mt-auto pt-6">
                    <Link
                        :href="issue.url"
                        class="group inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-7 text-sm font-semibold text-white shadow-md shadow-navy-900/20 transition-all hover:-translate-y-0.5 hover:bg-navy-800 hover:shadow-lg hover:shadow-navy-900/25"
                    >
                        {{ t("Sonni ko'rish") }}
                        <ArrowRight
                            class="size-4 transition-transform group-hover:translate-x-0.5"
                        />
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex flex-col items-center gap-2 py-10 text-center text-sm text-navy-500"
        >
            <BookOpen class="size-8 text-navy-300" />
            {{ t("Hozircha chop etilgan son yo'q.") }}
        </div>
    </HomeCard>
</template>

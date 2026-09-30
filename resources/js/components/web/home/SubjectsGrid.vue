<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { SubjectSummary } from '@/types';

/**
 * Ilmiy yo'nalishlar — katalogga yo'nalish filtri bilan o'tadi.
 */
defineProps<{ subjects: SubjectSummary[] }>();
</script>

<template>
    <section v-if="subjects.length" class="bg-surface-muted py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Tadqiqot sohalari"
                title="Ilmiy yo'nalishlar"
                description="Jurnal gumanitar va ijtimoiy fanlarning quyidagi yo'nalishlari bo'yicha maqolalarni qabul qiladi."
                :href="articlesIndex()"
                link-text="Katalogga o'tish"
            />
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="subject in subjects" :key="subject.id">
                    <Link
                        :href="
                            articlesIndex({ query: { subject: subject.slug } })
                        "
                        class="group flex h-full items-start gap-4 rounded-xl border border-line bg-white p-5 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card-hover"
                    >
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white"
                        >
                            <SubjectIcon
                                :slug="subject.slug"
                                class="size-5"
                                :stroke-width="1.7"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-start justify-between gap-2 font-serif text-base font-semibold text-navy-950"
                            >
                                {{ subject.name }}
                                <ArrowUpRight
                                    class="size-4 shrink-0 text-navy-300 transition-colors group-hover:text-brand-600"
                                />
                            </span>
                            <span
                                v-if="subject.nameEn"
                                class="block font-serif text-sm text-navy-400 italic"
                            >
                                {{ subject.nameEn }}
                            </span>
                            <span
                                class="mt-2 block text-xs font-medium text-navy-500"
                            >
                                {{ subject.articlesCount }} ta maqola
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { SubjectSummary } from '@/types';

/**
 * Hero ostidagi yo'nalishlar qatori (home.png): ikonka, nom, inglizcha nom.
 */
defineProps<{ subjects: SubjectSummary[] }>();
</script>

<template>
    <nav
        v-if="subjects.length"
        class="border-b border-line bg-white"
        aria-label="Ilmiy yo'nalishlar"
    >
        <ul
            class="mx-auto grid max-w-7xl grid-cols-2 sm:grid-cols-4 xl:auto-cols-fr xl:grid-flow-col xl:grid-cols-none"
        >
            <li
                v-for="subject in subjects"
                :key="subject.id"
                class="border-line max-xl:border-b xl:not-last:border-r"
            >
                <Link
                    :href="articlesIndex({ query: { subject: subject.slug } })"
                    class="group flex h-full items-center gap-2 px-3 py-4 transition-colors hover:bg-brand-50 sm:gap-3 sm:px-4 xl:flex-col xl:gap-2 xl:px-2 xl:py-5 xl:text-center"
                >
                    <SubjectIcon
                        :slug="subject.slug"
                        class="size-6 shrink-0 text-navy-800 transition-colors group-hover:text-brand-700 sm:size-8"
                        :stroke-width="1.4"
                    />
                    <span class="min-w-0 leading-tight">
                        <span
                            class="block font-serif text-[13px] font-semibold text-navy-900 sm:text-sm"
                        >
                            {{ subject.name }}
                        </span>
                        <span
                            v-if="subject.nameEn"
                            class="block font-serif text-xs text-navy-400 italic"
                        >
                            {{ subject.nameEn }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </nav>
</template>

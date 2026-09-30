<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { index as articlesIndex } from '@/routes/articles';
import { computed } from 'vue';
import type { SubjectSummary } from '@/types';

/**
 * Slayder ostidagi ilmiy yo'nalishlar qatori (home.png):
 * ikonka, nom va inglizcha nom (kursiv), orasida ingichka ajratgichlar.
 * Bosilganda katalog shu yo'nalish bo'yicha filtrlanadi.
 *
 * Dizayn bo'yicha qatorda ko'pi bilan 6 ta yo'nalish: qaysilari chiqishini
 * admin yo'nalishlar tartibi (sort_order) bilan belgilaydi.
 */
const MAX_ITEMS = 6;

const props = defineProps<{ subjects: SubjectSummary[] }>();

const visible = computed(() => props.subjects.slice(0, MAX_ITEMS));
</script>

<template>
    <nav
        v-if="visible.length"
        class="border-b border-[#e6e3dc] bg-[#f9f8f6]"
        aria-label="Ilmiy yo'nalishlar"
    >
        <ul
            class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:grid-cols-3 sm:px-6 lg:flex lg:items-center lg:px-8"
        >
            <template v-for="(subject, index) in visible" :key="subject.id">
                <li
                    v-if="index > 0"
                    class="hidden h-14 w-px shrink-0 bg-[#dcdfe1] lg:block"
                    aria-hidden="true"
                />
                <li class="lg:flex-1">
                    <Link
                        :href="
                            articlesIndex({ query: { subject: subject.slug } })
                        "
                        class="group flex items-center gap-4 px-3 py-5 lg:justify-center lg:py-7"
                    >
                        <SubjectIcon
                            :slug="subject.slug"
                            class="size-9 shrink-0 text-navy-900 transition-colors group-hover:text-brand-700 lg:size-10"
                            :stroke-width="1.6"
                        />
                        <span class="min-w-0 leading-tight">
                            <span
                                class="block font-serif text-[15px] font-semibold text-navy-900 transition-colors group-hover:text-brand-700"
                            >
                                {{ subject.name }}
                            </span>
                            <span
                                v-if="subject.nameEn"
                                class="mt-0.5 block font-serif text-[13px] text-navy-500 italic"
                            >
                                {{ subject.nameEn }}
                            </span>
                        </span>
                    </Link>
                </li>
            </template>
        </ul>
    </nav>
</template>

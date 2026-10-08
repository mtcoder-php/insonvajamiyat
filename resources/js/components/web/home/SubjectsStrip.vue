<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { index as articlesIndex } from '@/routes/articles';
import { computed } from 'vue';
import type { SubjectSummary } from '@/types';
import { t, useLocale } from '@/lib/i18n';

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
// Inglizcha nom ingliz tilida takrorlanmaydi
const locale = useLocale();
</script>

<template>
    <nav
        v-if="visible.length"
        class="relative border-b border-[#e6e3dc] bg-[#f9f8f6]"
        :aria-label="t('Ilmiy yo\'nalishlar')"
    >
        <ul
            class="mx-auto grid max-w-7xl grid-cols-2 gap-1 px-3 py-3 sm:grid-cols-3 sm:px-6 lg:flex lg:items-center lg:gap-0 lg:px-8 lg:py-4"
        >
            <template v-for="(subject, index) in visible" :key="subject.id">
                <li
                    v-if="index > 0"
                    class="hidden h-12 w-px shrink-0 bg-[#dcdfe1] lg:block"
                    aria-hidden="true"
                />
                <li
                    class="strip-item flex lg:flex-1 lg:justify-center lg:px-1.5"
                    :style="{ animationDelay: `${index * 70}ms` }"
                >
                    <Link
                        :href="
                            articlesIndex({ query: { subject: subject.slug } })
                        "
                        class="group relative flex w-full items-center gap-3.5 rounded-xl px-3 py-3 ring-1 ring-transparent transition-all duration-300 ease-out outline-none hover:-translate-y-1 hover:bg-white hover:shadow-[0_14px_32px_-14px_rgba(0,36,66,0.35)] hover:ring-[#e6e3dc] focus-visible:bg-white focus-visible:ring-2 focus-visible:ring-brand-300 lg:w-auto lg:px-4"
                    >
                        <span
                            class="relative flex size-12 shrink-0 items-center justify-center rounded-full transition-all duration-300 group-hover:bg-navy-900 group-hover:shadow-lg group-hover:shadow-navy-900/25"
                        >
                            <SubjectIcon
                                :slug="subject.slug"
                                class="size-9 text-navy-900 transition-all duration-300 group-hover:size-6 group-hover:text-gold-300 lg:size-10 lg:group-hover:size-6"
                                :stroke-width="1.6"
                            />
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span
                                class="block font-serif text-[15px] font-semibold text-navy-900 transition-colors duration-300 group-hover:text-brand-700"
                            >
                                {{ subject.name }}
                            </span>
                            <span
                                v-if="subject.nameEn && locale !== 'en'"
                                class="mt-0.5 block font-serif text-[13px] text-navy-500 italic transition-colors duration-300 group-hover:text-navy-700"
                            >
                                {{ subject.nameEn }}
                            </span>
                            <span
                                class="mt-1.5 block h-0.5 w-0 rounded-full bg-gold-500 transition-all duration-300 group-hover:w-8"
                                aria-hidden="true"
                            />
                        </span>
                    </Link>
                </li>
            </template>
        </ul>
    </nav>
</template>

<style scoped>
/* Sahifa ochilganda yo'nalishlar ketma-ket paydo bo'ladi */
@media (prefers-reduced-motion: no-preference) {
    .strip-item {
        animation: strip-in 0.5s ease-out both;
    }
}

@keyframes strip-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

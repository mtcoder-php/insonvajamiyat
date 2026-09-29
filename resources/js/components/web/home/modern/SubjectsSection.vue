<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SectionHeading from '@/components/web/SectionHeading.vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import { formatNumber } from '@/lib/format';
import { index as articlesIndex } from '@/routes/articles';
import type { SubjectSummary } from '@/types';

/**
 * "Yo'nalishlar bo'yicha maqolalar" — katalogga filtr bilan o'tadi.
 */
defineProps<{ subjects: SubjectSummary[] }>();
</script>

<template>
    <section>
        <SectionHeading
            title="Yo'nalishlar bo'yicha maqolalar"
            :href="articlesIndex()"
            link-text="Barcha yo'nalishlar"
        />
        <div
            class="grid grid-cols-2 gap-px overflow-hidden surface-card bg-line sm:grid-cols-4 xl:grid-cols-4"
        >
            <Link
                v-for="subject in subjects"
                :key="subject.id"
                :href="articlesIndex({ query: { subject: subject.slug } })"
                class="group flex flex-col items-center gap-2 bg-surface px-3 py-5 text-center transition-colors hover:bg-brand-50"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-gold-100/70 text-gold-600 transition-colors group-hover:bg-white"
                >
                    <SubjectIcon
                        :slug="subject.slug"
                        class="size-6"
                        :stroke-width="1.5"
                    />
                </span>
                <span class="text-sm font-semibold text-navy-900">
                    {{ subject.name }}
                </span>
                <span class="text-xs text-navy-500">
                    {{ formatNumber(subject.articlesCount) }} maqola
                </span>
            </Link>
        </div>
    </section>
</template>

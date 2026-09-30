<script setup lang="ts">
import type { PartnerItem } from '@/types';

/**
 * Indekslash bazalari va hamkor tashkilotlar.
 * Logotip yuklanmagan bo'lsa, nom matn ko'rinishida chiqadi.
 */
const props = defineProps<{
    indexing: PartnerItem[];
    partners: PartnerItem[];
}>();

const groups = [
    { title: 'Indekslanadi', items: props.indexing },
    { title: 'Hamkorlarimiz', items: props.partners },
].filter((group) => group.items.length > 0);
</script>

<template>
    <section v-if="groups.length" class="border-t border-line bg-white py-14">
        <div class="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
            <div v-for="group in groups" :key="group.title">
                <h2
                    class="mb-5 text-center font-sans text-xs font-semibold tracking-[0.18em] text-navy-400 uppercase"
                >
                    {{ group.title }}
                </h2>
                <ul class="flex flex-wrap items-stretch justify-center gap-3">
                    <li v-for="item in group.items" :key="item.id">
                        <component
                            :is="item.url ? 'a' : 'div'"
                            :href="item.url ?? undefined"
                            :target="item.url ? '_blank' : undefined"
                            :rel="item.url ? 'noopener noreferrer' : undefined"
                            class="flex h-16 min-w-44 items-center justify-center gap-3 rounded-lg border border-line px-6 text-center transition-colors hover:border-brand-200 hover:bg-brand-50/40"
                        >
                            <img
                                v-if="item.logoUrl"
                                :src="item.logoUrl"
                                :alt="item.name"
                                loading="lazy"
                                class="max-h-9 max-w-36 object-contain grayscale transition hover:grayscale-0"
                            />
                            <span
                                v-else
                                class="max-w-56 font-serif text-sm leading-tight font-semibold text-navy-700"
                            >
                                {{ item.name }}
                            </span>
                        </component>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

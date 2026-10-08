<script setup lang="ts">
import { ArrowUpRight, Database, Handshake } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PartnerItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Bosh sahifa pastidagi "Hamkorlar va indekslash bazalari" qatori.
 * Logolar kulrang ko'rinishda, ustiga kelganda asl rangiga qaytadi.
 * Ro'yxat Admin → Sozlamalar → Hamkorlar bo'limida boshqariladi.
 */
const props = defineProps<{ partners: PartnerItem[] }>();

const groups = computed(() =>
    [
        {
            key: 'indexing' as const,
            title: t('Indekslash bazalari'),
            icon: Database,
        },
        {
            key: 'partner' as const,
            title: t('Hamkor tashkilotlar'),
            icon: Handshake,
        },
    ]
        .map((group) => ({
            ...group,
            items: props.partners.filter((p) => p.type === group.key),
        }))
        .filter((group) => group.items.length),
);
</script>

<template>
    <section
        v-if="groups.length"
        class="border-t border-[#ebe8e1] bg-[#f9f8f6]"
        aria-labelledby="partners-title"
    >
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-12"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <p
                        class="text-[11px] font-bold tracking-[0.18em] text-gold-700 uppercase"
                    >
                        {{ t('Ishonchli manbalar') }}
                    </p>
                    <h2
                        id="partners-title"
                        class="mt-1 font-serif text-2xl font-bold text-navy-900"
                    >
                        {{ t('Hamkorlar va indekslash bazalari') }}
                    </h2>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <div v-for="group in groups" :key="group.key">
                    <h3
                        class="mb-3 inline-flex items-center gap-2 text-[13px] font-semibold text-navy-600"
                    >
                        <component :is="group.icon" class="size-4" />
                        {{ group.title }}
                    </h3>
                    <ul
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6"
                    >
                        <li v-for="partner in group.items" :key="partner.id">
                            <component
                                :is="partner.url ? 'a' : 'div'"
                                :href="partner.url ?? undefined"
                                :target="partner.url ? '_blank' : undefined"
                                :rel="
                                    partner.url
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                :class="
                                    cn(
                                        'group relative flex h-full min-h-28 flex-col items-center justify-center gap-2 rounded-xl border border-[#e6e3dc] bg-white px-4 py-4 text-center transition-all duration-300 ease-out',
                                        partner.url &&
                                            'hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_16px_34px_-18px_rgba(0,36,66,0.4)] focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:outline-none',
                                    )
                                "
                            >
                                <ArrowUpRight
                                    v-if="partner.url"
                                    class="absolute top-2.5 right-2.5 size-3.5 text-navy-300 opacity-0 transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-brand-600 group-hover:opacity-100"
                                />
                                <img
                                    v-if="partner.logoUrl"
                                    :src="partner.logoUrl"
                                    :alt="partner.name"
                                    loading="lazy"
                                    class="max-h-12 max-w-[85%] object-contain opacity-75 grayscale transition-all duration-300 group-hover:scale-105 group-hover:opacity-100 group-hover:grayscale-0"
                                />
                                <span
                                    v-else
                                    class="font-serif text-[15px] leading-tight font-semibold [overflow-wrap:anywhere] text-navy-800 transition-colors group-hover:text-brand-700"
                                    >{{ partner.name }}</span
                                >
                                <span
                                    v-if="partner.subtitle"
                                    class="text-[11px] font-medium text-navy-500"
                                    >{{ partner.subtitle }}</span
                                >
                            </component>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

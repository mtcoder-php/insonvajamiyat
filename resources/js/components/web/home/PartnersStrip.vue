<script setup lang="ts">
import { ArrowUpRight, Database, Globe, Handshake } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PartnerItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Bosh sahifa pastidagi "Hamkorlar va indekslash bazalari" qatori.
 * Har bir kartada: logo "avatar" (logo yuklanmagan bo'lsa — nomdan monogramma),
 * nom, izoh va sayt manzili. Sayt kiritilgan bo'lsa butun karta havola (yangi oynada).
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

/** "Yangi Asr universiteti" → "YA", "CrossRef" → "CR" */
function monogram(name: string): string {
    const words = name
        .replace(/[«»"'ʻʼ‘’`.,()]/g, '')
        .split(/\s+/)
        .filter(Boolean);

    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }

    const word = words[0] ?? '?';
    const capitals = word.match(/[A-ZА-ЯЁ]/g);

    return (
        capitals && capitals.length >= 2
            ? capitals.slice(0, 2).join('')
            : word.slice(0, 2)
    ).toUpperCase();
}

/** "https://www.yangiasr.uz/uz" → "yangiasr.uz" */
function domain(url: string): string {
    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}
</script>

<template>
    <section
        v-if="groups.length"
        class="border-t border-[#ebe8e1] bg-[#f9f8f6]"
        aria-labelledby="partners-title"
    >
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-14"
        >
            <div class="mb-7">
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
                <div class="mt-3 gold-rule w-16" aria-hidden="true" />
            </div>

            <div class="grid grid-cols-1 gap-9">
                <div v-for="group in groups" :key="group.key">
                    <h3
                        class="mb-4 inline-flex items-center gap-2 text-[13px] font-semibold text-navy-600"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-white text-gold-600 ring-1 ring-[#e6e3dc]"
                        >
                            <component :is="group.icon" class="size-4" />
                        </span>
                        {{ group.title }}
                        <span
                            class="rounded-full bg-white px-2 py-0.5 text-[11px] text-navy-400 tabular-nums ring-1 ring-[#e6e3dc]"
                            >{{ group.items.length }}</span
                        >
                    </h3>
                    <ul
                        class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
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
                                :title="
                                    partner.url
                                        ? `${partner.name} — ${t('saytga o\'tish')}`
                                        : partner.name
                                "
                                :class="
                                    cn(
                                        'group relative flex h-full items-center gap-4 overflow-hidden rounded-2xl border border-[#e6e3dc] bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.04)] transition-all duration-300 ease-out',
                                        partner.url &&
                                            'hover:-translate-y-1 hover:border-gold-300 hover:shadow-[0_22px_40px_-22px_rgba(0,36,66,0.45),0_0_0_4px_rgba(210,174,90,0.10)] focus-visible:ring-4 focus-visible:ring-brand-200 focus-visible:outline-none',
                                    )
                                "
                            >
                                <!-- Hoverda chapdan oltin chiziq -->
                                <span
                                    v-if="partner.url"
                                    class="absolute inset-y-0 left-0 w-1 origin-top scale-y-0 bg-gradient-to-b from-gold-300 to-gold-600 transition-transform duration-300 group-hover:scale-y-100"
                                    aria-hidden="true"
                                />

                                <!-- Logo avatar -->
                                <span
                                    :class="
                                        cn(
                                            'flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl ring-1 transition-all duration-300',
                                            partner.logoUrl
                                                ? 'bg-white p-2 ring-[#ebe8e1] group-hover:ring-gold-300'
                                                : 'bg-gradient-to-br from-navy-800 via-navy-900 to-navy-950 shadow-[inset_0_1px_0_rgba(255,255,255,0.12)] ring-navy-950/10 group-hover:shadow-[0_10px_22px_-10px_rgba(0,30,60,0.7)]',
                                        )
                                    "
                                >
                                    <img
                                        v-if="partner.logoUrl"
                                        :src="partner.logoUrl"
                                        :alt="partner.name"
                                        loading="lazy"
                                        class="max-h-full max-w-full object-contain opacity-80 grayscale transition-all duration-300 group-hover:scale-105 group-hover:opacity-100 group-hover:grayscale-0"
                                    />
                                    <span
                                        v-else
                                        class="font-serif text-lg font-bold tracking-wide text-gold-300 transition-transform duration-300 group-hover:scale-110"
                                        aria-hidden="true"
                                        >{{ monogram(partner.name) }}</span
                                    >
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="line-clamp-2 font-serif text-[15px] leading-snug font-semibold text-navy-900 transition-colors duration-300 group-hover:text-brand-700"
                                        >{{ partner.name }}</span
                                    >
                                    <span
                                        v-if="partner.subtitle"
                                        class="mt-1 inline-flex rounded-full bg-[#fbf6ea] px-2 py-0.5 text-[11px] font-semibold text-gold-700 ring-1 ring-gold-200/70"
                                        >{{ partner.subtitle }}</span
                                    >
                                    <span
                                        v-if="partner.url"
                                        class="mt-1.5 flex items-center gap-1 text-xs font-medium text-navy-400 transition-colors duration-300 group-hover:text-brand-600"
                                    >
                                        <Globe class="size-3.5 shrink-0" />
                                        <span class="truncate">{{
                                            domain(partner.url)
                                        }}</span>
                                    </span>
                                </span>

                                <span
                                    v-if="partner.url"
                                    class="flex size-8 shrink-0 items-center justify-center self-start rounded-full bg-[#f4f2ed] text-navy-400 transition-all duration-300 group-hover:rotate-0 group-hover:bg-gold-400 group-hover:text-navy-950 group-hover:shadow-[0_8px_18px_-8px_rgba(196,154,69,0.9)]"
                                    aria-hidden="true"
                                >
                                    <ArrowUpRight
                                        class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                    />
                                </span>
                            </component>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

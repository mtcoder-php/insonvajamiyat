<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    BookMarked,
    Fingerprint,
    Info,
} from '@lucide/vue';
import { computed } from 'vue';
import SkylineIllustration from '@/components/web/SkylineIllustration.vue';
import { about } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import type { PartnerItem } from '@/types';

/**
 * Zamonaviy hero (home_2.png): chapda shior va tugmalar,
 * o'ngda jurnal rekvizitlari va indekslash bazalari kartasi.
 */
const props = defineProps<{ indexing: PartnerItem[] }>();

const journal = computed(() => usePage().props.journal);

type Fact = {
    key: string;
    title: string;
    subtitle: string;
    logoUrl?: string | null;
};

const facts = computed<Fact[]>(() => {
    const items: Fact[] = [];

    if (journal.value.issn) {
        items.push({
            key: 'issn',
            title: `ISSN ${journal.value.issn}`,
            subtitle: journal.value.eissn
                ? `e-ISSN ${journal.value.eissn}`
                : 'Bosma nashr',
        });
    }

    if (journal.value.doiPrefix) {
        items.push({
            key: 'doi',
            title: 'DOI',
            subtitle: journal.value.doiPrefix,
        });
    }

    for (const partner of props.indexing.slice(0, 4 - items.length)) {
        items.push({
            key: `partner-${partner.id}`,
            title: partner.name,
            subtitle: partner.subtitle ?? 'Indekslangan',
            logoUrl: partner.logoUrl,
        });
    }

    return items;
});
</script>

<template>
    <section
        class="relative isolate overflow-hidden bg-navy-gradient text-white"
    >
        <div class="absolute inset-0 -z-10 bg-girih opacity-[0.05]" />
        <div
            class="absolute -top-40 right-0 -z-10 size-[36rem] rounded-full bg-brand-600/25 blur-3xl"
        />
        <div
            class="absolute -bottom-24 left-1/4 -z-10 size-[28rem] rounded-full bg-gold-500/10 blur-3xl"
        />
        <SkylineIllustration
            class="absolute inset-x-0 bottom-0 -z-10 h-40 w-full opacity-90 sm:h-56 lg:h-64"
        />

        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 pt-14 pb-40 sm:px-6 sm:pb-48 lg:grid-cols-[1fr_22rem] lg:items-center lg:px-8 lg:pt-20 lg:pb-52"
        >
            <div class="max-w-2xl">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur"
                >
                    <span class="size-1.5 rounded-full bg-gold-400" />
                    Ilmiy jurnal
                </span>
                <h1
                    class="mt-5 font-serif text-4xl leading-[1.1] font-semibold text-white sm:text-5xl lg:text-6xl"
                >
                    Insonni anglash —<br class="hidden sm:block" />
                    jamiyatni anglashdir.
                </h1>
                <div class="mt-6 gold-rule w-24" />
                <p
                    class="mt-6 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg"
                >
                    "{{ journal.name }}" ilmiy jurnali — tarix, etnologiya,
                    antropologiya va falsafaga doir ilmiy tadqiqotlarni nashr
                    etuvchi xalqaro ilmiy nashr.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="articlesIndex()"
                        class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-navy-950 shadow-float transition-colors hover:bg-brand-50"
                    >
                        Maqolalar katalogi
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        :href="about()"
                        class="inline-flex h-12 items-center gap-2 rounded-full border border-white/40 px-6 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                    >
                        Jurnal haqida
                        <Info class="size-4" />
                    </Link>
                </div>
            </div>

            <aside
                v-if="facts.length"
                class="rounded-2xl border border-white/15 bg-navy-950/55 p-5 shadow-float backdrop-blur-md"
                aria-label="Jurnal rekvizitlari"
            >
                <ul class="divide-y divide-white/10">
                    <li
                        v-for="fact in facts"
                        :key="fact.key"
                        class="flex items-center gap-3 py-3 first:pt-0"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-white/15 bg-white/10"
                        >
                            <img
                                v-if="fact.logoUrl"
                                :src="fact.logoUrl"
                                alt=""
                                class="size-7 object-contain"
                            />
                            <BookMarked
                                v-else-if="fact.key === 'issn'"
                                class="size-5 text-gold-300"
                            />
                            <Fingerprint
                                v-else-if="fact.key === 'doi'"
                                class="size-5 text-gold-300"
                            />
                            <BadgeCheck v-else class="size-5 text-gold-300" />
                        </span>
                        <div class="min-w-0 text-sm leading-tight">
                            <p class="truncate font-semibold">
                                {{ fact.title }}
                            </p>
                            <p class="truncate text-xs text-white/65">
                                {{ fact.subtitle }}
                            </p>
                        </div>
                    </li>
                </ul>
                <Link
                    :href="about()"
                    class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-gold-300 hover:text-gold-200"
                >
                    Batafsil
                    <ArrowRight class="size-4" />
                </Link>
            </aside>
        </div>
    </section>
</template>

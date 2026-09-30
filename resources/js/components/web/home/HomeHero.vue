<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    Fingerprint,
    ShieldCheck,
    Unlock,
} from '@lucide/vue';
import { computed } from 'vue';
import IssueCover from '@/components/web/IssueCover.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateLong } from '@/lib/format';
import { dashboard, register } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import type { LatestIssue } from '@/types';

/**
 * Bosh sahifa hero bloki — login sahifasidagi brend paneli uslubida:
 * chapda jurnal shiori va asosiy harakatlar, o'ngda joriy son muqovasi.
 */
const props = defineProps<{ issue: LatestIssue | null }>();

const journal = computed(() => usePage().props.journal);
const { auth } = usePermissions();

// Kirgan muallif kabinetga, mehmon ro'yxatdan o'tishga yo'naltiriladi
const submitHref = computed(() => (auth.value.user ? dashboard() : register()));

const principles = [
    { label: 'Ochiq kirish', icon: Unlock },
    { label: 'Yashirin taqriz', icon: ShieldCheck },
    { label: 'DOI va indekslash', icon: Fingerprint },
];

const identifiers = computed(() =>
    [
        journal.value.issn ? `ISSN ${journal.value.issn}` : null,
        journal.value.eissn ? `e-ISSN ${journal.value.eissn}` : null,
    ].filter((value): value is string => value !== null),
);

const hasIssue = computed(() => props.issue !== null);
</script>

<template>
    <section
        class="relative isolate overflow-hidden bg-navy-gradient text-white"
    >
        <div class="absolute inset-0 -z-10 bg-girih opacity-[0.05]" />
        <div
            class="absolute top-1/2 right-[8%] -z-10 size-[34rem] -translate-y-1/2 rounded-full bg-brand-500/20 blur-3xl"
        />
        <div
            class="absolute inset-x-0 bottom-0 -z-10 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent"
        />

        <div
            class="mx-auto grid max-w-7xl items-center gap-14 px-4 py-16 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8 lg:py-24"
        >
            <div>
                <p
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold tracking-[0.18em] text-gold-300 uppercase"
                >
                    <span class="h-px w-8 bg-gold-400" aria-hidden="true" />
                    Ilmiy-nazariy jurnal
                    <template v-for="id in identifiers" :key="id">
                        <span class="text-white/30" aria-hidden="true">·</span>
                        <span class="tracking-[0.08em] text-white/70">{{
                            id
                        }}</span>
                    </template>
                </p>

                <h1
                    class="mt-6 max-w-2xl font-serif text-4xl leading-[1.12] font-semibold text-white sm:text-5xl lg:text-[3.5rem]"
                >
                    Insonni anglash —
                    <span class="text-gold-300 italic">jamiyatni</span>
                    anglashdir.
                </h1>

                <p
                    class="mt-6 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg"
                >
                    "{{ journal.name }}" — tarix, etnologiya, antropologiya va
                    falsafa sohalaridagi original tadqiqotlarni taqriz asosida
                    nashr etuvchi ilmiy jurnal.
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <Link
                        :href="submitHref"
                        class="inline-flex h-12 items-center gap-2 rounded-lg bg-brand-600 px-6 text-sm font-semibold text-white shadow-lg shadow-brand-900/30 transition-colors hover:bg-brand-500"
                    >
                        Maqola yuborish
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        :href="articlesIndex()"
                        class="inline-flex h-12 items-center gap-2 rounded-lg border border-white/25 bg-white/5 px-6 text-sm font-semibold text-white backdrop-blur transition-colors hover:bg-white/10"
                    >
                        <BookOpenCheck class="size-4" />
                        Maqolalar katalogi
                    </Link>
                </div>

                <ul
                    class="mt-12 grid gap-3 border-t border-white/10 pt-8 sm:grid-cols-3"
                >
                    <li
                        v-for="item in principles"
                        :key="item.label"
                        class="flex items-center gap-3 text-sm font-medium text-white/85"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/5 text-gold-300"
                        >
                            <component :is="item.icon" class="size-4" />
                        </span>
                        {{ item.label }}
                    </li>
                </ul>
            </div>

            <div class="relative mx-auto w-full max-w-sm lg:max-w-none">
                <template v-if="hasIssue && issue">
                    <Link
                        :href="issue.url"
                        class="group relative mx-auto block w-56 sm:w-64"
                        :aria-label="`Joriy son: ${issue.label}`"
                    >
                        <div
                            class="absolute -inset-6 rounded-3xl bg-white/5 ring-1 ring-white/10"
                            aria-hidden="true"
                        />
                        <IssueCover
                            :src="issue.coverUrl"
                            :number="issue.number"
                            :year="issue.year"
                            class="relative shadow-2xl shadow-black/40 transition-transform duration-300 group-hover:-translate-y-1"
                        />
                    </Link>
                    <div
                        class="relative mx-auto mt-10 w-full max-w-xs rounded-xl border border-white/15 bg-navy-950/70 p-4 shadow-xl backdrop-blur-md"
                    >
                        <p
                            class="text-[11px] font-semibold tracking-[0.16em] text-gold-300 uppercase"
                        >
                            Joriy son
                        </p>
                        <div class="mt-1 flex items-end justify-between gap-3">
                            <div>
                                <p class="font-serif text-xl font-semibold">
                                    {{ issue.label }}
                                </p>
                                <p
                                    v-if="issue.publishedAt"
                                    class="text-xs text-white/60"
                                >
                                    {{ formatDateLong(issue.publishedAt) }}
                                    <template v-if="issue.articlesCount">
                                        · {{ issue.articlesCount }} ta maqola
                                    </template>
                                </p>
                            </div>
                            <Link
                                :href="issue.url"
                                class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-gold-300 hover:text-gold-200"
                            >
                                Ochish
                                <ArrowRight class="size-4" />
                            </Link>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="mx-auto flex aspect-square w-64 items-center justify-center rounded-full border border-gold-400/30"
                    aria-hidden="true"
                >
                    <div
                        class="flex size-48 items-center justify-center rounded-full border border-gold-400/20 bg-white/[0.03]"
                    >
                        <img
                            src="/images/logo-mark-light.webp"
                            alt=""
                            class="w-24 object-contain"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

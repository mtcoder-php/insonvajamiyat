<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BookOpenText,
    Building2,
    Database,
    FileText,
    GraduationCap,
    Layers,
    ListTree,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import SubjectIcon from '@/components/web/SubjectIcon.vue';
import WebPageHeader from '@/components/web/WebPageHeader.vue';
import ContentSection from '@/components/web/content/ContentSection.vue';
import { formatNumber } from '@/lib/format';
import { t } from '@/lib/i18n';
import { index as articlesIndex } from '@/routes/articles';
import type { AboutPageProps } from '@/types';

/**
 * "Jurnal haqida": tahririyat yozgan bo'limlar (admin → Sozlamalar → Sahifalar) +
 * jurnal rekvizitlari, ilmiy yo'nalishlar, tahririyat kengashi va indekslash bazalari.
 */
const props = defineProps<AboutPageProps>();

const stats = computed(() => [
    {
        icon: FileText,
        value: props.stats.articles,
        label: t('Nashr etilgan maqolalar'),
    },
    {
        icon: BookOpenText,
        value: props.stats.issues,
        label: t('Jurnal sonlari'),
    },
    { icon: UsersRound, value: props.stats.authors, label: t('Mualliflar') },
    {
        icon: Layers,
        value: props.stats.subjects,
        label: t("Ilmiy yo'nalishlar"),
    },
]);

/** Mundarija: bo'limlar + avtomatik bloklar */
const toc = computed(() => [
    ...props.page.sections.map((section, index) => ({
        id: `section-${index + 1}`,
        title: section.heading,
    })),
    ...(props.subjects.length
        ? [{ id: 'subjects', title: t("Ilmiy yo'nalishlar") }]
        : []),
    ...(props.board.length
        ? [{ id: 'editorial-board', title: t('Tahririyat kengashi') }]
        : []),
    ...(props.indexing.length
        ? [{ id: 'indexing', title: t('Indekslash') }]
        : []),
]);

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <Head :title="page.title" />

    <WebPageHeader
        :title="page.title"
        :description="page.description"
        :crumbs="[{ title: page.title }]"
    />

    <!-- Raqamlarda -->
    <section class="border-b border-[#ece8df] bg-white">
        <div
            class="mx-auto grid w-full max-w-[1700px] grid-cols-2 gap-px px-4 sm:px-6 lg:w-[90%] lg:grid-cols-4 lg:px-0"
        >
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="group flex items-center gap-4 px-2 py-6 sm:px-5"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition-all duration-300 group-hover:-rotate-6 group-hover:bg-navy-900 group-hover:text-gold-300 group-hover:shadow-lg group-hover:shadow-navy-900/20"
                >
                    <component :is="stat.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span
                        class="block font-serif text-2xl font-bold text-navy-950 tabular-nums sm:text-3xl"
                        >{{ formatNumber(stat.value) }}</span
                    >
                    <span class="block text-xs text-navy-500 sm:text-sm">{{
                        stat.label
                    }}</span>
                </span>
            </div>
        </div>
    </section>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-8 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_21rem] lg:px-0 lg:py-12"
        >
            <div class="flex min-w-0 flex-col gap-6">
                <!-- Tahririyat yozgan bo'limlar -->
                <ContentSection
                    v-for="(section, index) in page.sections"
                    :id="`section-${index + 1}`"
                    :key="index"
                    :heading="section.heading"
                    :body="section.body"
                />

                <!-- Ilmiy yo'nalishlar -->
                <section
                    v-if="subjects.length"
                    id="subjects"
                    class="scroll-mt-28 rounded-2xl border border-line bg-white p-6 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-8"
                >
                    <h2
                        class="mb-5 flex items-center gap-3 font-serif text-xl font-bold text-navy-950 sm:text-2xl"
                    >
                        <span
                            class="h-7 w-1 rounded-full bg-gold-500"
                            aria-hidden="true"
                        />
                        {{ t("Ilmiy yo'nalishlar") }}
                    </h2>
                    <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <li v-for="subject in subjects" :key="subject.slug">
                            <Link
                                :href="
                                    articlesIndex({
                                        query: { subject: subject.slug },
                                    })
                                "
                                class="group flex items-center gap-3 rounded-xl border border-line bg-[#fafbfd] px-4 py-3 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-white hover:shadow-[0_14px_30px_-20px_rgba(0,36,66,0.45)]"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-navy-900 ring-1 ring-line transition-all duration-300 group-hover:bg-navy-900 group-hover:text-gold-300 group-hover:ring-navy-900"
                                >
                                    <SubjectIcon
                                        :slug="subject.slug"
                                        class="size-5"
                                    />
                                </span>
                                <span
                                    class="min-w-0 flex-1 truncate text-sm font-semibold text-navy-900 transition-colors group-hover:text-brand-700"
                                    >{{ subject.name }}</span
                                >
                                <ArrowUpRight
                                    class="size-4 -translate-x-1 text-brand-600 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100"
                                />
                            </Link>
                        </li>
                    </ul>
                </section>

                <!-- Tahririyat kengashi -->
                <section
                    v-if="board.length"
                    id="editorial-board"
                    class="scroll-mt-28 rounded-2xl border border-line bg-white p-6 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-8"
                >
                    <h2
                        class="mb-6 flex items-center gap-3 font-serif text-xl font-bold text-navy-950 sm:text-2xl"
                    >
                        <span
                            class="h-7 w-1 rounded-full bg-gold-500"
                            aria-hidden="true"
                        />
                        {{ t('Tahririyat kengashi') }}
                    </h2>

                    <div class="flex flex-col gap-8">
                        <div v-for="group in board" :key="group.role">
                            <h3
                                class="mb-3 text-xs font-semibold tracking-[0.14em] text-gold-700 uppercase"
                            >
                                {{ group.label }}
                            </h3>
                            <ul
                                :class="[
                                    'grid gap-4',
                                    group.role === 'member'
                                        ? 'sm:grid-cols-2 xl:grid-cols-3'
                                        : 'sm:grid-cols-2',
                                ]"
                            >
                                <li
                                    v-for="member in group.members"
                                    :key="member.id"
                                    class="group flex gap-4 rounded-xl border border-line bg-[#fafbfd] p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-white hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                                >
                                    <img
                                        v-if="member.photoUrl"
                                        :src="member.photoUrl"
                                        :alt="member.name"
                                        loading="lazy"
                                        class="size-16 shrink-0 rounded-xl object-cover ring-1 ring-line transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <span
                                        v-else
                                        class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-navy-900 font-serif text-lg font-bold text-gold-300 transition-transform duration-300 group-hover:scale-105"
                                        aria-hidden="true"
                                        >{{ initials(member.name) }}</span
                                    >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="font-serif text-[15px] font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                                        >
                                            {{ member.name }}
                                        </p>
                                        <p
                                            v-if="member.degree"
                                            class="mt-0.5 flex items-start gap-1.5 text-xs text-navy-600"
                                        >
                                            <GraduationCap
                                                class="mt-0.5 size-3.5 shrink-0 text-gold-600"
                                            />
                                            {{ member.degree }}
                                        </p>
                                        <p
                                            v-if="
                                                member.position ||
                                                member.organization
                                            "
                                            class="mt-1 flex items-start gap-1.5 text-xs leading-relaxed text-navy-500"
                                        >
                                            <Building2
                                                class="mt-0.5 size-3.5 shrink-0 text-navy-400"
                                            />
                                            <span>
                                                {{
                                                    [
                                                        member.position,
                                                        member.organization,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(', ')
                                                }}
                                                <span
                                                    v-if="member.country"
                                                    class="text-navy-400"
                                                    >({{
                                                        member.country
                                                    }})</span
                                                >
                                            </span>
                                        </p>
                                        <a
                                            v-if="member.orcid"
                                            :href="`https://orcid.org/${member.orcid}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-2 inline-flex items-center gap-1 rounded-md bg-lime-50 px-2 py-0.5 font-mono text-[11px] text-lime-800 ring-1 ring-lime-200 transition-colors hover:bg-lime-100"
                                            :aria-label="
                                                t(
                                                    ':name (yangi oynada ochiladi)',
                                                    {
                                                        name: `ORCID ${member.orcid}`,
                                                    },
                                                )
                                            "
                                        >
                                            ORCID {{ member.orcid }}
                                            <ArrowUpRight class="size-3" />
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Indekslash -->
                <section
                    v-if="indexing.length"
                    id="indexing"
                    class="scroll-mt-28 rounded-2xl border border-line bg-white p-6 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:p-8"
                >
                    <h2
                        class="mb-5 flex items-center gap-3 font-serif text-xl font-bold text-navy-950 sm:text-2xl"
                    >
                        <span
                            class="h-7 w-1 rounded-full bg-gold-500"
                            aria-hidden="true"
                        />
                        {{ t('Indekslash') }}
                    </h2>
                    <ul
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"
                    >
                        <li v-for="item in indexing" :key="item.id">
                            <component
                                :is="item.url ? 'a' : 'div'"
                                :href="item.url ?? undefined"
                                :target="item.url ? '_blank' : undefined"
                                :rel="
                                    item.url ? 'noopener noreferrer' : undefined
                                "
                                class="group flex h-24 items-center justify-center rounded-xl border border-line bg-[#fafbfd] px-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-white hover:shadow-[0_14px_30px_-20px_rgba(0,36,66,0.45)]"
                            >
                                <img
                                    v-if="item.logoUrl"
                                    :src="item.logoUrl"
                                    :alt="item.name"
                                    loading="lazy"
                                    class="max-h-12 max-w-full object-contain grayscale transition-all duration-300 group-hover:scale-105 group-hover:grayscale-0"
                                />
                                <span
                                    v-else
                                    class="flex items-center gap-2 text-center text-sm font-semibold text-navy-700 transition-colors group-hover:text-brand-700"
                                >
                                    <Database class="size-4 shrink-0" />
                                    {{ item.name }}
                                </span>
                            </component>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- O'ng ustun: rekvizitlar va mundarija -->
            <aside class="min-w-0">
                <div class="flex flex-col gap-5 lg:sticky lg:top-24">
                    <section
                        v-if="facts.length"
                        class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                    >
                        <h2
                            class="border-b border-line bg-[#f9f8f6] px-5 py-3.5 font-serif text-base font-bold text-navy-950"
                        >
                            {{ t("Jurnal ma'lumotlari") }}
                        </h2>
                        <dl class="divide-y divide-line">
                            <div
                                v-for="fact in facts"
                                :key="fact.label"
                                class="flex items-baseline justify-between gap-4 px-5 py-3 transition-colors hover:bg-brand-50/40"
                            >
                                <dt class="text-xs text-navy-500">
                                    {{ fact.label }}
                                </dt>
                                <dd
                                    class="text-right text-[13px] font-semibold text-navy-900 tabular-nums"
                                >
                                    {{ fact.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <nav
                        v-if="toc.length > 1"
                        class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                        :aria-label="t('Sahifa mundarijasi')"
                    >
                        <h2
                            class="mb-3 flex items-center gap-2 font-serif text-base font-bold text-navy-950"
                        >
                            <ListTree class="size-4 text-brand-600" />
                            {{ t('Mundarija') }}
                        </h2>
                        <ol class="space-y-1">
                            <li v-for="item in toc" :key="item.id">
                                <a
                                    :href="`#${item.id}`"
                                    class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-navy-600 transition-all hover:bg-brand-50 hover:pl-3 hover:text-brand-700"
                                >
                                    <span
                                        class="size-1.5 shrink-0 rounded-full bg-navy-200 transition-colors group-hover:bg-brand-600"
                                        aria-hidden="true"
                                    />
                                    {{ item.title }}
                                </a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </aside>
        </div>
    </div>
</template>

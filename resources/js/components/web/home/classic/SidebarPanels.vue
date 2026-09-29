<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookMarked,
    CalendarClock,
    ChevronRight,
    Fingerprint,
    Mail,
    MapPin,
    Phone,
    Unlock,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import NewsletterForm from '@/components/web/NewsletterForm.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import { about, contact, guidelines, register } from '@/routes';
import type { JournalSocialNetwork } from '@/types';
import type { InertiaLinkProps } from '@inertiajs/vue3';

/**
 * Klassik variant o'ng ustuni (home.png): jurnal haqida, rekvizitlar,
 * obuna, tezkor havolalar, aloqa.
 */
const journal = computed(() => usePage().props.journal);

const socials = computed(
    () =>
        Object.entries(journal.value.socials) as [
            JournalSocialNetwork,
            string,
        ][],
);

type Info = { label: string; value: string; icon: Component };

const info = computed<Info[]>(() => {
    const rows: Info[] = [];

    if (journal.value.issn || journal.value.eissn) {
        rows.push({
            label: 'ISSN (print) / e-ISSN',
            value: [journal.value.issn, journal.value.eissn]
                .filter(Boolean)
                .join(' / '),
            icon: BookMarked,
        });
    }

    if (journal.value.doiPrefix) {
        rows.push({
            label: 'DOI',
            value: journal.value.doiPrefix,
            icon: Fingerprint,
        });
    }

    if (journal.value.frequency) {
        rows.push({
            label: 'Chop etish davriyligi',
            value: journal.value.frequency,
            icon: CalendarClock,
        });
    }

    rows.push({
        label: 'Open Access',
        value: 'Barcha maqolalar ochiq',
        icon: Unlock,
    });

    return rows;
});

const quickLinks: { title: string; href: InertiaLinkProps['href'] }[] = [
    { title: 'Maqola yuborish', href: register() },
    { title: "Maqola yozish bo'yicha ko'rsatmalar", href: guidelines() },
    { title: 'Peer-review jarayoni', href: guidelines() },
    { title: 'Etika va siyosat', href: about() },
    { title: "Tahrir hay'ati", href: about() },
    { title: 'Aloqa', href: contact() },
];
</script>

<template>
    <div class="space-y-5">
        <section
            class="relative overflow-hidden rounded-xl bg-navy-gradient p-6 text-white shadow-card"
        >
            <div class="absolute inset-0 bg-girih opacity-[0.06]" />
            <h2 class="relative font-serif text-2xl font-semibold text-white">
                Jurnal haqida
            </h2>
            <p class="relative mt-3 text-sm leading-relaxed text-white/80">
                "{{ journal.name }}" ilmiy jurnali tarix, etnologiya,
                etnografiya, antropologiya va filologiya sohalaridagi yangi
                ilmiy natijalarni e'lon qilish, fanlararo tadqiqotlarni
                rivojlantirish va xalqaro ilmiy hamkorlikni mustahkamlashga
                qaratilgan.
            </p>
            <Link
                :href="about()"
                class="relative mt-5 inline-flex h-9 items-center gap-1.5 rounded-full border border-white/40 px-4 text-sm font-semibold transition-colors hover:bg-white/10"
            >
                Batafsil
                <ArrowRight class="size-4" />
            </Link>
        </section>

        <section class="surface-card p-5">
            <h2 class="mb-4 font-serif text-lg font-semibold text-navy-950">
                Jurnal ma'lumotlari
            </h2>
            <dl class="space-y-4">
                <div v-for="row in info" :key="row.label" class="flex gap-3">
                    <component
                        :is="row.icon"
                        class="mt-0.5 size-5 shrink-0 text-navy-700"
                        :stroke-width="1.6"
                    />
                    <div class="flex min-w-0 flex-col-reverse">
                        <dd
                            class="text-sm font-semibold break-words text-navy-900"
                        >
                            {{ row.value }}
                        </dd>
                        <dt class="text-xs text-navy-500">{{ row.label }}</dt>
                    </div>
                </div>
            </dl>
        </section>

        <section
            class="relative overflow-hidden rounded-xl bg-navy-gradient p-5 text-white shadow-card"
        >
            <h2 class="font-serif text-lg font-semibold text-white">
                Yangiliklardan xabardor bo'ling
            </h2>
            <p class="mt-1 mb-4 text-xs text-white/70">
                Jurnal yangiliklari va yangi sonlar haqida xabar olish uchun
                obuna bo'ling.
            </p>
            <NewsletterForm id="sidebar-newsletter-email" compact />
        </section>

        <section class="surface-card p-5">
            <h2 class="mb-2 font-serif text-lg font-semibold text-navy-950">
                Tez havolalar
            </h2>
            <ul class="divide-y divide-line">
                <li v-for="link in quickLinks" :key="link.title">
                    <Link
                        :href="link.href"
                        class="group flex items-center justify-between gap-3 py-2.5 text-sm text-navy-700 hover:text-brand-700"
                    >
                        {{ link.title }}
                        <ChevronRight
                            class="size-4 shrink-0 text-navy-300 transition-transform group-hover:translate-x-0.5 group-hover:text-brand-600"
                        />
                    </Link>
                </li>
            </ul>
        </section>

        <section class="rounded-xl border border-gold-200 bg-gold-100/50 p-5">
            <h2 class="mb-3 font-serif text-lg font-semibold text-gold-700">
                Biz bilan bog'laning
            </h2>
            <ul class="space-y-2.5 text-sm text-navy-800">
                <li v-if="journal.contact.email">
                    <a
                        :href="`mailto:${journal.contact.email}`"
                        class="inline-flex items-center gap-2.5 hover:text-brand-700"
                    >
                        <Mail class="size-4 text-navy-600" />
                        {{ journal.contact.email }}
                    </a>
                </li>
                <li
                    v-if="journal.contact.phone"
                    class="flex items-center gap-2.5"
                >
                    <Phone class="size-4 text-navy-600" />
                    {{ journal.contact.phone }}
                </li>
                <li
                    v-if="journal.contact.address"
                    class="flex items-center gap-2.5"
                >
                    <MapPin class="size-4 text-navy-600" />
                    {{ journal.contact.address }}
                </li>
            </ul>
            <div v-if="socials.length" class="mt-4 flex gap-3 text-navy-800">
                <a
                    v-for="[network, url] in socials"
                    :key="network"
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="network"
                    class="hover:text-brand-700"
                >
                    <SocialIcon :network="network" />
                </a>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import NewsletterForm from '@/components/web/NewsletterForm.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import { footerQuickLinks, footerUsefulLinks } from '@/navigation/web';
import { cn } from '@/lib/utils';
import type { JournalSocialNetwork } from '@/types';

/**
 * Sayt footer'i (dizayn: home_2.png): brend, havolalar, aloqa, obuna formasi.
 */
const { wide = false } = defineProps<{
    /** Keng konteyner (muallif kabineti) */
    wide?: boolean;
}>();

const container = computed(() => (wide ? 'max-w-[100rem]' : 'max-w-7xl'));

const journal = computed(() => usePage().props.journal);

const socials = computed(
    () =>
        Object.entries(journal.value.socials) as [
            JournalSocialNetwork,
            string,
        ][],
);

const phoneHref = computed(
    () => `tel:${(journal.value.contact.phone ?? '').replace(/[^\d+]/g, '')}`,
);

const quickLinks = footerQuickLinks();
const usefulLinks = footerUsefulLinks();
const year = new Date().getFullYear();
</script>

<template>
    <footer class="relative overflow-hidden bg-navy-gradient text-white/80">
        <div
            class="pointer-events-none absolute inset-0 bg-girih opacity-[0.04]"
            aria-hidden="true"
        />

        <div
            :class="
                cn(
                    'relative mx-auto grid grid-cols-2 gap-x-6 gap-y-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.3fr_1fr_1fr_1.35fr_1.5fr] lg:gap-8 lg:px-8',
                    container,
                )
            "
        >
            <div class="col-span-2 lg:col-span-1">
                <BrandLogo tone="light" size="md" />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/65">
                    {{ journal.description }}
                </p>
            </div>

            <nav aria-label="Tezkor havolalar">
                <h2
                    class="mb-4 font-sans text-sm font-semibold tracking-wide text-white"
                >
                    Tezkor havolalar
                </h2>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="item in quickLinks" :key="item.title">
                        <Link
                            :href="item.href"
                            class="transition-colors hover:text-white"
                        >
                            {{ item.title }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <nav aria-label="Foydali havolalar">
                <h2
                    class="mb-4 font-sans text-sm font-semibold tracking-wide text-white"
                >
                    Foydali havolalar
                </h2>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="item in usefulLinks" :key="item.title">
                        <Link
                            :href="item.href"
                            class="transition-colors hover:text-white"
                        >
                            {{ item.title }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="col-span-2 sm:col-span-1">
                <h2
                    class="mb-4 font-sans text-sm font-semibold tracking-wide text-white"
                >
                    Biz bilan bog'laning
                </h2>
                <ul class="space-y-3 text-sm">
                    <li v-if="journal.contact.email">
                        <a
                            :href="`mailto:${journal.contact.email}`"
                            class="flex items-start gap-2.5 whitespace-nowrap transition-colors hover:text-white"
                        >
                            <Mail
                                class="mt-0.5 size-4 shrink-0 text-gold-300"
                            />
                            {{ journal.contact.email }}
                        </a>
                    </li>
                    <li v-if="journal.contact.phone">
                        <a
                            :href="phoneHref"
                            class="flex items-start gap-2.5 transition-colors hover:text-white"
                        >
                            <Phone
                                class="mt-0.5 size-4 shrink-0 text-gold-300"
                            />
                            {{ journal.contact.phone }}
                        </a>
                    </li>
                    <li
                        v-if="journal.contact.address"
                        class="flex items-start gap-2.5"
                    >
                        <MapPin class="mt-0.5 size-4 shrink-0 text-gold-300" />
                        {{ journal.contact.address }}
                    </li>
                </ul>
                <div v-if="socials.length" class="mt-5 flex gap-2">
                    <a
                        v-for="[network, url] in socials"
                        :key="network"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="network"
                        class="flex size-9 items-center justify-center rounded-full border border-white/15 transition-colors hover:border-white/40 hover:text-white"
                    >
                        <SocialIcon :network="network" />
                    </a>
                </div>
            </div>

            <div class="col-span-2 sm:col-span-1">
                <h2
                    class="mb-2 font-sans text-sm font-semibold tracking-wide text-white"
                >
                    Yangiliklardan xabardor bo'ling
                </h2>
                <p class="mb-4 text-sm text-white/65">
                    Jurnal yangiliklari va yangi sonlar haqida birinchilardan
                    bo'lib xabar oling.
                </p>
                <NewsletterForm id="footer-newsletter-email" />
            </div>
        </div>

        <div class="relative border-t border-white/10">
            <div
                :class="
                    cn(
                        'mx-auto flex flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-white/55 sm:flex-row sm:px-6 lg:px-8',
                        container,
                    )
                "
            >
                <p>
                    © {{ year }} "{{ journal.name }}" ilmiy jurnali. Barcha
                    huquqlar himoyalangan.
                </p>
                <p v-if="journal.issn">ISSN {{ journal.issn }}</p>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { socialLabel } from '@/lib/social';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUp, ChevronRight, Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import NewsletterForm from '@/components/web/NewsletterForm.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import { footerQuickLinks, footerUsefulLinks } from '@/navigation/web';
import { home } from '@/routes';
import { siteContainer, wideContainer } from '@/lib/layout';
import { cn } from '@/lib/utils';
import type { JournalSocialNetwork } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Sayt footer'i (dizayn: home_2.png): brend va ijtimoiy tarmoqlar, havolalar, aloqa,
 * obuna formasi. Havolalarda oltin chiziq, siljish va soya effektlari.
 */
const { wide = false } = defineProps<{
    /** Keng konteyner (muallif kabineti) */
    wide?: boolean;
}>();

// Sahifa kontenti bilan bir xil kenglik (90%, eng ko'pi 1700px)
const container = computed(() => (wide ? wideContainer : siteContainer));

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

const quickLinks = computed(() => footerQuickLinks());
const usefulLinks = computed(() => footerUsefulLinks());
const year = new Date().getFullYear();

const contacts = computed(
    () =>
        [
            journal.value.contact.email && {
                key: 'email',
                icon: Mail,
                label: journal.value.contact.email,
                href: `mailto:${journal.value.contact.email}`,
                breakAll: true,
            },
            journal.value.contact.phone && {
                key: 'phone',
                icon: Phone,
                label: journal.value.contact.phone,
                href: phoneHref.value,
                breakAll: false,
            },
            journal.value.contact.address && {
                key: 'address',
                icon: MapPin,
                label: journal.value.contact.address,
                href: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(journal.value.contact.address)}`,
                breakAll: false,
            },
        ].filter(Boolean) as {
            key: string;
            icon: typeof Mail;
            label: string;
            href: string;
            breakAll: boolean;
        }[],
);

function scrollTop(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const heading =
    'relative mb-5 pb-3 font-sans text-sm font-semibold tracking-wide text-white after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-8 after:rounded-full after:bg-gradient-to-r after:from-gold-400 after:to-gold-200 after:transition-all after:duration-500 group-hover/col:after:w-14';

const navLink =
    'group/link relative inline-flex items-center gap-1.5 py-0.5 text-white/70 transition-all duration-300 hover:translate-x-1 hover:text-white';
</script>

<template>
    <footer class="relative overflow-hidden bg-navy-gradient text-white/80">
        <div
            class="pointer-events-none absolute inset-0 bg-girih opacity-[0.04]"
            aria-hidden="true"
        />
        <!-- Yumshoq yorug'lik dog'lari -->
        <div
            class="pointer-events-none absolute -top-32 -left-24 size-96 rounded-full bg-brand-500/15 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -right-24 -bottom-40 size-[28rem] rounded-full bg-gold-400/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            :class="
                cn(
                    'relative grid grid-cols-2 gap-x-6 gap-y-12 py-16 lg:grid-cols-[1.35fr_1fr_1fr_1.35fr_1.5fr] lg:gap-10',
                    container,
                )
            "
        >
            <!-- Brend va ijtimoiy tarmoqlar -->
            <div class="col-span-2 lg:col-span-1">
                <Link
                    :href="home()"
                    class="inline-block transition-transform duration-300 hover:-translate-y-0.5"
                >
                    <BrandLogo tone="light" size="md" />
                </Link>
                <p class="mt-5 max-w-xs text-sm leading-relaxed text-white/65">
                    {{ journal.description }}
                </p>
                <div v-if="socials.length" class="mt-6 flex flex-wrap gap-2.5">
                    <a
                        v-for="[network, url] in socials"
                        :key="network"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="socialLabel(network)"
                        class="flex size-10 items-center justify-center rounded-xl bg-white/[0.06] text-white/80 ring-1 ring-white/10 transition-all duration-300 hover:-translate-y-1 hover:bg-gold-400 hover:text-navy-950 hover:shadow-[0_14px_28px_-12px_rgba(196,154,69,0.85)] hover:ring-gold-300"
                    >
                        <SocialIcon :network="network" />
                    </a>
                </div>
            </div>

            <nav class="group/col" :aria-label="t('Tezkor havolalar')">
                <h2 :class="heading">{{ t('Tezkor havolalar') }}</h2>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="(item, i) in quickLinks" :key="i">
                        <Link :href="item.href" :class="navLink">
                            <ChevronRight
                                class="size-3.5 -translate-x-1 text-gold-300 opacity-0 transition-all duration-300 group-hover/link:translate-x-0 group-hover/link:opacity-100"
                            />
                            <span
                                class="relative -ml-5 transition-[margin] duration-300 group-hover/link:ml-0 after:absolute after:-bottom-0.5 after:left-0 after:h-px after:w-0 after:bg-gold-300/70 after:transition-all after:duration-300 group-hover/link:after:w-full"
                            >
                                {{ item.title }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <nav class="group/col" :aria-label="t('Foydali havolalar')">
                <h2 :class="heading">{{ t('Foydali havolalar') }}</h2>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="(item, i) in usefulLinks" :key="i">
                        <Link :href="item.href" :class="navLink">
                            <ChevronRight
                                class="size-3.5 -translate-x-1 text-gold-300 opacity-0 transition-all duration-300 group-hover/link:translate-x-0 group-hover/link:opacity-100"
                            />
                            <span
                                class="relative -ml-5 transition-[margin] duration-300 group-hover/link:ml-0 after:absolute after:-bottom-0.5 after:left-0 after:h-px after:w-0 after:bg-gold-300/70 after:transition-all after:duration-300 group-hover/link:after:w-full"
                            >
                                {{ item.title }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Aloqa -->
            <div class="group/col col-span-2 sm:col-span-1">
                <h2 :class="heading">{{ t("Biz bilan bog'laning") }}</h2>
                <ul class="space-y-2 text-sm">
                    <li v-for="item in contacts" :key="item.key">
                        <a
                            :href="item.href"
                            :target="
                                item.key === 'address' ? '_blank' : undefined
                            "
                            :rel="
                                item.key === 'address'
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                            class="group/contact -mx-2 flex items-center gap-3 rounded-xl px-2 py-1.5 text-white/75 transition-all duration-300 hover:translate-x-1 hover:bg-white/[0.05] hover:text-white"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/[0.06] text-gold-300 ring-1 ring-white/10 transition-all duration-300 group-hover/contact:bg-gold-400 group-hover/contact:text-navy-950 group-hover/contact:shadow-[0_10px_24px_-10px_rgba(196,154,69,0.9)] group-hover/contact:ring-gold-300"
                            >
                                <component :is="item.icon" class="size-4" />
                            </span>
                            <span
                                :class="
                                    cn('min-w-0', item.breakAll && 'break-all')
                                "
                                >{{ item.label }}</span
                            >
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Obuna -->
            <div class="col-span-2 sm:col-span-1">
                <div
                    class="rounded-2xl bg-white/[0.04] p-5 ring-1 ring-white/10 backdrop-blur-sm transition-all duration-500 hover:-translate-y-0.5 hover:bg-white/[0.06] hover:shadow-[0_30px_60px_-30px_rgba(0,0,0,0.75)] hover:ring-gold-300/40"
                >
                    <h2
                        class="mb-2 font-sans text-sm font-semibold tracking-wide text-white"
                    >
                        {{ t("Yangiliklardan xabardor bo'ling") }}
                    </h2>
                    <p class="mb-4 text-sm leading-relaxed text-white/65">
                        {{
                            t(
                                "Jurnal yangiliklari va yangi sonlar haqida birinchilardan bo'lib xabar oling.",
                            )
                        }}
                    </p>
                    <NewsletterForm id="footer-newsletter-email" />
                </div>
            </div>
        </div>

        <div class="relative border-t border-white/10 bg-black/10">
            <div
                :class="
                    cn(
                        'flex flex-col items-center justify-between gap-3 py-5 text-xs text-white/55 sm:flex-row',
                        container,
                    )
                "
            >
                <p class="text-center sm:text-left">
                    ©
                    {{
                        t(
                            ':year «:name» ilmiy jurnali. Barcha huquqlar himoyalangan.',
                            { year, name: journal.name },
                        )
                    }}
                </p>
                <div class="flex items-center gap-3">
                    <span
                        v-if="journal.issn"
                        class="rounded-full bg-white/[0.06] px-3 py-1 font-medium tracking-wide text-white/70 ring-1 ring-white/10"
                        >ISSN {{ journal.issn }}</span
                    >
                    <button
                        type="button"
                        class="group/top inline-flex items-center gap-1.5 rounded-full bg-white/[0.06] px-3 py-1 font-medium text-white/75 ring-1 ring-white/10 transition-all duration-300 hover:-translate-y-0.5 hover:bg-gold-400 hover:text-navy-950 hover:shadow-[0_10px_24px_-10px_rgba(196,154,69,0.9)]"
                        @click="scrollTop"
                    >
                        <ArrowUp
                            class="size-3.5 transition-transform duration-300 group-hover/top:-translate-y-0.5"
                        />
                        {{ t('Yuqoriga') }}
                    </button>
                </div>
            </div>
        </div>
    </footer>
</template>

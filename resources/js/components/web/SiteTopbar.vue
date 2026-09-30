<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import LocaleSwitcher from '@/components/web/LocaleSwitcher.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import type { JournalSocialNetwork } from '@/types';

/**
 * Header ustidagi ingichka to'q ko'k chiziq: aloqa ma'lumotlari va
 * ijtimoiy tarmoqlar (dizayn: home_2.png). Mobil ekranda yashiriladi.
 */
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
</script>

<template>
    <div class="hidden bg-navy-950 text-xs text-white/80 md:block">
        <div
            class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8"
        >
            <div class="flex items-center gap-6">
                <a
                    v-if="journal.contact.email"
                    :href="`mailto:${journal.contact.email}`"
                    class="inline-flex items-center gap-2 transition-colors hover:text-white"
                >
                    <Mail class="size-3.5 text-gold-300" />
                    {{ journal.contact.email }}
                </a>
                <a
                    v-if="journal.contact.phone"
                    :href="phoneHref"
                    class="inline-flex items-center gap-2 transition-colors hover:text-white"
                >
                    <Phone class="size-3.5 text-gold-300" />
                    {{ journal.contact.phone }}
                </a>
                <span
                    v-if="journal.contact.address"
                    class="hidden items-center gap-2 lg:inline-flex"
                >
                    <MapPin class="size-3.5 text-gold-300" />
                    {{ journal.contact.address }}
                </span>
            </div>

            <div class="flex items-center gap-4">
                <span v-if="journal.issn" class="hidden lg:inline">
                    ISSN {{ journal.issn }}
                </span>
                <a
                    v-for="[network, url] in socials"
                    :key="network"
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="network"
                    class="transition-colors hover:text-white"
                >
                    <SocialIcon :network="network" />
                </a>
                <span class="h-4 w-px bg-white/20" aria-hidden="true" />
                <LocaleSwitcher tone="dark" />
            </div>
        </div>
    </div>
</template>

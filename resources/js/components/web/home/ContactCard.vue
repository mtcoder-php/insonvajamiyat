<script setup lang="ts">
import { socialLabel } from '@/lib/social';
import { usePage } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import type { JournalSocialNetwork } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Biz bilan bog'laning" — oltin tusli karta (home.png).
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
    <HomeCard tone="cream" :title="t('Biz bilan bog\'laning')">
        <ul class="space-y-2.5 text-sm text-navy-800">
            <li v-if="journal.contact.email">
                <a
                    :href="`mailto:${journal.contact.email}`"
                    class="inline-flex items-center gap-3 transition-colors hover:text-brand-700"
                >
                    <Mail class="size-4 text-navy-700" />
                    {{ journal.contact.email }}
                </a>
            </li>
            <li v-if="journal.contact.phone">
                <a
                    :href="phoneHref"
                    class="inline-flex items-center gap-3 transition-colors hover:text-brand-700"
                >
                    <Phone class="size-4 text-navy-700" />
                    {{ journal.contact.phone }}
                </a>
            </li>
            <li v-if="journal.contact.address" class="flex items-center gap-3">
                <MapPin class="size-4 text-navy-700" />
                {{ journal.contact.address }}
            </li>
        </ul>
        <div v-if="socials.length" class="mt-4 flex gap-2">
            <a
                v-for="[network, url] in socials"
                :key="network"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                :aria-label="socialLabel(network)"
                class="flex size-9 items-center justify-center rounded-full text-navy-900 transition-all duration-300 hover:-translate-y-0.5 hover:bg-navy-900 hover:text-gold-300 hover:shadow-md"
            >
                <SocialIcon :network="network" />
            </a>
        </div>
    </HomeCard>
</template>

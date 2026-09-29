<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import { about, guidelines } from '@/routes';
import type { JournalSocialNetwork } from '@/types';

/**
 * Klassik variant ustki chizig'i (home.png): tillar, tezkor havolalar,
 * ijtimoiy tarmoqlar. Rus/ingliz tillari i18n bosqichida yoqiladi.
 */
const journal = computed(() => usePage().props.journal);

const socials = computed(
    () =>
        Object.entries(journal.value.socials) as [
            JournalSocialNetwork,
            string,
        ][],
);

const languages = [
    { code: 'uz', label: "O'zbek", available: true },
    { code: 'ru', label: 'Русский', available: false },
    { code: 'en', label: 'English', available: false },
];
</script>

<template>
    <div class="hidden bg-navy-800 text-xs text-white/85 md:block">
        <div
            class="mx-auto flex h-9 max-w-7xl items-stretch justify-between gap-6 px-4 sm:px-6 lg:px-8"
        >
            <ul class="flex items-stretch" aria-label="Sayt tili">
                <li v-for="language in languages" :key="language.code">
                    <span
                        :class="[
                            'flex h-full items-center px-3',
                            language.code === 'uz'
                                ? 'bg-white/10 font-semibold text-white'
                                : 'cursor-not-allowed text-white/45',
                        ]"
                        :title="language.available ? undefined : 'Tez orada'"
                        :aria-current="
                            language.code === 'uz' ? 'true' : undefined
                        "
                    >
                        {{ language.label }}
                    </span>
                </li>
            </ul>

            <div class="flex items-center gap-5">
                <Link
                    :href="about()"
                    class="hidden transition-colors hover:text-white xl:inline"
                >
                    Tashrif buyuruvchilar uchun
                </Link>
                <Link
                    :href="guidelines()"
                    class="transition-colors hover:text-white"
                >
                    Mualliflar uchun
                </Link>
                <Link
                    :href="guidelines()"
                    class="hidden transition-colors hover:text-white lg:inline"
                >
                    Taqrizchilar uchun
                </Link>
                <span v-if="socials.length" class="h-4 w-px bg-white/20" />
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
            </div>
        </div>
    </div>
</template>

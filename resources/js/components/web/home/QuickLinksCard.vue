<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, FileText } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import { about, contact, dashboard, guidelines, register } from '@/routes';
import { t } from '@/lib/i18n';

/**
 * "Tez havolalar" (home.png).
 */
const { auth } = usePermissions();

const links = computed<{ title: string; href: InertiaLinkProps['href'] }[]>(
    () => [
        {
            title: t('Maqola yuborish'),
            href: auth.value.user ? dashboard() : register(),
        },
        {
            title: t("Maqola yozish bo'yicha ko'rsatmalar"),
            href: guidelines(),
        },
        { title: t('Peer-review jarayoni'), href: guidelines() },
        { title: t('Etika va siyosat'), href: about() },
        { title: t("Tahrir hay'ati"), href: about() },
        { title: t('Aloqa'), href: contact() },
    ],
);
</script>

<template>
    <HomeCard :title="t('Tez havolalar')">
        <ul class="-mx-2">
            <li v-for="(link, i) in links" :key="i">
                <Link
                    :href="link.href"
                    class="group flex items-center gap-2.5 rounded-lg px-2 py-2 text-sm text-navy-800 transition-colors hover:bg-white hover:text-brand-700"
                >
                    <FileText
                        class="size-4 shrink-0 text-navy-500 transition-colors group-hover:text-brand-600"
                    />
                    <span class="flex-1">{{ link.title }}</span>
                    <ArrowRight
                        class="size-4 shrink-0 text-navy-400 transition-all group-hover:translate-x-1 group-hover:text-brand-600"
                    />
                </Link>
            </li>
        </ul>
    </HomeCard>
</template>

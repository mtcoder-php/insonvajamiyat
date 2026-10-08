<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, FilePen, Fingerprint, Library } from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, guidelines, register } from '@/routes';
import { t } from '@/lib/i18n';

/**
 * Mualliflar uchun asosiy imkoniyatlar qatori (home.png).
 */
const { auth } = usePermissions();

const items = computed<
    {
        title: string;
        text: string;
        icon: Component;
        href: InertiaLinkProps['href'];
    }[]
>(() => [
    {
        title: t('Maqola yuborish'),
        text: t('Onlayn topshirish shakli'),
        icon: FilePen,
        href: auth.value.user ? dashboard() : register(),
    },
    {
        title: t('Taqriz jarayoni'),
        text: t('2 bosqichli peer-review'),
        icon: ClipboardCheck,
        href: guidelines(),
    },
    {
        title: t('DOI va ORCID'),
        text: t('Xalqaro standartlar'),
        icon: Fingerprint,
        href: guidelines(),
    },
    {
        title: t('Indekslash'),
        text: t('Xalqaro ilmiy bazalar'),
        icon: Library,
        href: guidelines(),
    },
]);
</script>

<template>
    <ul
        class="grid grid-cols-1 overflow-hidden rounded-xl border border-[#ebe8e1] bg-[#f3f2ee] sm:grid-cols-2 lg:grid-cols-4"
    >
        <li
            v-for="(item, index) in items"
            :key="index"
            :class="[
                'border-[#e2ded5]',
                index > 0 && 'max-sm:border-t',
                index % 2 === 1 && 'sm:border-l',
                index >= 2 && 'sm:max-lg:border-t',
                index === 2 && 'lg:border-l',
            ]"
        >
            <Link
                :href="item.href"
                class="group flex h-full items-start gap-3 p-4 transition-colors duration-300 hover:bg-white xl:p-5"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-lg text-navy-800 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-navy-900 group-hover:text-gold-300 group-hover:shadow-lg group-hover:shadow-navy-900/20"
                >
                    <component
                        :is="item.icon"
                        class="size-7 transition-all duration-300 group-hover:size-5"
                        :stroke-width="1.5"
                    />
                </span>
                <span class="min-w-0 leading-tight">
                    <span
                        class="block font-serif text-sm font-bold text-navy-900 transition-colors group-hover:text-brand-700"
                    >
                        {{ item.title }}
                    </span>
                    <span class="mt-1 block text-xs leading-snug text-navy-500">
                        {{ item.text }}
                    </span>
                </span>
            </Link>
        </li>
    </ul>
</template>

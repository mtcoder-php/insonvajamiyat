<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, FilePen, Fingerprint, Library } from '@lucide/vue';
import type { Component } from 'vue';
import { guidelines, register } from '@/routes';
import type { InertiaLinkProps } from '@inertiajs/vue3';

/**
 * Mualliflar uchun asosiy imkoniyatlar qatori (home.png).
 */
const items: {
    title: string;
    text: string;
    icon: Component;
    href: InertiaLinkProps['href'];
}[] = [
    {
        title: 'Maqola yuborish',
        text: 'Onlayn topshirish shakli',
        icon: FilePen,
        href: register(),
    },
    {
        title: 'Taqriz jarayoni',
        text: '2 bosqichli peer-review',
        icon: ClipboardCheck,
        href: guidelines(),
    },
    {
        title: 'DOI va ORCID',
        text: 'Xalqaro standartlar',
        icon: Fingerprint,
        href: guidelines(),
    },
    {
        title: 'Indekslash',
        text: 'Xalqaro ilmiy bazalar',
        icon: Library,
        href: guidelines(),
    },
];
</script>

<template>
    <ul
        class="grid grid-cols-2 gap-px overflow-hidden surface-card bg-line lg:grid-cols-4"
    >
        <li v-for="item in items" :key="item.title" class="bg-surface">
            <Link
                :href="item.href"
                class="group flex h-full items-center gap-3 p-4 transition-colors hover:bg-brand-50"
            >
                <component
                    :is="item.icon"
                    class="size-8 shrink-0 text-navy-800 group-hover:text-brand-700"
                    :stroke-width="1.4"
                />
                <span class="leading-tight">
                    <span class="block text-sm font-semibold text-navy-900">
                        {{ item.title }}
                    </span>
                    <span class="mt-0.5 block text-xs text-navy-500">
                        {{ item.text }}
                    </span>
                </span>
            </Link>
        </li>
    </ul>
</template>

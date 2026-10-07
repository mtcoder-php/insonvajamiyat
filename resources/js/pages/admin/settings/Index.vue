<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    FolderTree,
    GalleryHorizontalEnd,
    Settings,
} from '@lucide/vue';
import type { Component } from 'vue';
import ArticleTypesPanel from '@/components/admin/settings/ArticleTypesPanel.vue';
import BannersPanel from '@/components/admin/settings/BannersPanel.vue';
import SubjectsPanel from '@/components/admin/settings/SubjectsPanel.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/settings';
import type { SettingsPageProps, SettingsTab } from '@/types';

/**
 * Admin → Sozlamalar: ilmiy yo'nalishlar, maqola turlari va narxlar, bosh sahifa bannerlari.
 * Har bir tab o'z ma'lumotini alohida yuklaydi (?tab=…).
 */
const props = defineProps<SettingsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Sozlamalar', href: index() },
        ],
    },
});

const meta: Record<
    SettingsTab,
    { label: string; hint: string; icon: Component }
> = {
    subjects: {
        label: "Yo'nalishlar",
        hint: 'Rukn va fan sohalari',
        icon: FolderTree,
    },
    types: {
        label: 'Maqola turlari va narxlar',
        hint: "Nashr to'lovi",
        icon: BadgeDollarSign,
    },
    banners: {
        label: 'Bannerlar',
        hint: 'Bosh sahifa slayderi',
        icon: GalleryHorizontalEnd,
    },
};

function go(tab: SettingsTab): void {
    router.get(props.urls.index, tab === 'subjects' ? {} : { tab }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sozlamalar" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            title="Sozlamalar"
            description="Jurnal ma'lumotlari, ilmiy yo'nalishlar, maqola turlari va sayt kontenti."
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <Settings class="size-5" />
                </span>
            </template>
        </PageHeader>

        <nav class="grid grid-cols-1 gap-3 sm:grid-cols-3" role="tablist">
            <button
                v-for="key in tabs"
                :key="key"
                type="button"
                role="tab"
                :aria-selected="tab === key"
                :class="
                    cn(
                        'group flex items-center gap-3 rounded-xl border bg-white px-4 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-20px_rgba(0,36,66,0.45)]',
                        tab === key
                            ? 'border-brand-300 ring-2 ring-brand-100'
                            : 'border-line hover:border-brand-200',
                    )
                "
                @click="go(key)"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-colors',
                            tab === key
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-brand-50 text-brand-600 group-hover:bg-brand-100',
                        )
                    "
                >
                    <component :is="meta[key].icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-[14px] font-bold text-navy-950">{{
                        meta[key].label
                    }}</span>
                    <span class="block text-xs text-navy-500">{{
                        meta[key].hint
                    }}</span>
                </span>
            </button>
        </nav>

        <SubjectsPanel
            v-if="tab === 'subjects' && subjects"
            :subjects="subjects"
            :store-url="urls.subjects"
        />
        <ArticleTypesPanel
            v-else-if="tab === 'types' && types && urls.types"
            :types="types"
            :store-url="urls.types"
        />
        <BannersPanel
            v-else-if="tab === 'banners' && banners"
            :banners="banners"
            :store-url="urls.banners"
        />
    </div>
</template>

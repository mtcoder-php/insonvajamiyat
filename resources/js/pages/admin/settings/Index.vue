<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    BookOpen,
    CalendarDays,
    FolderTree,
    GalleryHorizontalEnd,
    Handshake,
    Newspaper,
    Settings,
} from '@lucide/vue';
import type { Component } from 'vue';
import ArticleTypesPanel from '@/components/admin/settings/ArticleTypesPanel.vue';
import BannersPanel from '@/components/admin/settings/BannersPanel.vue';
import BooksPanel from '@/components/admin/settings/BooksPanel.vue';
import EventsPanel from '@/components/admin/settings/EventsPanel.vue';
import PartnersPanel from '@/components/admin/settings/PartnersPanel.vue';
import PostsPanel from '@/components/admin/settings/PostsPanel.vue';
import SubjectsPanel from '@/components/admin/settings/SubjectsPanel.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/settings';
import type { SettingsPageProps, SettingsTab } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Admin → Sozlamalar: ilmiy yo'nalishlar, maqola turlari va narxlar, bosh sahifa bannerlari,
 * yangiliklar, tadbirlar, tavsiya etilgan kitoblar va hamkorlar.
 * Har bir tab o'z ma'lumotini alohida yuklaydi (?tab=…).
 */
const props = defineProps<SettingsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Sozlamalar'), href: index() },
        ],
    },
});

const meta: Record<
    SettingsTab,
    { label: string; hint: string; icon: Component }
> = {
    subjects: {
        label: t("Yo'nalishlar"),
        hint: t('Rukn va fan sohalari'),
        icon: FolderTree,
    },
    types: {
        label: t('Maqola turlari va narxlar'),
        hint: t("Nashr to'lovi"),
        icon: BadgeDollarSign,
    },
    banners: {
        label: t('Bannerlar'),
        hint: t('Bosh sahifa slayderi'),
        icon: GalleryHorizontalEnd,
    },
    posts: {
        label: t("Yangiliklar va e'lonlar"),
        hint: t('Sayt xabarlari'),
        icon: Newspaper,
    },
    events: {
        label: t('Tadbirlar'),
        hint: t('Konferensiya, seminar'),
        icon: CalendarDays,
    },
    books: {
        label: t('Tavsiya etilgan kitoblar'),
        hint: t("Bosh sahifa o'ng ustuni"),
        icon: BookOpen,
    },
    partners: {
        label: t('Hamkorlar'),
        hint: t('Indekslash bazalari'),
        icon: Handshake,
    },
};

function go(tab: SettingsTab): void {
    router.get(props.urls.index, tab === 'subjects' ? {} : { tab }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('Sozlamalar')" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            :title="t('Sozlamalar')"
            :description="
                t(
                    'Jurnal ma\'lumotlari, ilmiy yo\'nalishlar, maqola turlari va sayt kontenti.',
                )
            "
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <Settings class="size-5" />
                </span>
            </template>
        </PageHeader>

        <nav
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
            role="tablist"
        >
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
        <PostsPanel
            v-else-if="tab === 'posts' && posts"
            :posts="posts"
            :filters="filters"
            :store-url="urls.posts"
            :index-url="urls.index"
        />
        <EventsPanel
            v-else-if="tab === 'events' && events"
            :events="events"
            :filters="filters"
            :store-url="urls.events"
            :index-url="urls.index"
        />
        <BooksPanel
            v-else-if="tab === 'books' && books"
            :books="books"
            :store-url="urls.books"
        />
        <PartnersPanel
            v-else-if="tab === 'partners' && partners && partnerTypes"
            :partners="partners"
            :types="partnerTypes"
            :store-url="urls.partners"
        />
    </div>
</template>

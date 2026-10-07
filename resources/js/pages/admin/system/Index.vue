<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AtSign,
    Building2,
    Landmark,
    Mail,
    MonitorCog,
    Settings2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { ref, watch } from 'vue';
import JournalPanel from '@/components/admin/system/JournalPanel.vue';
import MailPanel from '@/components/admin/system/MailPanel.vue';
import StatusPanel from '@/components/admin/system/StatusPanel.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/system';
import type { SystemPageProps, SystemTab } from '@/types';

/**
 * Admin → Tizim sozlamalari: jurnal rekvizitlari, aloqa, bank rekvizitlari, pochta va tizim holati.
 * Tablar brauzerda almashadi (saqlanmagan o'zgarishlar yo'qolmaydi); URL ?tab= bilan yangilanadi.
 */
const props = defineProps<SystemPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Tizim sozlamalari', href: index() },
        ],
    },
});

const tab = ref<SystemTab>(props.tab);

watch(tab, (value) => {
    const url = new URL(window.location.href);

    if (value === 'journal') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', value);
    }

    window.history.replaceState(window.history.state, '', url);
});

const tabs: { key: SystemTab; label: string; hint: string; icon: Component }[] =
    [
        {
            key: 'journal',
            label: 'Jurnal',
            hint: 'Nom, ISSN, DOI',
            icon: Building2,
        },
        {
            key: 'contacts',
            label: 'Aloqa',
            hint: 'Email, telefon, tarmoqlar',
            icon: AtSign,
        },
        {
            key: 'payment',
            label: 'Rekvizitlar',
            hint: "Bank orqali to'lov",
            icon: Landmark,
        },
        { key: 'mail', label: 'Pochta', hint: 'SMTP va test xat', icon: Mail },
        {
            key: 'status',
            label: 'Tizim holati',
            hint: 'Server va xizmatlar',
            icon: MonitorCog,
        },
    ];
</script>

<template>
    <Head title="Tizim sozlamalari" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            title="Tizim sozlamalari"
            description="Jurnal rekvizitlari, aloqa ma'lumotlari, pochta va server holati. O'zgarishlar darhol saytda aks etadi."
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <Settings2 class="size-5" />
                </span>
            </template>
        </PageHeader>

        <nav
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5"
            role="tablist"
        >
            <button
                v-for="item in tabs"
                :key="item.key"
                type="button"
                role="tab"
                :aria-selected="tab === item.key"
                :class="
                    cn(
                        'group flex items-center gap-3 rounded-xl border bg-white px-4 py-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-20px_rgba(0,36,66,0.45)]',
                        tab === item.key
                            ? 'border-brand-300 ring-2 ring-brand-100'
                            : 'border-line hover:border-brand-200',
                    )
                "
                @click="tab = item.key"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-colors',
                            tab === item.key
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-brand-50 text-brand-600 group-hover:bg-brand-100',
                        )
                    "
                >
                    <component :is="item.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span
                        class="block truncate text-[14px] font-bold text-navy-950"
                        >{{ item.label }}</span
                    >
                    <span class="block truncate text-xs text-navy-500">{{
                        item.hint
                    }}</span>
                </span>
            </button>
        </nav>

        <JournalPanel
            v-show="
                tab === 'journal' || tab === 'contacts' || tab === 'payment'
            "
            :journal="journalForm"
            :section="
                tab === 'contacts'
                    ? 'contacts'
                    : tab === 'payment'
                      ? 'payment'
                      : 'journal'
            "
            :url="urls.journal"
        />
        <MailPanel
            v-show="tab === 'mail'"
            :mail="mailForm"
            :password="mailPassword"
            :url="urls.mail"
            :test-url="urls.mailTest"
        />
        <StatusPanel v-if="tab === 'status'" :rows="status" />
    </div>
</template>

<script setup lang="ts">
import {
    Database,
    ExternalLink,
    Handshake,
    PenLine,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';
import type { SettingsPartner, SettingsPartnerType } from '@/types';
import DeleteDialog from './DeleteDialog.vue';
import PartnerDialog from './PartnerDialog.vue';
import { t } from '@/lib/i18n';

/** Hamkorlar va indekslash bazalari — tur bo'yicha guruhlangan logo kartochkalari */
const props = defineProps<{
    partners: SettingsPartner[];
    types: { value: SettingsPartnerType; label: string }[];
    storeUrl: string;
}>();

const groups = computed(() =>
    (
        [
            {
                key: 'indexing',
                title: t('Indekslash bazalari'),
                icon: Database,
            },
            {
                key: 'partner',
                title: t('Hamkor tashkilotlar'),
                icon: Handshake,
            },
        ] as const
    ).map((group) => ({
        ...group,
        items: props.partners.filter((p) => p.type === group.key),
    })),
);

const editing = ref<SettingsPartner | null>(null);
const formOpen = ref(false);
const newType = ref<SettingsPartnerType>('partner');
const removing = ref<SettingsPartner | null>(null);
const deleteOpen = ref(false);

function open(
    partner: SettingsPartner | null,
    type: SettingsPartnerType = 'partner',
): void {
    editing.value = partner;
    newType.value = type;
    formOpen.value = true;
}

function remove(partner: SettingsPartner): void {
    removing.value = partner;
    deleteOpen.value = true;
}

function host(url: string): string {
    try {
        return new URL(url).host.replace(/^www\./, '');
    } catch {
        return url;
    }
}
</script>

<template>
    <section class="grid grid-cols-1 gap-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Hamkorlar va indekslash bazalari') }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "Bosh sahifa pastida logolar qatori sifatida ko'rinadi",
                        )
                    }}
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> {{ t("Hamkor qo'shish") }}
            </button>
        </header>

        <div v-for="group in groups" :key="group.key">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3
                    class="inline-flex items-center gap-2 text-[13px] font-bold text-navy-700"
                >
                    <component :is="group.icon" class="size-4 text-brand-600" />
                    {{ group.title }}
                    <span
                        class="rounded-full bg-[#eef3fa] px-2 text-[11px] text-navy-500 tabular-nums"
                        >{{ group.items.length }}</span
                    >
                </h3>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 transition-colors hover:text-brand-500"
                    @click="open(null, group.key)"
                >
                    <Plus class="size-3.5" /> {{ t("Qo'shish") }}
                </button>
            </div>

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="partner in group.items"
                    :key="partner.id"
                    class="group overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                >
                    <div
                        class="relative flex h-24 items-center justify-center border-b border-line bg-[#fafbfd] px-6"
                    >
                        <img
                            v-if="partner.logoUrl"
                            :src="partner.logoUrl"
                            :alt="partner.name"
                            loading="lazy"
                            :class="
                                cn(
                                    'max-h-14 max-w-full object-contain transition-all duration-300 group-hover:scale-105',
                                    !partner.isActive && 'opacity-50 grayscale',
                                )
                            "
                        />
                        <span
                            v-else
                            class="text-center font-serif text-[15px] font-semibold [overflow-wrap:anywhere] text-navy-700"
                            >{{ partner.name }}</span
                        >
                        <span
                            v-if="!partner.isActive"
                            class="absolute top-2 left-2 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600"
                            >{{ t('Nofaol') }}</span
                        >
                    </div>
                    <div class="flex items-start gap-2 px-3.5 py-3">
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-[13px] font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                            >
                                {{ partner.name }}
                            </p>
                            <p class="truncate text-[11px] text-navy-500">
                                {{
                                    partner.translations.subtitle.uz ||
                                    (partner.url ? host(partner.url) : '—')
                                }}
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 gap-0.5">
                            <a
                                v-if="partner.url"
                                :href="partner.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                :aria-label="t('Saytni ochish')"
                            >
                                <ExternalLink class="size-4" />
                            </a>
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                :aria-label="t('Tahrirlash')"
                                @click="open(partner)"
                            >
                                <PenLine class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                :aria-label="t('O\'chirish')"
                                @click="remove(partner)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </span>
                    </div>
                </article>
            </div>
            <p
                v-if="!group.items.length"
                class="rounded-xl border border-dashed border-line bg-white px-4 py-6 text-center text-sm text-navy-400"
            >
                {{ t("Hali qo'shilmagan") }}
            </p>
        </div>

        <PartnerDialog
            v-model:open="formOpen"
            :partner="editing"
            :types="types"
            :default-type="newType"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('Hamkorni o\'chirish')"
            :description="
                t('«:name» va uning logosi o\'chiriladi.', {
                    name: removing?.name ?? '',
                })
            "
        />
    </section>
</template>

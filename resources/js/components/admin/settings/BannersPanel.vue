<script setup lang="ts">
import {
    CalendarRange,
    ExternalLink,
    Eye,
    EyeOff,
    PenLine,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { SettingsBanner } from '@/types';
import BannerDialog from './BannerDialog.vue';
import DeleteDialog from './DeleteDialog.vue';

/** Bosh sahifa slayderi bannerlari */
defineProps<{ banners: SettingsBanner[]; storeUrl: string }>();

const editing = ref<SettingsBanner | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsBanner | null>(null);
const deleteOpen = ref(false);

function open(banner: SettingsBanner | null): void {
    editing.value = banner;
    formOpen.value = true;
}

function remove(banner: SettingsBanner): void {
    removing.value = banner;
    deleteOpen.value = true;
}
</script>

<template>
    <section>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    Bosh sahifa bannerlari
                </h2>
                <p class="text-xs text-navy-500">
                    Faol banner bo'lmasa, bosh sahifada standart slaydlar
                    ko'rsatiladi
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> Banner qo'shish
            </button>
        </header>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <article
                v-for="banner in banners"
                :key="banner.id"
                class="group overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-20px_rgba(0,36,66,0.45)]"
            >
                <div class="relative aspect-[16/6] overflow-hidden bg-navy-950">
                    <img
                        v-if="banner.imageUrl"
                        :src="banner.imageUrl"
                        :alt="banner.title"
                        :class="
                            cn(
                                'size-full object-cover transition-transform duration-700 group-hover:scale-105',
                                !banner.visible && 'opacity-70 grayscale-[60%]',
                            )
                        "
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-navy-950/85 via-navy-950/40 to-transparent"
                    />
                    <div
                        class="absolute inset-y-0 left-0 flex max-w-[75%] flex-col justify-center gap-1 p-5 text-white"
                    >
                        <p
                            class="line-clamp-2 font-serif text-lg leading-tight font-bold"
                        >
                            {{ banner.title }}
                        </p>
                        <p
                            v-if="banner.translations.subtitle.uz"
                            class="line-clamp-2 text-xs text-white/80"
                        >
                            {{ banner.translations.subtitle.uz }}
                        </p>
                        <span
                            v-if="banner.translations.button_text.uz"
                            class="mt-1 inline-flex w-fit rounded-md bg-brand-600 px-2.5 py-1 text-[11px] font-semibold"
                            >{{ banner.translations.button_text.uz }}</span
                        >
                    </div>
                    <span
                        :class="
                            cn(
                                'absolute top-3 right-3 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold shadow-sm',
                                banner.visible
                                    ? 'bg-emerald-500 text-white'
                                    : 'bg-white/90 text-navy-600',
                            )
                        "
                    >
                        <Eye v-if="banner.visible" class="size-3.5" />
                        <EyeOff v-else class="size-3.5" />
                        {{
                            banner.visible
                                ? "Ko'rinmoqda"
                                : banner.isActive
                                  ? 'Muddatdan tashqari'
                                  : 'Nofaol'
                        }}
                    </span>
                </div>
                <div
                    class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 text-xs text-navy-500"
                >
                    <span class="inline-flex items-center gap-1">
                        <CalendarRange class="size-3.5" />
                        {{
                            banner.startsAt ? formatDate(banner.startsAt) : '…'
                        }}
                        —
                        {{
                            banner.endsAt
                                ? formatDate(banner.endsAt)
                                : 'muddatsiz'
                        }}
                    </span>
                    <a
                        v-if="banner.linkUrl"
                        :href="banner.linkUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex max-w-[14rem] items-center gap-1 truncate text-brand-700 hover:text-brand-600"
                    >
                        <ExternalLink class="size-3.5 shrink-0" />
                        {{ banner.linkUrl }}
                    </a>
                    <span class="tabular-nums"
                        >Tartib: {{ banner.sortOrder }}</span
                    >
                    <span class="ml-auto inline-flex gap-1">
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            aria-label="Tahrirlash"
                            @click="open(banner)"
                        >
                            <PenLine class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                            aria-label="O'chirish"
                            @click="remove(banner)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </span>
                </div>
            </article>
        </div>
        <p
            v-if="!banners.length"
            class="rounded-xl border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-navy-400"
        >
            Bannerlar yo'q — bosh sahifada standart slaydlar ko'rsatilmoqda
        </p>

        <BannerDialog
            v-model:open="formOpen"
            :banner="editing"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            title="Bannerni o'chirish"
            :description="`«${removing?.title ?? ''}» banneri va uning rasmi o'chiriladi.`"
        />
    </section>
</template>

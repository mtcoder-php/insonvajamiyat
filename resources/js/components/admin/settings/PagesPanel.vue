<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    BookMarked,
    ExternalLink,
    Info,
    Mail,
    PenLine,
    RotateCcw,
} from '@lucide/vue';
import { ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { formatDateTime } from '@/lib/format';
import { t } from '@/lib/i18n';
import type { SettingsPage } from '@/types';
import PageDialog from './PageDialog.vue';

/**
 * Statik sahifalar: "Jurnal haqida", "Mualliflar uchun yo'riqnoma", "Aloqa".
 * Tahrirlanmagan sahifada standart matn ko'rsatiladi.
 */
defineProps<{ pages: SettingsPage[] }>();

const icons = { about: Info, guidelines: BookMarked, contact: Mail };

const editing = ref<SettingsPage | null>(null);
const formOpen = ref(false);
const resetting = ref<SettingsPage | null>(null);
const resetOpen = ref(false);
const processing = ref(false);

function edit(page: SettingsPage): void {
    editing.value = page;
    formOpen.value = true;
}

function askReset(page: SettingsPage): void {
    resetting.value = page;
    resetOpen.value = true;
}

function reset(): void {
    if (!resetting.value) {
        return;
    }

    router.delete(resetting.value.urls.reset, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => {
            processing.value = false;
            resetOpen.value = false;
        },
    });
}
</script>

<template>
    <section class="grid grid-cols-1 gap-4">
        <header>
            <h2 class="font-sans text-[15px] font-bold text-navy-950">
                {{ t('Statik sahifalar') }}
            </h2>
            <p class="text-xs text-navy-500">
                {{
                    t(
                        "Sayt menyusidagi «Jurnal haqida», «Yo'riqnoma» va «Aloqa» sahifalari matni. Tahririyat kengashi, yo'nalishlar, narxlar va aloqa ma'lumotlari avtomatik qo'shiladi.",
                    )
                }}
            </p>
        </header>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <article
                v-for="page in pages"
                :key="page.slug"
                class="group flex flex-col rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                :data-test="`page-${page.slug}`"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-all duration-300 group-hover:bg-navy-900 group-hover:text-gold-300"
                    >
                        <component :is="icons[page.slug]" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3
                            class="truncate text-[15px] font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                        >
                            {{ page.label }}
                        </h3>
                        <p class="truncate text-xs text-navy-500">
                            {{ page.title.uz }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px]">
                    <span
                        v-if="page.isCustom"
                        class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 ring-1 ring-emerald-200"
                        >{{ t('Tahrirlangan') }}</span
                    >
                    <span
                        v-else
                        class="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 ring-1 ring-amber-200"
                        >{{ t('Standart matn') }}</span
                    >
                    <span class="text-navy-500">{{
                        t(":count ta bo'lim", { count: page.sections.length })
                    }}</span>
                </div>
                <p
                    v-if="page.updatedAt"
                    class="mt-1.5 text-[11px] text-navy-400"
                >
                    {{ formatDateTime(page.updatedAt) }}
                    <template v-if="page.updatedBy">
                        · {{ page.updatedBy }}</template
                    >
                </p>

                <div class="mt-auto flex flex-wrap gap-2 pt-5">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                        @click="edit(page)"
                    >
                        <PenLine class="size-4" /> {{ t('Tahrirlash') }}
                    </button>
                    <a
                        :href="page.publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-white px-3 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                    >
                        <ExternalLink class="size-4" /> {{ t('Saytda') }}
                    </a>
                    <button
                        v-if="page.isCustom"
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-[13px] font-semibold text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                        :title="t('Standart matnga qaytarish')"
                        :aria-label="t('Standart matnga qaytarish')"
                        @click="askReset(page)"
                    >
                        <RotateCcw class="size-4" />
                    </button>
                </div>
            </article>
        </div>

        <PageDialog v-model:open="formOpen" :page="editing" />

        <ActionDialog
            v-model:open="resetOpen"
            :title="t('Standart matnga qaytarish')"
            :description="
                resetting
                    ? t(
                          '«:name» sahifasidagi o\'zgarishlar o\'chiriladi va boshlang\'ich matn ko\'rsatiladi.',
                          { name: resetting.label },
                      )
                    : undefined
            "
            :icon="RotateCcw"
            tone="danger"
            :confirm-text="t('Qaytarish')"
            :processing="processing"
            @confirm="reset"
        />
    </section>
</template>

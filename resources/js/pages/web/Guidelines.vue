<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Clock,
    Download,
    FileText,
    ListTree,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import WebPageHeader from '@/components/web/WebPageHeader.vue';
import ContentSection from '@/components/web/content/ContentSection.vue';
import { formatNumber, formatSum } from '@/lib/format';
import { t, tc } from '@/lib/i18n';
import type { GuidelinesPageProps } from '@/types';

/**
 * "Mualliflar uchun yo'riqnoma": tahririyat yozgan bo'limlar + Word shablon,
 * maqola turlari va nashr narxlari (bazadan), maqola yuborish tugmasi.
 */
const props = defineProps<GuidelinesPageProps>();

const toc = computed(() => [
    ...props.page.sections.map((section, index) => ({
        id: `section-${index + 1}`,
        title: section.heading,
    })),
    ...(props.types.length
        ? [{ id: 'fees', title: t('Maqola turlari va narxlar') }]
        : []),
]);

function price(value: number, currency: string): string {
    if (value <= 0) {
        return t('Bepul');
    }

    return currency === 'UZS' || currency === ''
        ? formatSum(value)
        : `${formatNumber(value)} ${currency}`;
}
</script>

<template>
    <Head :title="page.title" />

    <WebPageHeader
        :title="page.title"
        :description="page.description"
        :crumbs="[{ title: page.title }]"
    >
        <div class="mt-6 flex flex-wrap gap-3">
            <a
                :href="submitUrl"
                class="group inline-flex h-11 items-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(0,108,246,0.9)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-500 hover:shadow-[0_16px_30px_-12px_rgba(0,108,246,0.9)]"
            >
                <Send
                    class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                />
                {{ t('Maqola yuborish') }}
            </a>
            <a
                v-if="template"
                :href="template"
                download
                class="group inline-flex h-11 items-center gap-2 rounded-lg bg-white px-5 text-sm font-semibold text-navy-800 ring-1 ring-[#e6e1d6] transition-all duration-200 hover:-translate-y-0.5 hover:text-brand-700 hover:shadow-md hover:ring-brand-200"
            >
                <Download
                    class="size-4 transition-transform duration-300 group-hover:translate-y-0.5"
                />
                {{ t('Word shablonni yuklab olish') }}
            </a>
        </div>
    </WebPageHeader>

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-8 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_21rem] lg:px-0 lg:py-12"
        >
            <div class="flex min-w-0 flex-col gap-6">
                <ContentSection
                    v-for="(section, index) in page.sections"
                    :id="`section-${index + 1}`"
                    :key="index"
                    :heading="section.heading"
                    :body="section.body"
                />

                <!-- Maqola turlari va narxlar (admin → Sozlamalar → Maqola turlari) -->
                <ContentSection
                    v-if="types.length"
                    id="fees"
                    :heading="t('Maqola turlari va narxlar')"
                >
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="type in types"
                            :key="type.id"
                            class="group flex flex-col gap-2 rounded-xl border border-line bg-[#fafbfd] p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-white hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p
                                    class="flex items-center gap-2 text-[15px] font-semibold text-navy-950 transition-colors group-hover:text-brand-700"
                                >
                                    <FileText
                                        class="size-4 shrink-0 text-brand-600"
                                    />
                                    {{ type.name }}
                                </p>
                                <span
                                    :class="[
                                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
                                        type.price > 0
                                            ? 'bg-navy-900 text-gold-300'
                                            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                    ]"
                                    >{{
                                        price(type.price, type.currency)
                                    }}</span
                                >
                            </div>
                            <p
                                v-if="type.description"
                                class="text-[13px] leading-relaxed text-navy-600"
                            >
                                {{ type.description }}
                            </p>
                            <p
                                v-if="type.reviewDays"
                                class="mt-auto flex items-center gap-1.5 text-xs text-navy-500"
                            >
                                <Clock class="size-3.5" />
                                {{
                                    tc(
                                        "Ko'rib chiqish: ~:count kun",
                                        type.reviewDays,
                                    )
                                }}
                            </p>
                        </li>
                    </ul>
                    <p class="mt-4 text-xs text-navy-500">
                        {{
                            t(
                                "To'lov maqola yuborilgach kabinetda amalga oshiriladi: Click, Payme yoki bank orqali.",
                            )
                        }}
                    </p>
                </ContentSection>
            </div>

            <aside class="min-w-0">
                <div class="flex flex-col gap-5 lg:sticky lg:top-24">
                    <section
                        class="relative overflow-hidden rounded-2xl bg-navy-950 p-6 text-white shadow-[0_24px_48px_-28px_rgba(0,30,60,0.9)]"
                    >
                        <div
                            class="absolute inset-0 -z-0 bg-girih opacity-[0.06]"
                            aria-hidden="true"
                        />
                        <div class="relative">
                            <h2 class="font-serif text-lg font-bold text-white">
                                {{ t('Yuborishdan oldin tekshiring') }}
                            </h2>
                            <ul class="mt-4 space-y-2.5 text-sm text-white/85">
                                <li
                                    v-for="item in [
                                        t(
                                            'Maqola shablon asosida rasmiylashtirilgan',
                                        ),
                                        t(
                                            'Annotatsiya va kalit so\'zlar uch tilda',
                                        ),
                                        t(
                                            'Mualliflar, tashkilot va ORCID ko\'rsatilgan',
                                        ),
                                        t('Adabiyotlar ro\'yxati talabga mos'),
                                    ]"
                                    :key="item"
                                    class="flex items-start gap-2"
                                >
                                    <BadgeCheck
                                        class="mt-0.5 size-4 shrink-0 text-gold-300"
                                    />
                                    {{ item }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <ShieldCheck
                                        class="mt-0.5 size-4 shrink-0 text-gold-300"
                                    />
                                    {{
                                        t("O'xshashlik :value% dan oshmaydi", {
                                            value: plagiarismMax,
                                        })
                                    }}
                                </li>
                            </ul>
                            <a
                                :href="submitUrl"
                                class="group mt-6 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gold-500 text-sm font-semibold text-navy-950 transition-all duration-200 hover:-translate-y-0.5 hover:bg-gold-400 hover:shadow-lg hover:shadow-gold-500/25"
                            >
                                <Send
                                    class="size-4 transition-transform duration-300 group-hover:translate-x-0.5"
                                />
                                {{ t('Maqola yuborish') }}
                            </a>
                        </div>
                    </section>

                    <nav
                        v-if="toc.length > 1"
                        class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                        :aria-label="t('Sahifa mundarijasi')"
                    >
                        <h2
                            class="mb-3 flex items-center gap-2 font-serif text-base font-bold text-navy-950"
                        >
                            <ListTree class="size-4 text-brand-600" />
                            {{ t('Mundarija') }}
                        </h2>
                        <ol class="space-y-1">
                            <li v-for="item in toc" :key="item.id">
                                <a
                                    :href="`#${item.id}`"
                                    class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-navy-600 transition-all hover:bg-brand-50 hover:pl-3 hover:text-brand-700"
                                >
                                    <span
                                        class="size-1.5 shrink-0 rounded-full bg-navy-200 transition-colors group-hover:bg-brand-600"
                                        aria-hidden="true"
                                    />
                                    {{ item.title }}
                                </a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </aside>
        </div>
    </div>
</template>

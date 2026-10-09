<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    CircleAlert,
    House,
    Library,
    LoaderCircle,
    MailCheck,
    MailMinus,
    MailX,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as issuesIndex } from '@/routes/issues';
import { store as unsubscribeStore } from '@/routes/newsletter/unsubscribe';
import type { NewsletterStatusProps } from '@/types';

/**
 * Obuna holati: tasdiqlandi / chiqishni tasdiqlash / chiqildi / havola yaroqsiz.
 * Xatlardagi havolalar shu sahifaga olib keladi (NewsletterSubscriptionController).
 */
const props = defineProps<NewsletterStatusProps>();

const form = useForm({});

type Copy = {
    icon: Component;
    tone: 'success' | 'gold' | 'muted' | 'danger';
    title: string;
    description: string;
};

const copy = computed<Copy>(() => {
    switch (props.state) {
        case 'confirmed':
            return {
                icon: MailCheck,
                tone: 'success',
                title: t('Obuna tasdiqlandi!'),
                description: t(
                    "Rahmat! Endi jurnalning yangi sonlari va muhim yangiliklarini elektron pochtangizga yuboramiz. Har bir xat oxirida obunadan chiqish havolasi bo'ladi.",
                ),
            };
        case 'ask':
            return {
                icon: MailMinus,
                tone: 'gold',
                title: t('Obunadan chiqasizmi?'),
                description: t(
                    "Tasdiqlasangiz, :email manziliga jurnal xatlari boshqa yuborilmaydi. Istalgan vaqtda sayt orqali qayta obuna bo'lishingiz mumkin.",
                    { email: props.email ?? '' },
                ),
            };
        case 'unsubscribed':
            return {
                icon: MailX,
                tone: 'muted',
                title: t('Obunadan chiqdingiz'),
                description: t(
                    "Bu manzilga jurnal xatlari endi yuborilmaydi. Fikringiz o'zgarsa, sayt pastidagi forma orqali qayta obuna bo'lishingiz mumkin.",
                ),
            };
        default:
            return {
                icon: CircleAlert,
                tone: 'danger',
                title: t('Havola yaroqsiz'),
                description: t(
                    "Havola noto'g'ri yoki eskirgan. Tasdiqlanmagan obunalar 30 kundan keyin o'chiriladi — sayt pastidagi forma orqali qayta obuna bo'ling.",
                ),
            };
    }
});

const tones: Record<
    Copy['tone'],
    { ring: string; tile: string; glow: string }
> = {
    success: {
        ring: 'ring-emerald-100',
        tile: 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white',
        glow: 'bg-emerald-400/25',
    },
    gold: {
        ring: 'ring-gold-200',
        tile: 'bg-gradient-to-br from-gold-300 to-gold-600 text-navy-950',
        glow: 'bg-gold-400/25',
    },
    muted: {
        ring: 'ring-slate-200',
        tile: 'bg-gradient-to-br from-navy-700 to-navy-950 text-white',
        glow: 'bg-navy-400/20',
    },
    danger: {
        ring: 'ring-red-100',
        tile: 'bg-gradient-to-br from-red-400 to-red-600 text-white',
        glow: 'bg-red-400/20',
    },
};

const tone = computed(() => tones[copy.value.tone]);

const links = computed(() => [
    { title: t('Bosh sahifa'), href: home.url(), icon: House },
    { title: t('Maqolalar'), href: articlesIndex.url(), icon: BookOpen },
    { title: t('Jurnal sonlari'), href: issuesIndex.url(), icon: Library },
]);

function unsubscribe(): void {
    if (props.token) {
        form.post(unsubscribeStore.url(props.token));
    }
}
</script>

<template>
    <Head :title="copy.title" />

    <section class="relative isolate overflow-hidden bg-page">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-navy-gradient"
            aria-hidden="true"
        >
            <div class="absolute inset-0 bg-girih opacity-40" />
        </div>

        <div class="mx-auto w-full max-w-2xl px-4 pt-16 pb-20 sm:px-6 lg:pt-20">
            <div
                :class="
                    cn(
                        'relative overflow-hidden rounded-3xl border border-line bg-white px-6 py-10 text-center shadow-[0_30px_60px_-36px_rgba(0,30,60,0.55)] ring-8 sm:px-12',
                        tone.ring,
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'pointer-events-none absolute -top-16 left-1/2 size-56 -translate-x-1/2 rounded-full blur-3xl',
                            tone.glow,
                        )
                    "
                    aria-hidden="true"
                />

                <span
                    :class="
                        cn(
                            'relative mx-auto flex size-20 items-center justify-center rounded-2xl shadow-[0_16px_30px_-14px_rgba(0,30,60,0.6)]',
                            tone.tile,
                        )
                    "
                >
                    <component :is="copy.icon" class="size-9" />
                </span>

                <p
                    class="relative mt-6 text-[11px] font-bold tracking-[0.18em] text-gold-700 uppercase"
                >
                    {{ t('Yangiliklarga obuna') }}
                </p>
                <h1
                    class="relative mt-2 font-serif text-3xl font-bold text-navy-950"
                >
                    {{ copy.title }}
                </h1>
                <p
                    class="relative mx-auto mt-3 max-w-md text-[15px] leading-7 text-navy-600"
                >
                    {{ copy.description }}
                </p>

                <div
                    v-if="state === 'ask'"
                    class="relative mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex h-11 items-center gap-2 rounded-xl bg-navy-950 px-6 text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(0,30,60,0.9)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[0_16px_28px_-14px_rgba(220,38,38,0.8)] focus-visible:ring-4 focus-visible:ring-red-200 focus-visible:outline-none disabled:opacity-60"
                        @click="unsubscribe"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <MailMinus v-else class="size-4" />
                        {{ t('Ha, obunadan chiqish') }}
                    </button>
                    <Link
                        :href="home()"
                        class="inline-flex h-11 items-center rounded-xl border border-line px-6 text-sm font-semibold text-navy-800 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"
                    >
                        {{ t('Obunada qolaman') }}
                    </Link>
                </div>

                <div v-else class="relative mt-8 grid gap-3 sm:grid-cols-3">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="group flex items-center justify-center gap-2 rounded-xl border border-line bg-[#f8fafd] px-4 py-3 text-sm font-semibold text-navy-800 transition-all duration-300 hover:-translate-y-0.5 hover:border-gold-300 hover:bg-white hover:text-brand-700 hover:shadow-[0_14px_28px_-18px_rgba(0,36,66,0.5)]"
                    >
                        <component
                            :is="link.icon"
                            class="size-4 text-gold-600 transition-transform duration-300 group-hover:scale-110"
                        />
                        {{ link.title }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

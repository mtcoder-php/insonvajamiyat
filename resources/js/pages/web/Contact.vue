<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    LoaderCircle,
    Mail,
    MapPin,
    MessageSquareText,
    Phone,
    Send,
} from '@lucide/vue';
import { computed } from 'vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import WebPageHeader from '@/components/web/WebPageHeader.vue';
import ContentSection from '@/components/web/content/ContentSection.vue';
import { t } from '@/lib/i18n';
import { socialLabel } from '@/lib/social';
import { cn } from '@/lib/utils';
import { send } from '@/routes/contact';
import type { ContactPageProps, JournalSocialNetwork } from '@/types';

/**
 * "Aloqa": tahririyat rekvizitlari (Tizim sozlamalari → Aloqa), tahririyat matni va
 * xabar yuborish formasi (tahririyat pochtasiga, navbat orqali).
 */
defineProps<ContactPageProps>();

const journal = computed(() => usePage().props.journal);

const socials = computed(
    () =>
        Object.entries(journal.value.socials).filter(
            ([, url]) => typeof url === 'string' && url !== '',
        ) as [JournalSocialNetwork, string][],
);

const cards = computed(() => {
    const contact = journal.value.contact;

    return [
        contact.email && {
            icon: Mail,
            label: t('Elektron pochta'),
            value: contact.email,
            href: `mailto:${contact.email}`,
            external: false,
        },
        contact.phone && {
            icon: Phone,
            label: t('Telefon'),
            value: contact.phone,
            href: `tel:${contact.phone.replace(/[^\d+]/g, '')}`,
            external: false,
        },
        contact.address && {
            icon: MapPin,
            label: t('Manzil'),
            value: contact.address,
            href: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(contact.address)}`,
            external: true,
        },
    ].filter(Boolean) as {
        icon: typeof Mail;
        label: string;
        value: string;
        href: string;
        external: boolean;
    }[];
});

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
    // Spam botlar uchun yashirin maydon (odam ko'rmaydi va to'ldirmaydi)
    website: '',
});

function submit(): void {
    form.post(send.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const field =
    'h-11 w-full rounded-lg border border-line bg-white px-3.5 text-sm text-navy-900 shadow-[0_1px_2px_rgba(0,30,60,0.04)] outline-none transition placeholder:text-navy-300 hover:border-navy-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100';
</script>

<template>
    <Head :title="page.title" />

    <WebPageHeader
        :title="page.title"
        :description="page.description"
        :crumbs="[{ title: page.title }]"
    />

    <div class="bg-[#f6f8fb]">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-8 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:px-0 lg:py-12"
        >
            <div class="flex min-w-0 flex-col gap-6">
                <!-- Aloqa ma'lumotlari -->
                <ul class="grid gap-3">
                    <li v-for="card in cards" :key="card.label">
                        <a
                            :href="card.href"
                            :target="card.external ? '_blank' : undefined"
                            :rel="
                                card.external
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                            class="group flex items-center gap-4 rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_18px_40px_-28px_rgba(0,36,66,0.45)]"
                        >
                            <span
                                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition-all duration-300 group-hover:-rotate-6 group-hover:bg-navy-900 group-hover:text-gold-300"
                            >
                                <component :is="card.icon" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs text-navy-500">{{
                                    card.label
                                }}</span>
                                <span
                                    class="block text-[15px] font-semibold break-words text-navy-950 transition-colors group-hover:text-brand-700"
                                    >{{ card.value }}</span
                                >
                            </span>
                            <ArrowUpRight
                                class="size-4 shrink-0 -translate-x-1 text-brand-600 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100"
                            />
                        </a>
                    </li>
                </ul>

                <div v-if="socials.length" class="flex flex-wrap gap-2">
                    <a
                        v-for="[network, url] in socials"
                        :key="network"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="socialLabel(network)"
                        class="flex size-11 items-center justify-center rounded-xl border border-line bg-white text-navy-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-navy-900 hover:bg-navy-900 hover:text-gold-300 hover:shadow-lg"
                    >
                        <SocialIcon :network="network" />
                    </a>
                </div>

                <ContentSection
                    v-for="(section, index) in page.sections"
                    :key="index"
                    :heading="section.heading"
                    :body="section.body"
                />
            </div>

            <!-- Xabar yuborish formasi -->
            <section
                class="h-fit rounded-2xl border border-line bg-white p-6 shadow-[0_24px_48px_-36px_rgba(0,30,60,0.55)] sm:p-8 lg:sticky lg:top-24"
                aria-labelledby="contact-form-title"
            >
                <h2
                    id="contact-form-title"
                    class="flex items-center gap-3 font-serif text-xl font-bold text-navy-950 sm:text-2xl"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-navy-900 text-gold-300"
                    >
                        <MessageSquareText class="size-5" />
                    </span>
                    {{ t('Tahririyatga yozing') }}
                </h2>
                <p class="mt-2 text-sm text-navy-500">
                    {{
                        t(
                            'Xabaringiz tahririyat pochtasiga boradi, javob emailingizga keladi.',
                        )
                    }}
                </p>

                <form class="mt-6 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid content-start gap-1.5">
                            <label
                                for="contact-name"
                                class="text-[13px] font-semibold text-navy-700"
                                >{{ t('Ismingiz') }}
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                id="contact-name"
                                v-model="form.name"
                                required
                                maxlength="100"
                                autocomplete="name"
                                :aria-invalid="!!form.errors.name"
                                :class="
                                    cn(
                                        field,
                                        form.errors.name && 'border-red-400',
                                    )
                                "
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid content-start gap-1.5">
                            <label
                                for="contact-email"
                                class="text-[13px] font-semibold text-navy-700"
                                >{{ t('Elektron pochta') }}
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                id="contact-email"
                                v-model="form.email"
                                type="email"
                                required
                                maxlength="255"
                                autocomplete="email"
                                placeholder="email@example.com"
                                :aria-invalid="!!form.errors.email"
                                :class="
                                    cn(
                                        field,
                                        form.errors.email && 'border-red-400',
                                    )
                                "
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label
                            for="contact-subject"
                            class="text-[13px] font-semibold text-navy-700"
                            >{{ t('Mavzu') }}</label
                        >
                        <input
                            id="contact-subject"
                            v-model="form.subject"
                            maxlength="150"
                            :class="field"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <label
                            for="contact-message"
                            class="text-[13px] font-semibold text-navy-700"
                            >{{ t('Xabar') }}
                            <span class="text-red-500">*</span></label
                        >
                        <textarea
                            id="contact-message"
                            v-model="form.message"
                            required
                            rows="6"
                            maxlength="5000"
                            :aria-invalid="!!form.errors.message"
                            :class="
                                cn(
                                    field,
                                    'h-auto min-h-36 py-3 leading-relaxed',
                                    form.errors.message && 'border-red-400',
                                )
                            "
                        />
                        <p
                            v-if="form.errors.message"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <!-- Yashirin maydon: faqat botlar to'ldiradi -->
                    <div class="hidden" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input
                            id="contact-website"
                            v-model="form.website"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="group inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-brand-600 px-6 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(0,108,246,0.9)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-500 hover:shadow-[0_16px_30px_-12px_rgba(0,108,246,0.9)] disabled:pointer-events-none disabled:opacity-60"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <Send
                            v-else
                            class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        />
                        {{ t('Xabarni yuborish') }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>

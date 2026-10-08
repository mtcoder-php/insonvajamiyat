<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpenText, Cpu, Globe, Handshake } from '@lucide/vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import { home } from '@/routes';
import { computed } from 'vue';
import { t } from '@/lib/i18n';

/**
 * Login / ro'yxatdan o'tish / parol tiklash sahifalari layouti
 * (dizayn: register_login.png).
 *
 * Chapda — brend paneli (faqat lg va undan katta ekranlarda),
 * o'ngda — forma kartasi. `wide` — keng forma (ro'yxatdan o'tish).
 *
 * title/description — sahifaning defineOptions({ layout }) qiymatlari (o'zbekcha kalit),
 * shu yerda tarjima qilinadi.
 */
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        wide?: boolean;
    }>(),
    {
        title: '',
        description: '',
        wide: false,
    },
);

const features = computed(() => [
    { icon: BookOpenText, text: t('Ilmiy maqolalar nashri') },
    { icon: Globe, text: t('Ochiq ilmiy platforma') },
    { icon: Handshake, text: t('Xalqaro hamkorlik') },
    { icon: Cpu, text: t('Zamonaviy texnologiyalar') },
]);

const year = new Date().getFullYear();
</script>

<template>
    <div
        class="grid min-h-dvh bg-page lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]"
    >
        <aside
            class="relative hidden overflow-hidden bg-navy-gradient text-white lg:flex lg:flex-col lg:p-12 xl:p-16"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-girih opacity-[0.06]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -bottom-48 -left-48 size-[32rem] rounded-full bg-gold-500/15 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -top-40 -right-40 size-[26rem] rounded-full bg-brand-500/20 blur-3xl"
                aria-hidden="true"
            />

            <div class="relative z-10">
                <Link :href="home()" class="inline-flex">
                    <BrandLogo tone="light" size="lg" />
                </Link>
            </div>

            <div class="relative z-10 mt-16 xl:mt-24">
                <blockquote
                    class="max-w-md font-serif text-3xl leading-snug text-white/95 italic xl:text-4xl"
                >
                    “{{
                        t(
                            'Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.',
                        )
                    }}”
                </blockquote>
                <div class="mt-6 gold-rule w-48" />
            </div>

            <div class="flex-1" />

            <ul class="relative z-10 space-y-4">
                <li
                    v-for="feature in features"
                    :key="feature.icon.name"
                    class="flex items-center gap-4"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/5"
                    >
                        <component
                            :is="feature.icon"
                            class="size-5 text-gold-300"
                        />
                    </span>
                    <span class="text-sm font-medium text-white/90">
                        {{ feature.text }}
                    </span>
                </li>
            </ul>

            <p class="relative z-10 mt-12 text-xs text-white/55">
                ©
                {{
                    t(
                        ':year «:name» ilmiy jurnali. Barcha huquqlar himoyalangan.',
                        { year, name: $page.props.journal.name },
                    )
                }}
            </p>
        </aside>

        <main
            class="flex items-center justify-center px-4 py-10 sm:px-6 lg:px-12"
        >
            <div :class="['w-full', wide ? 'max-w-xl' : 'max-w-md']">
                <Link :href="home()" class="mb-8 flex justify-center lg:hidden">
                    <BrandLogo size="md" />
                </Link>

                <div class="surface-card p-6 sm:p-8">
                    <div v-if="title || description" class="mb-7 space-y-1.5">
                        <h1
                            v-if="title"
                            class="text-2xl leading-tight md:text-[1.75rem]"
                        >
                            {{ t(title) }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t(description) }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

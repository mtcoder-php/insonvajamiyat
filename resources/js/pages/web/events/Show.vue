<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Clock,
    ExternalLink,
    History,
    MapPin,
} from '@lucide/vue';
import { computed } from 'vue';
import WebHero from '@/components/web/WebHero.vue';
import { eventDateParts, formatDateLong } from '@/lib/format';
import { index } from '@/routes/events';
import type { EventDetail, EventItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Tadbir sahifasi: sana, vaqt, joy, tavsif va ro'yxatdan o'tish havolasi.
 */
const props = defineProps<{
    /** Sarlavha fon rasmi (config journal.heroes) */
    hero: string | null;
    event: EventDetail;
    others: EventItem[];
}>();

function time(value: string): string {
    const date = new Date(value);

    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

const date = computed(() => {
    const start = formatDateLong(props.event.startsAt);
    const end = props.event.endsAt ? formatDateLong(props.event.endsAt) : null;

    return end && end !== start ? `${start} — ${end}` : start;
});
</script>

<template>
    <Head :title="event.title" />

    <WebHero
        :image="hero"
        :title="event.title"
        :crumbs="[
            { title: t('Tadbirlar'), href: index() },
            { title: t('Tadbir') },
        ]"
    >
        <div
            class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-navy-600"
        >
            <span class="inline-flex items-center gap-1.5">
                <CalendarDays class="size-4 text-brand-600" />
                {{ date }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <Clock class="size-4 text-brand-600" />
                {{ time(event.startsAt) }}
            </span>
            <span
                v-if="event.location"
                class="inline-flex items-center gap-1.5"
            >
                <MapPin class="size-4 text-brand-600" />
                {{ event.location }}
            </span>
            <span
                v-if="event.isPast"
                class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-navy-600"
            >
                <History class="size-3.5" />
                {{ t("Tadbir o'tib ketgan") }}
            </span>
        </div>
    </WebHero>

    <div class="bg-white">
        <div
            class="mx-auto grid w-full max-w-[1700px] gap-10 px-4 py-10 sm:px-6 lg:w-[90%] lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-0 lg:py-12"
        >
            <article class="min-w-0">
                <img
                    v-if="event.imageUrl"
                    :src="event.imageUrl"
                    :alt="event.title"
                    class="mb-8 aspect-[16/8] w-full rounded-xl object-cover shadow-md"
                />
                <div class="flex items-start gap-6">
                    <div
                        class="hidden w-24 shrink-0 flex-col items-center rounded-xl border-2 border-navy-800 bg-white py-3 text-navy-900 shadow-sm sm:flex"
                    >
                        <span
                            class="font-serif text-4xl leading-none font-bold"
                            >{{ eventDateParts(event.startsAt).day }}</span
                        >
                        <span
                            class="mt-1.5 text-xs font-semibold tracking-wide uppercase"
                        >
                            {{ eventDateParts(event.startsAt).month }}
                        </span>
                        <span class="text-xs opacity-70">{{
                            eventDateParts(event.startsAt).year
                        }}</span>
                    </div>
                    <!-- Server tomonda tozalangan HTML (App\Support\Html\RichText) -->
                    <div
                        v-if="event.description"
                        class="web-prose max-w-3xl min-w-0 flex-1"
                        v-html="event.description"
                    />
                    <p v-else class="font-serif text-[17px] text-navy-500">
                        {{
                            t(
                                "Tadbir haqida batafsil ma'lumot tez orada e'lon qilinadi.",
                            )
                        }}
                    </p>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a
                        v-if="event.registrationUrl && !event.isPast"
                        :href="event.registrationUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-7 text-sm font-semibold text-white shadow-md shadow-navy-900/20 transition-all hover:-translate-y-0.5 hover:bg-navy-800 hover:shadow-lg"
                    >
                        {{ t("Ro'yxatdan o'tish") }}
                        <ExternalLink class="size-4" />
                    </a>
                    <Link
                        :href="index()"
                        class="group inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-600"
                    >
                        <ArrowLeft
                            class="size-4 transition-transform group-hover:-translate-x-1"
                        />
                        {{ t('Barcha tadbirlar') }}
                    </Link>
                </div>
            </article>

            <aside
                v-if="others.length"
                class="lg:sticky lg:top-6 lg:self-start"
            >
                <div
                    class="rounded-xl border border-[#ebe8e1] bg-[#f8f7f4] p-5"
                >
                    <h2 class="font-serif text-lg font-bold text-navy-900">
                        {{ t('Yaqinlashayotgan tadbirlar') }}
                    </h2>
                    <ul class="mt-3 divide-y divide-[#ece8df]">
                        <li v-for="item in others" :key="item.id">
                            <Link
                                :href="item.url"
                                class="group flex gap-3 py-3"
                            >
                                <span
                                    class="flex size-11 shrink-0 items-center justify-center rounded-md border-2 border-navy-800 bg-white font-serif text-lg font-bold text-navy-900 transition-colors group-hover:bg-navy-900 group-hover:text-white"
                                >
                                    {{ eventDateParts(item.startsAt).day }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-navy-500">
                                        {{
                                            eventDateParts(item.startsAt).month
                                        }}
                                        {{ eventDateParts(item.startsAt).year }}
                                    </span>
                                    <span
                                        class="block text-sm leading-snug text-navy-800 transition-colors group-hover:text-brand-700"
                                    >
                                        {{ item.title }}
                                    </span>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</template>

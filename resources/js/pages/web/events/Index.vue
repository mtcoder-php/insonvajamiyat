<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, Clock, History, MapPin } from '@lucide/vue';
import WebPageHeader from '@/components/web/WebPageHeader.vue';
import { eventDateParts, formatDate } from '@/lib/format';
import type { EventItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Tadbirlar (/events): yaqinlashayotganlar va o'tgan tadbirlar.
 */
defineProps<{
    upcoming: EventItem[];
    past: EventItem[];
}>();

function time(value: string): string {
    const date = new Date(value);

    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}
</script>

<template>
    <Head :title="t('Tadbirlar')" />

    <WebPageHeader
        :title="t('Tadbirlar')"
        :description="
            t(
                'Konferensiyalar, ilmiy seminarlar, forumlar va mualliflar uchun master-klasslar.',
            )
        "
        :crumbs="[{ title: t('Tadbirlar') }]"
    />

    <div class="bg-white">
        <div
            class="mx-auto w-full max-w-[1700px] space-y-12 px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-12"
        >
            <section>
                <h2 class="font-serif text-2xl font-bold text-navy-950">
                    {{ t('Yaqinlashayotgan tadbirlar') }}
                </h2>

                <div
                    v-if="upcoming.length"
                    class="mt-6 grid gap-5 lg:grid-cols-2"
                >
                    <Link
                        v-for="event in upcoming"
                        :key="event.id"
                        :href="event.url"
                        class="group flex gap-5 rounded-xl border border-[#ebe8e1] bg-[#f8f7f4] p-5 transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-[0_18px_36px_-18px_rgba(0,30,60,0.35)]"
                    >
                        <div
                            class="flex w-20 shrink-0 flex-col items-center justify-center rounded-lg border-2 border-navy-800 bg-white py-2 text-navy-900 transition-colors duration-300 group-hover:bg-navy-900 group-hover:text-white"
                        >
                            <span
                                class="font-serif text-3xl leading-none font-bold"
                                >{{ eventDateParts(event.startsAt).day }}</span
                            >
                            <span
                                class="mt-1 text-[11px] font-semibold tracking-wide uppercase"
                            >
                                {{
                                    eventDateParts(event.startsAt).month.slice(
                                        0,
                                        3,
                                    )
                                }}
                            </span>
                            <span class="text-[11px] opacity-70">{{
                                eventDateParts(event.startsAt).year
                            }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3
                                class="font-serif text-lg leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                            >
                                {{ event.title }}
                            </h3>
                            <p
                                class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-navy-600"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <Clock class="size-4 text-navy-400" />
                                    {{ time(event.startsAt) }}
                                </span>
                                <span
                                    v-if="event.location"
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <MapPin class="size-4 text-navy-400" />
                                    {{ event.location }}
                                </span>
                            </p>
                            <span
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700"
                            >
                                {{ t('Batafsil') }}
                                <ArrowRight
                                    class="size-4 transition-transform group-hover:translate-x-1"
                                />
                            </span>
                        </div>
                    </Link>
                </div>
                <p v-else class="mt-6 flex items-center gap-2 text-navy-500">
                    <CalendarDays class="size-5 text-navy-300" />
                    {{ t('Yaqin kunlarda tadbirlar rejalashtirilmagan.') }}
                </p>
            </section>

            <section v-if="past.length">
                <h2
                    class="flex items-center gap-2 font-serif text-2xl font-bold text-navy-950"
                >
                    <History class="size-6 text-navy-400" />
                    {{ t("O'tgan tadbirlar") }}
                </h2>
                <ul
                    class="mt-5 divide-y divide-[#ece8df] rounded-xl border border-[#ebe8e1]"
                >
                    <li v-for="event in past" :key="event.id">
                        <Link
                            :href="event.url"
                            class="group flex flex-wrap items-baseline gap-x-6 gap-y-1 px-5 py-4 transition-colors hover:bg-[#f8f7f4]"
                        >
                            <time
                                class="w-24 shrink-0 text-sm text-navy-400 tabular-nums"
                                >{{ formatDate(event.startsAt) }}</time
                            >
                            <span
                                class="min-w-0 flex-1 text-[15px] text-navy-800 transition-colors group-hover:text-brand-700"
                            >
                                {{ event.title }}
                            </span>
                            <span
                                v-if="event.location"
                                class="text-sm text-navy-400"
                                >{{ event.location }}</span
                            >
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>

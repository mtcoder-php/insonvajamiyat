<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { eventDateParts } from '@/lib/format';
import { index as eventsIndex } from '@/routes/events';
import type { EventItem } from '@/types';

/**
 * "Tadbirlar" (home.png): kun raqami ramkada, oy va yil, nomi, joyi —
 * har biri tadbir sahifasiga havola.
 */
defineProps<{ events: EventItem[] }>();
</script>

<template>
    <HomeCard
        title="Tadbirlar"
        :href="eventsIndex()"
        link-text="Barcha tadbirlar"
    >
        <ul v-if="events.length" class="-mx-2 divide-y divide-[#ece8df]">
            <li v-for="event in events" :key="event.id">
                <Link
                    :href="event.url"
                    class="group flex gap-4 rounded-lg px-2 py-3 transition-colors hover:bg-white"
                >
                    <time
                        :datetime="event.startsAt"
                        class="flex size-12 shrink-0 items-center justify-center rounded-md border-2 border-navy-800 bg-white font-serif text-xl font-bold text-navy-900 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-navy-900 group-hover:text-white group-hover:shadow-md"
                    >
                        {{ eventDateParts(event.startsAt).day }}
                    </time>
                    <div class="w-20 shrink-0 pt-0.5 text-xs text-navy-600">
                        {{ eventDateParts(event.startsAt).month }}
                        {{ eventDateParts(event.startsAt).year }}
                    </div>
                    <div class="min-w-0 pt-0.5">
                        <p
                            class="text-sm leading-snug text-navy-900 transition-colors group-hover:text-brand-700"
                        >
                            {{ event.title }}
                        </p>
                        <p
                            v-if="event.location"
                            class="mt-1 inline-flex items-center gap-1 text-xs text-navy-400"
                        >
                            <MapPin class="size-3" />
                            {{ event.location }}
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
        <div
            v-else
            class="flex flex-col items-center gap-2 py-8 text-center text-sm text-navy-500"
        >
            <CalendarDays class="size-7 text-navy-300" />
            Yaqin kunlarda tadbirlar rejalashtirilmagan.
        </div>
    </HomeCard>
</template>

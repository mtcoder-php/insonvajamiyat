<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { MONTHS_LONG } from '@/lib/format';
import type { EventItem } from '@/types';

/**
 * "Tadbirlar" (home.png): kun raqami ramkada, oy va yil, nomi, joyi.
 */
defineProps<{ events: EventItem[] }>();

function parts(value: string): { day: number; month: string; year: number } {
    const date = new Date(value);

    return {
        day: date.getDate(),
        month:
            MONTHS_LONG[date.getMonth()].charAt(0).toUpperCase() +
            MONTHS_LONG[date.getMonth()].slice(1),
        year: date.getFullYear(),
    };
}
</script>

<template>
    <HomeCard title="Tadbirlar">
        <ul v-if="events.length" class="divide-y divide-[#e8e4db]">
            <li
                v-for="event in events"
                :key="event.id"
                class="group flex gap-4 py-3 first:pt-0 last:pb-0"
            >
                <time
                    :datetime="event.startsAt"
                    class="flex size-12 shrink-0 items-center justify-center rounded-md border-2 border-navy-800 bg-white font-serif text-xl font-bold text-navy-900 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-navy-900 group-hover:text-white group-hover:shadow-md"
                >
                    {{ parts(event.startsAt).day }}
                </time>
                <div class="w-20 shrink-0 pt-0.5 text-xs text-navy-600">
                    {{ parts(event.startsAt).month }}
                    {{ parts(event.startsAt).year }}
                </div>
                <div class="min-w-0 pt-0.5">
                    <p
                        class="text-sm leading-snug text-navy-900 transition-colors group-hover:text-brand-700"
                    >
                        <a
                            v-if="event.registrationUrl"
                            :href="event.registrationUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ event.title }}
                        </a>
                        <template v-else>{{ event.title }}</template>
                    </p>
                    <p
                        v-if="event.location"
                        class="mt-0.5 text-xs text-navy-400"
                    >
                        {{ event.location }}
                    </p>
                </div>
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

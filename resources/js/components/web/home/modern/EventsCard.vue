<script setup lang="ts">
import { CalendarDays, ExternalLink, MapPin } from '@lucide/vue';
import { dateParts } from '@/lib/format';
import type { EventItem } from '@/types';

/**
 * "Tadbirlar va konferensiyalar" — yaqinlashayotgan tadbirlar.
 */
withDefaults(
    defineProps<{
        events: EventItem[];
        title?: string;
    }>(),
    { title: 'Tadbirlar va konferensiyalar' },
);
</script>

<template>
    <section class="flex flex-col">
        <h2
            class="mb-4 flex items-center gap-2 font-serif text-lg font-semibold text-navy-950 sm:text-xl"
        >
            <CalendarDays class="size-5 text-brand-600" />
            {{ title }}
        </h2>

        <div class="flex flex-1 flex-col surface-card p-2">
            <ul v-if="events.length" class="divide-y divide-line">
                <li
                    v-for="event in events"
                    :key="event.id"
                    class="flex gap-3 p-3"
                >
                    <time
                        :datetime="event.startsAt"
                        class="flex size-14 shrink-0 flex-col items-center justify-center rounded-lg border border-line bg-surface-muted leading-none"
                    >
                        <span
                            class="font-serif text-xl font-semibold text-navy-950"
                        >
                            {{ dateParts(event.startsAt).day }}
                        </span>
                        <span
                            class="mt-1 text-[10px] font-semibold tracking-wider text-brand-700 uppercase"
                        >
                            {{ dateParts(event.startsAt).month }}
                        </span>
                    </time>
                    <div class="min-w-0">
                        <h3
                            class="text-sm leading-snug font-semibold text-navy-900"
                        >
                            <a
                                v-if="event.registrationUrl"
                                :href="event.registrationUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-start gap-1 hover:text-brand-700"
                            >
                                {{ event.title }}
                                <ExternalLink class="mt-0.5 size-3 shrink-0" />
                            </a>
                            <template v-else>{{ event.title }}</template>
                        </h3>
                        <p
                            v-if="event.location"
                            class="mt-1 flex items-center gap-1 text-xs text-navy-500"
                        >
                            <MapPin class="size-3 shrink-0" />
                            {{ event.location }},
                            {{ dateParts(event.startsAt).year }}
                        </p>
                    </div>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-center text-sm text-navy-500"
            >
                <CalendarDays class="size-7 text-navy-300" />
                Yaqin kunlarda tadbirlar rejalashtirilmagan.
            </div>
        </div>
    </section>
</template>

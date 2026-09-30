<script setup lang="ts">
import { MapPin } from '@lucide/vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { dateParts, formatDateLong } from '@/lib/format';
import type { EventItem, PostItem } from '@/types';

/**
 * Yangiliklar va yaqinlashayotgan tadbirlar.
 */
const props = defineProps<{
    news: PostItem[];
    events: EventItem[];
}>();

const visible = props.news.length > 0 || props.events.length > 0;
</script>

<template>
    <section v-if="visible" class="bg-surface-muted py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Jurnal hayoti"
                title="Yangiliklar va tadbirlar"
            />

            <div class="grid items-start gap-8 lg:grid-cols-[1.4fr_1fr]">
                <div
                    v-if="news.length"
                    class="rounded-xl border border-line bg-white p-6 sm:p-8"
                >
                    <h3
                        class="font-sans text-xs font-semibold tracking-[0.14em] text-navy-500 uppercase"
                    >
                        Yangiliklar
                    </h3>
                    <ul class="mt-2 divide-y divide-line">
                        <li v-for="item in news" :key="item.id" class="py-4">
                            <time
                                v-if="item.publishedAt"
                                :datetime="item.publishedAt"
                                class="text-xs text-navy-400"
                            >
                                {{ formatDateLong(item.publishedAt) }}
                            </time>
                            <p
                                class="mt-1 font-serif text-base leading-snug font-semibold text-navy-950"
                            >
                                {{ item.title }}
                            </p>
                            <p
                                v-if="item.excerpt"
                                class="mt-1 line-clamp-2 text-sm text-navy-600"
                            >
                                {{ item.excerpt }}
                            </p>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="events.length"
                    class="rounded-xl border border-line bg-white p-6 sm:p-8"
                >
                    <h3
                        class="font-sans text-xs font-semibold tracking-[0.14em] text-navy-500 uppercase"
                    >
                        Yaqinlashayotgan tadbirlar
                    </h3>
                    <ul class="mt-4 space-y-5">
                        <li
                            v-for="event in events"
                            :key="event.id"
                            class="flex gap-4"
                        >
                            <time
                                :datetime="event.startsAt"
                                class="flex w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-navy-900 py-2 text-white"
                            >
                                <span
                                    class="font-serif text-2xl leading-none font-semibold"
                                >
                                    {{ dateParts(event.startsAt).day }}
                                </span>
                                <span
                                    class="mt-1 text-[10px] font-semibold tracking-widest text-gold-300 uppercase"
                                >
                                    {{ dateParts(event.startsAt).month }}
                                </span>
                            </time>
                            <div class="min-w-0">
                                <p
                                    class="font-serif text-base leading-snug font-semibold text-navy-950"
                                >
                                    <a
                                        v-if="event.registrationUrl"
                                        :href="event.registrationUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="hover:text-brand-700"
                                    >
                                        {{ event.title }}
                                    </a>
                                    <template v-else>{{
                                        event.title
                                    }}</template>
                                </p>
                                <p
                                    v-if="event.location"
                                    class="mt-1 flex items-center gap-1.5 text-xs text-navy-500"
                                >
                                    <MapPin class="size-3.5" />
                                    {{ event.location }},
                                    {{ dateParts(event.startsAt).year }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

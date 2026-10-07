<script setup lang="ts">
import {
    Clock,
    ExternalLink,
    EyeOff,
    Link2,
    MapPin,
    PenLine,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { inputClass } from '@/lib/formStyles';
import {
    eventDateParts,
    formatDate,
    formatNumber,
    formatTime,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    SettingsEvent,
    SettingsFilters,
    SettingsPageProps,
} from '@/types';
import DeleteDialog from './DeleteDialog.vue';
import EventDialog from './EventDialog.vue';
import { useSettingsQuery } from './useSettingsQuery';

/** Tadbirlar: kelgusi / o'tgan filtri, qidiruv, sahifalash */
const props = defineProps<{
    events: NonNullable<SettingsPageProps['events']>;
    filters: SettingsFilters;
    storeUrl: string;
    indexUrl: string;
}>();

const { search, apply } = useSettingsQuery(
    props.indexUrl,
    'events',
    () => props.filters,
);

const editing = ref<SettingsEvent | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsEvent | null>(null);
const deleteOpen = ref(false);

function open(event: SettingsEvent | null): void {
    editing.value = event;
    formOpen.value = true;
}

function remove(event: SettingsEvent): void {
    removing.value = event;
    deleteOpen.value = true;
}

const chips = [
    { value: '', label: 'Hammasi', count: 'all' },
    { value: 'upcoming', label: 'Kelgusi', count: 'upcoming' },
    { value: 'past', label: "O'tgan", count: null },
] as const;

function range(event: SettingsEvent): string {
    const start = formatDate(event.startsAt);
    const time = formatTime(event.startsAt);

    if (!event.endsAt) {
        return `${start}, ${time}`;
    }

    const end = formatDate(event.endsAt);

    return end === start
        ? `${start}, ${time}–${formatTime(event.endsAt)}`
        : `${start} — ${end}`;
}
</script>

<template>
    <section>
        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    Tadbirlar
                </h2>
                <p class="text-xs text-navy-500">
                    Konferensiya, seminar, forum va davra suhbatlari
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="open(null)"
            >
                <Plus class="size-4" /> Tadbir qo'shish
            </button>
        </header>

        <div
            class="mb-4 flex flex-col gap-3 rounded-xl border border-line bg-white p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="chip in chips"
                    :key="chip.value"
                    type="button"
                    :class="
                        cn(
                            'inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-[13px] font-semibold transition-all',
                            filters.when === chip.value
                                ? 'bg-navy-900 text-white shadow-sm'
                                : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                        )
                    "
                    @click="apply({ when: chip.value })"
                >
                    {{ chip.label }}
                    <span
                        v-if="chip.count"
                        :class="
                            cn(
                                'rounded-full px-1.5 text-[11px] tabular-nums',
                                filters.when === chip.value
                                    ? 'bg-white/20'
                                    : 'bg-white text-navy-500',
                            )
                        "
                        >{{ formatNumber(events.counts[chip.count]) }}</span
                    >
                </button>
            </div>
            <label class="relative sm:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Nomi yoki joyi bo'yicha…"
                    :class="cn(inputClass, 'pl-9')"
                />
            </label>
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            <article
                v-for="event in events.data"
                :key="event.id"
                :class="
                    cn(
                        'group flex gap-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]',
                        event.isPast && 'bg-[#fbfcfe]',
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'flex w-16 shrink-0 flex-col items-center justify-center self-start rounded-xl border-2 py-2 transition-colors',
                            event.isPast
                                ? 'border-line text-navy-400'
                                : 'border-navy-800 text-navy-900 group-hover:border-brand-600 group-hover:text-brand-700',
                        )
                    "
                >
                    <span
                        class="font-serif text-2xl leading-none font-bold tabular-nums"
                        >{{ eventDateParts(event.startsAt).day }}</span
                    >
                    <span class="mt-1 text-[10px] font-semibold uppercase">{{
                        eventDateParts(event.startsAt).month.slice(0, 3)
                    }}</span>
                    <span class="text-[10px] tabular-nums opacity-70">{{
                        eventDateParts(event.startsAt).year
                    }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="mb-1 flex flex-wrap items-center gap-1.5">
                        <span
                            v-if="event.isPast"
                            class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                            >O'tgan</span
                        >
                        <span
                            v-else
                            class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
                            >Kelgusi</span
                        >
                        <span
                            v-if="!event.isPublished"
                            class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                        >
                            <EyeOff class="size-3" /> Yashirin
                        </span>
                    </div>
                    <h3
                        class="line-clamp-2 text-[14px] leading-snug font-bold [overflow-wrap:anywhere] text-navy-950 transition-colors group-hover:text-brand-700"
                    >
                        {{ event.title }}
                    </h3>
                    <div
                        class="mt-2 grid grid-cols-1 gap-1 text-[12px] text-navy-500"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Clock class="size-3.5 shrink-0" />
                            {{ range(event) }}
                        </span>
                        <span
                            v-if="event.location"
                            class="inline-flex min-w-0 items-center gap-1.5"
                        >
                            <MapPin class="size-3.5 shrink-0" />
                            <span class="truncate">{{ event.location }}</span>
                        </span>
                        <a
                            v-if="event.registrationUrl"
                            :href="event.registrationUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex min-w-0 items-center gap-1.5 text-brand-700 hover:text-brand-600"
                        >
                            <Link2 class="size-3.5 shrink-0" />
                            <span class="truncate">Ro'yxatdan o'tish</span>
                        </a>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col gap-1">
                    <a
                        v-if="event.url"
                        :href="event.url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                        aria-label="Saytda ko'rish"
                    >
                        <ExternalLink class="size-4" />
                    </a>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                        aria-label="Tahrirlash"
                        @click="open(event)"
                    >
                        <PenLine class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                        aria-label="O'chirish"
                        @click="remove(event)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </article>
        </div>

        <p
            v-if="!events.data.length"
            class="rounded-xl border border-dashed border-line bg-white px-4 py-10 text-center text-sm text-navy-400"
        >
            {{
                filters.q || filters.when
                    ? "Filtr bo'yicha tadbir topilmadi"
                    : "Hali tadbirlar yo'q — birinchisini qo'shing"
            }}
        </p>

        <div v-if="events.meta.lastPage > 1" class="mt-4">
            <SimplePager :meta="events.meta" @go="(page) => apply({}, page)" />
        </div>

        <EventDialog
            v-model:open="formOpen"
            :event="editing"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            title="Tadbirni o'chirish"
            :description="`«${removing?.title ?? ''}» saytdan olib tashlanadi.`"
        />
    </section>
</template>

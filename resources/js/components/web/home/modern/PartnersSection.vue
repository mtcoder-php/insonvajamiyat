<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref } from 'vue';
import type { PartnerItem } from '@/types';

/**
 * "Hamkorlarimiz" — gorizontal aylantiriladigan logotiplar qatori.
 */
defineProps<{ partners: PartnerItem[] }>();

const track = ref<HTMLElement | null>(null);

function scroll(direction: 1 | -1): void {
    track.value?.scrollBy({
        left: direction * track.value.clientWidth * 0.8,
        behavior: 'smooth',
    });
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter((word) => /^[A-ZА-ЯЎҚҒҲ]/u.test(word))
        .slice(0, 3)
        .map((word) => word[0])
        .join('');
}
</script>

<template>
    <section
        v-if="partners.length"
        class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"
    >
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2
                class="font-serif text-xl font-semibold text-navy-950 sm:text-2xl"
            >
                Hamkorlarimiz
            </h2>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="flex size-9 items-center justify-center rounded-full border border-line bg-surface text-navy-700 transition-colors hover:border-brand-300 hover:text-brand-700"
                    aria-label="Oldingi"
                    @click="scroll(-1)"
                >
                    <ChevronLeft class="size-4" />
                </button>
                <button
                    type="button"
                    class="flex size-9 items-center justify-center rounded-full border border-line bg-surface text-navy-700 transition-colors hover:border-brand-300 hover:text-brand-700"
                    aria-label="Keyingi"
                    @click="scroll(1)"
                >
                    <ChevronRight class="size-4" />
                </button>
            </div>
        </div>

        <ul
            ref="track"
            class="flex snap-x snap-mandatory scrollbar-thin gap-4 overflow-x-auto pb-2"
        >
            <li
                v-for="partner in partners"
                :key="partner.id"
                class="w-72 shrink-0 snap-start"
            >
                <component
                    :is="partner.url ? 'a' : 'div'"
                    :href="partner.url ?? undefined"
                    :target="partner.url ? '_blank' : undefined"
                    :rel="partner.url ? 'noopener noreferrer' : undefined"
                    class="flex h-24 items-center gap-4 rounded-xl border border-line bg-surface px-5 transition-colors hover:border-brand-200"
                >
                    <img
                        v-if="partner.logoUrl"
                        :src="partner.logoUrl"
                        :alt="partner.name"
                        class="h-14 w-14 shrink-0 object-contain"
                        loading="lazy"
                    />
                    <span
                        v-else
                        class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-navy-100 bg-navy-50 font-serif text-sm font-semibold text-navy-700"
                        aria-hidden="true"
                    >
                        {{ initials(partner.name) || partner.name[0] }}
                    </span>
                    <span
                        class="line-clamp-3 text-xs leading-snug text-navy-700"
                    >
                        {{ partner.name }}
                    </span>
                </component>
            </li>
        </ul>
    </section>
</template>

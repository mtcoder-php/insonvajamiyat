<script setup lang="ts">
import { Database, Handshake } from '@lucide/vue';
import { computed } from 'vue';
import PartnerCard from '@/components/web/home/PartnerCard.vue';
import type { PartnerItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Bosh sahifa pastidagi "Hamkorlar va indekslash bazalari" qatori.
 * Har bir kartada: logo "avatar" (logo yuklanmagan bo'lsa — nomdan monogramma),
 * nom, izoh va sayt manzili. Sayt kiritilgan bo'lsa butun karta havola (yangi oynada).
 *
 * Guruhda 4 tagacha element — oddiy to'r. Ko'proq bo'lsa — o'sha kartalar yonlama
 * cheksiz oqadigan lentada (marquee): sichqoncha yoki fokus kelganda to'xtaydi,
 * chetlari yumshoq so'nadi; "harakatni kamaytirish" yoqilgan bo'lsa oddiy
 * gorizontal aylantiriladigan qatorga aylanadi.
 * Ro'yxat Admin → Sozlamalar → Hamkorlar bo'limida boshqariladi.
 */
const props = defineProps<{ partners: PartnerItem[] }>();

const groups = computed(() =>
    [
        {
            key: 'indexing' as const,
            title: t('Indekslash bazalari'),
            icon: Database,
        },
        {
            key: 'partner' as const,
            title: t('Hamkor tashkilotlar'),
            icon: Handshake,
        },
    ]
        .map((group) => {
            const items = props.partners.filter((p) => p.type === group.key);

            return {
                ...group,
                items,
                flowing: items.length > MARQUEE_AFTER,
                duration: `${items.length * SECONDS_PER_CARD}s`,
            };
        })
        .filter((group) => group.items.length),
);

/** Shundan ko'p bo'lsa guruh yonlama oqib turuvchi lentaga aylanadi */
const MARQUEE_AFTER = 4;

/** Bitta kartaning lentadan o'tish vaqti (soniya) — ro'yxat uzaysa tezlik o'zgarmaydi */
const SECONDS_PER_CARD = 5;
</script>

<template>
    <section
        v-if="groups.length"
        class="border-t border-[#ebe8e1] bg-[#f9f8f6]"
        aria-labelledby="partners-title"
    >
        <div
            class="mx-auto w-full max-w-[1700px] px-4 py-10 sm:px-6 lg:w-[90%] lg:px-0 lg:py-14"
        >
            <div class="mb-7">
                <p
                    class="text-[11px] font-bold tracking-[0.18em] text-gold-700 uppercase"
                >
                    {{ t('Ishonchli manbalar') }}
                </p>
                <h2
                    id="partners-title"
                    class="mt-1 font-serif text-2xl font-bold text-navy-900"
                >
                    {{ t('Hamkorlar va indekslash bazalari') }}
                </h2>
                <div class="mt-3 gold-rule w-16" aria-hidden="true" />
            </div>

            <div class="grid grid-cols-1 gap-9">
                <div v-for="group in groups" :key="group.key">
                    <h3
                        class="mb-4 inline-flex items-center gap-2 text-[13px] font-semibold text-navy-600"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-white text-gold-600 ring-1 ring-[#e6e3dc]"
                        >
                            <component :is="group.icon" class="size-4" />
                        </span>
                        {{ group.title }}
                        <span
                            class="rounded-full bg-white px-2 py-0.5 text-[11px] text-navy-400 tabular-nums ring-1 ring-[#e6e3dc]"
                            >{{ group.items.length }}</span
                        >
                    </h3>
                    <!-- Oqib turuvchi lenta (4 tadan ko'p) -->
                    <div
                        v-if="group.flowing"
                        class="partners-marquee @container relative -my-6 overflow-hidden py-6 motion-reduce:overflow-x-auto"
                        role="region"
                        :aria-label="group.title"
                    >
                        <div
                            class="partners-marquee-track flex w-max"
                            :style="{ animationDuration: group.duration }"
                        >
                            <ul
                                v-for="copy in [false, true]"
                                :key="String(copy)"
                                :class="[
                                    'flex shrink-0 gap-4 pr-4',
                                    copy && 'motion-reduce:hidden',
                                ]"
                                :aria-hidden="copy ? 'true' : undefined"
                            >
                                <li
                                    v-for="partner in group.items"
                                    :key="partner.id"
                                    class="w-[calc(100cqw-2rem)] shrink-0 min-[480px]:w-[calc((100cqw-1rem)/2)] lg:w-[calc((100cqw-2rem)/3)] 2xl:w-[calc((100cqw-3rem)/4)]"
                                >
                                    <PartnerCard
                                        :partner="partner"
                                        :clone="copy"
                                    />
                                </li>
                            </ul>
                        </div>
                    </div>

                    <ul
                        v-else
                        class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
                    >
                        <li v-for="partner in group.items" :key="partner.id">
                            <PartnerCard :partner="partner" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

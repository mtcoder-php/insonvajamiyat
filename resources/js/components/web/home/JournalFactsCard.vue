<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { BookMarked, CalendarClock, Fingerprint, Unlock } from '@lucide/vue';
import { computed } from 'vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { t } from '@/lib/i18n';

/**
 * "Jurnal ma'lumotlari" (home.png): ISSN, e-ISSN, DOI, davriylik, Open Access.
 * Qiymatlar .env (JOURNAL_*) dan; bo'sh qatorlar ko'rsatilmaydi.
 */
const journal = computed(() => usePage().props.journal);
</script>

<template>
    <HomeCard :title="t('Jurnal ma\'lumotlari')">
        <dl class="space-y-4 text-sm">
            <div v-if="journal.issn || journal.eissn" class="flex gap-3">
                <BookMarked
                    class="mt-0.5 size-6 shrink-0 text-navy-800"
                    :stroke-width="1.5"
                />
                <div class="grid flex-1 grid-cols-2 gap-3">
                    <div v-if="journal.issn" class="flex flex-col-reverse">
                        <dd class="font-serif font-bold text-navy-950">
                            {{ journal.issn }}
                        </dd>
                        <dt class="text-xs text-navy-500">ISSN (print)</dt>
                    </div>
                    <div v-if="journal.eissn" class="flex flex-col-reverse">
                        <dd class="font-serif font-bold text-navy-950">
                            {{ journal.eissn }}
                        </dd>
                        <dt class="text-xs text-navy-500">e-ISSN</dt>
                    </div>
                </div>
            </div>
            <div v-if="journal.doiPrefix" class="flex gap-3">
                <Fingerprint
                    class="mt-0.5 size-6 shrink-0 text-navy-800"
                    :stroke-width="1.5"
                />
                <div class="flex flex-col-reverse">
                    <dd class="font-serif font-bold break-all text-navy-950">
                        {{ journal.doiPrefix }}
                    </dd>
                    <dt class="text-xs text-navy-500">DOI</dt>
                </div>
            </div>
            <div v-if="journal.frequency" class="flex gap-3">
                <CalendarClock
                    class="mt-0.5 size-6 shrink-0 text-navy-800"
                    :stroke-width="1.5"
                />
                <div class="flex flex-col-reverse">
                    <dd class="font-serif font-bold text-navy-950">
                        {{ journal.frequency }}
                    </dd>
                    <dt class="text-xs text-navy-500">
                        {{ t('Chop etish davriyligi') }}
                    </dt>
                </div>
            </div>
            <div class="flex gap-3">
                <Unlock
                    class="mt-0.5 size-6 shrink-0 text-navy-800"
                    :stroke-width="1.5"
                />
                <div class="flex flex-col-reverse">
                    <dd class="text-navy-700">
                        {{ t('Barcha maqolalar ochiq') }}
                    </dd>
                    <dt class="text-xs text-navy-500">Open Access</dt>
                </div>
            </div>
        </dl>
    </HomeCard>
</template>

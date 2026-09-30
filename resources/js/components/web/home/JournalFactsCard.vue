<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * "Jurnal ma'lumotlari" — rekvizitlar jadvali (config/journal.php).
 */
const journal = computed(() => usePage().props.journal);

const rows = computed(() =>
    [
        { label: 'ISSN (bosma)', value: journal.value.issn },
        { label: 'e-ISSN', value: journal.value.eissn },
        { label: 'DOI prefiksi', value: journal.value.doiPrefix },
        { label: 'Davriyligi', value: journal.value.frequency },
        { label: 'Nashr tillari', value: "O'zbek, rus, ingliz" },
        { label: 'Kirish', value: 'Ochiq (Open Access)' },
    ].filter((row): row is { label: string; value: string } =>
        Boolean(row.value),
    ),
);
</script>

<template>
    <section class="rounded-xl border border-line bg-white p-6">
        <h3 class="font-serif text-lg font-semibold text-navy-950">
            Jurnal ma'lumotlari
        </h3>
        <dl class="mt-4 divide-y divide-line text-sm">
            <div
                v-for="row in rows"
                :key="row.label"
                class="flex items-baseline justify-between gap-4 py-2.5"
            >
                <dt class="text-navy-500">{{ row.label }}</dt>
                <dd class="text-right font-medium text-navy-900">
                    {{ row.value }}
                </dd>
            </div>
        </dl>
    </section>
</template>

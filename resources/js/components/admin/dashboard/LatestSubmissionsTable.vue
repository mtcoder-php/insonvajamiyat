<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, FileText } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { LatestSubmission } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Yangi kelgan maqolalar" jadvali — "Ko'rish" maqolani Maqolalar bo'limida ochadi.
 */
defineProps<{ items: LatestSubmission[] }>();

const canOpen = usePermissions().can('articles.view_any');

const pill: Record<LatestSubmission['statusGroup'], string> = {
    new: 'bg-brand-50 text-brand-700 ring-brand-200',
    reviewing: 'bg-amber-50 text-amber-700 ring-amber-200',
    revision: 'bg-red-50 text-red-700 ring-red-200',
    accepted: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    published: 'bg-violet-50 text-violet-700 ring-violet-200',
    other: 'bg-navy-50 text-navy-600 ring-navy-200',
};
</script>

<template>
    <DashCard :title="t('Yangi kelgan maqolalar')">
        <div v-if="items.length" class="-mx-5 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-[13px]">
                <thead>
                    <tr
                        class="border-y border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >
                        <th class="w-12 py-2.5 pr-2 pl-5">№</th>
                        <th class="py-2.5 pr-4">{{ t('Maqola nomi') }}</th>
                        <th class="py-2.5 pr-4">{{ t('Muallif') }}</th>
                        <th class="py-2.5 pr-4">{{ t('Jurnal soni') }}</th>
                        <th class="py-2.5 pr-4">{{ t('Yuborilgan sana') }}</th>
                        <th class="py-2.5 pr-4">{{ t('Holati') }}</th>
                        <th class="py-2.5 pr-5 text-right">
                            {{ t('Amallar') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="group transition-colors hover:bg-brand-50/40"
                    >
                        <td class="py-3 pl-5 text-navy-500 tabular-nums">
                            {{ index + 1 }}
                        </td>
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-11 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-[#f3efe6] text-gold-700 ring-1 ring-black/5 transition-transform duration-300 group-hover:scale-105"
                                >
                                    <img
                                        v-if="item.coverUrl"
                                        :src="item.coverUrl"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                    <FileText v-else class="size-4" />
                                </span>
                                <span
                                    class="line-clamp-2 max-w-72 min-w-48 font-medium text-navy-900 group-hover:text-brand-700"
                                >
                                    {{ item.title }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3 pr-4 whitespace-nowrap text-navy-700">
                            {{ item.author }}
                        </td>
                        <td class="py-3 pr-4 whitespace-nowrap text-navy-700">
                            {{ item.issue ?? '—' }}
                        </td>
                        <td
                            class="py-3 pr-4 whitespace-nowrap text-navy-600 tabular-nums"
                        >
                            {{ formatDateTime(item.submittedAt) }}
                        </td>
                        <td class="py-3 pr-4">
                            <span
                                :class="
                                    cn(
                                        'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                                        pill[item.statusGroup],
                                    )
                                "
                            >
                                {{ item.statusLabel }}
                            </span>
                        </td>
                        <td class="py-3 pr-5">
                            <div class="flex justify-end">
                                <Link
                                    v-if="item.url && canOpen"
                                    :href="item.url"
                                    :aria-label="t('Ko\'rish')"
                                    :title="t('Ko\'rish')"
                                    class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"
                                >
                                    <Eye class="size-4" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="py-10 text-center text-sm text-navy-500">
            {{ t("Hozircha yangi maqolalar yo'q") }}
        </p>
    </DashCard>
</template>

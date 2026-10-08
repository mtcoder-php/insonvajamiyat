<script setup lang="ts">
import {
    ChevronRight,
    FileSpreadsheet,
    FileText,
    ShieldCheck,
} from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import type { ReportExportLink } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Tezkor amallar": maqolalar Excel (CSV) hisoboti, umumiy PDF hisobot, audit log.
 */
defineProps<{
    exports: ReportExportLink[];
    printUrl: string;
    auditUrl: string | null;
}>();

const row =
    'group flex items-center gap-3 rounded-xl border px-3.5 py-3 text-[13px] font-semibold transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_22px_-14px_rgba(0,36,66,0.45)]';
</script>

<template>
    <DashCard :title="t('Tezkor amallar')">
        <div class="grid gap-2.5">
            <a
                v-if="exports[0]"
                :href="exports[0].url"
                :class="[
                    row,
                    'border-emerald-200 bg-emerald-50/70 text-emerald-800 hover:bg-emerald-50',
                ]"
            >
                <FileSpreadsheet class="size-5 shrink-0" />
                <span class="flex-1">{{ t('Excel hisobotini yuklash') }}</span>
                <ChevronRight
                    class="size-4 transition-transform group-hover:translate-x-0.5"
                />
            </a>
            <a
                :href="printUrl"
                target="_blank"
                rel="noopener"
                :class="[
                    row,
                    'border-red-200 bg-red-50/70 text-red-800 hover:bg-red-50',
                ]"
            >
                <FileText class="size-5 shrink-0" />
                <span class="flex-1">{{ t('PDF hisobotini yuklash') }}</span>
                <ChevronRight
                    class="size-4 transition-transform group-hover:translate-x-0.5"
                />
            </a>
            <a
                v-if="auditUrl"
                :href="auditUrl"
                :class="[
                    row,
                    'border-brand-200 bg-brand-50/70 text-brand-800 hover:bg-brand-50',
                ]"
            >
                <ShieldCheck class="size-5 shrink-0" />
                <span class="flex-1">{{ t('Audit log') }}</span>
                <ChevronRight
                    class="size-4 transition-transform group-hover:translate-x-0.5"
                />
            </a>
        </div>
    </DashCard>
</template>

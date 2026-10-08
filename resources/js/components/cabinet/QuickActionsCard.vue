<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpenText,
    Download,
    MessageCircleQuestion,
    Send,
} from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { contact } from '@/routes';
import { create } from '@/routes/cabinet/articles';
import type { CabinetLinks } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Tezkor amallar": yangi maqola, shablon, yo'riqnoma, savol berish.
 */
defineProps<{ links: CabinetLinks }>();

const item =
    'group/action flex items-center gap-3 rounded-lg border border-line bg-white px-3.5 py-2.5 text-[13px] font-medium text-navy-800 transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-700 hover:shadow-[0_10px_20px_-14px_rgba(0,108,246,0.7)]';
const icon =
    'size-[18px] shrink-0 text-navy-500 transition-all duration-200 group-hover/action:scale-110 group-hover/action:text-brand-600';
</script>

<template>
    <DashCard :title="t('Tezkor amallar')">
        <div class="flex flex-col gap-2">
            <Link
                :href="create()"
                class="group/action flex items-center gap-3 rounded-lg bg-brand-600 px-3.5 py-3 text-[13px] font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-700"
            >
                <Send
                    class="size-[18px] transition-transform duration-300 group-hover/action:translate-x-0.5 group-hover/action:-translate-y-0.5"
                />
                {{ t('Yangi maqola yuborish') }}
            </Link>
            <a
                v-if="links.template"
                :href="links.template"
                download
                :class="item"
            >
                <Download :class="icon" />
                {{ t('Maqola shablonini yuklab olish') }}
            </a>
            <Link :href="links.guidelines" :class="item">
                <BookOpenText :class="icon" />
                {{ t("Yo'riqnoma (PDF)") }}
            </Link>
            <Link :href="contact()" :class="item">
                <MessageCircleQuestion :class="icon" />
                {{ t('Savol berish') }}
            </Link>
        </div>
    </DashCard>
</template>

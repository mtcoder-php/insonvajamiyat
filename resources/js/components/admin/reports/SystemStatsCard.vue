<script setup lang="ts">
import {
    ArrowUp,
    ArrowDown,
    BookCopy,
    Coins,
    LibraryBig,
    UserPlus,
    Users,
    Wifi,
} from '@lucide/vue';
import type { Component } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import Sparkline from '@/components/admin/reports/Sparkline.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportSystem } from '@/types';

/**
 * "Tizim statistikasi" va davrdagi ro'yxatdan o'tishlar trendi.
 */
defineProps<{ data: ReportSystem }>();

const meta: Record<
    ReportSystem['items'][number]['key'],
    { label: string; icon: Component }
> = {
    users: { label: 'Jami foydalanuvchilar', icon: Users },
    new_users: { label: "Davrda ro'yxatdan o'tgan", icon: UserPlus },
    online: { label: 'Faol (24 soat)', icon: Wifi },
    issues: { label: 'Chop etilgan sonlar', icon: BookCopy },
    archive: { label: 'Maqolalar arxivi', icon: LibraryBig },
    tokens: { label: 'AI tokenlar (davr)', icon: Coins },
};
</script>

<template>
    <DashCard title="Tizim statistikasi">
        <ul class="space-y-0.5">
            <li
                v-for="item in data.items"
                :key="item.key"
                class="-mx-2 flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-muted"
            >
                <component
                    :is="meta[item.key].icon"
                    class="size-4 shrink-0 text-navy-400"
                />
                <span class="min-w-0 flex-1 truncate text-navy-700">
                    {{ meta[item.key].label }}
                </span>
                <span class="font-semibold text-navy-950 tabular-nums">
                    {{ formatNumber(item.value) }}
                </span>
                <span
                    v-if="item.trend !== null"
                    :class="
                        cn(
                            'inline-flex w-12 items-center justify-end gap-0.5 text-[11px] font-semibold',
                            item.trend >= 0
                                ? 'text-emerald-600'
                                : 'text-red-600',
                        )
                    "
                >
                    <ArrowUp v-if="item.trend >= 0" class="size-3" />
                    <ArrowDown v-else class="size-3" />
                    {{ Math.abs(item.trend) }}%
                </span>
                <span v-else class="w-12" />
            </li>
        </ul>
        <div class="mt-3 border-t border-line pt-3">
            <p class="mb-1 text-[11px] font-medium text-navy-400">
                Ro'yxatdan o'tishlar dinamikasi
            </p>
            <Sparkline
                :values="data.registrations"
                label="Ro'yxatdan o'tishlar dinamikasi"
            />
        </div>
    </DashCard>
</template>

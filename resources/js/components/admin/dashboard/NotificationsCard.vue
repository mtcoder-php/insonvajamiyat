<script setup lang="ts">
import {
    Bell,
    BookOpenCheck,
    CreditCard,
    FileText,
    ShieldAlert,
    UserCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { DashboardNotification } from '@/types';

/**
 * "So'nggi bildirishnomalar" (o'ng panel).
 */
defineProps<{ items: DashboardNotification[] }>();

const kinds: Record<string, { icon: Component; tint: string }> = {
    article_submitted: { icon: FileText, tint: 'bg-brand-50 text-brand-600' },
    reviewer_assigned: { icon: UserCheck, tint: 'bg-amber-50 text-amber-600' },
    payment_confirmed: {
        icon: CreditCard,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    published: { icon: BookOpenCheck, tint: 'bg-violet-50 text-violet-600' },
    security: { icon: ShieldAlert, tint: 'bg-red-50 text-red-600' },
};

const fallback = { icon: Bell, tint: 'bg-navy-50 text-navy-600' };
</script>

<template>
    <DashCard title="So'nggi bildirishnomalar">
        <ul v-if="items.length" class="-mx-2 space-y-0.5">
            <li
                v-for="item in items"
                :key="item.id"
                class="group flex gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-[#f6f8fb]"
            >
                <span
                    :class="
                        cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                            (kinds[item.kind] ?? fallback).tint,
                        )
                    "
                >
                    <component
                        :is="(kinds[item.kind] ?? fallback).icon"
                        class="size-4"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p
                        class="flex items-center gap-1.5 text-[13px] font-semibold text-navy-950"
                    >
                        <span class="truncate">{{ item.title }}</span>
                        <span
                            v-if="!item.read"
                            class="size-1.5 shrink-0 rounded-full bg-brand-500"
                            aria-label="O'qilmagan"
                        />
                    </p>
                    <p
                        v-if="item.message"
                        class="truncate text-xs text-navy-600"
                    >
                        {{ item.message }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-navy-400">
                        {{ timeAgo(item.createdAt) }}
                    </p>
                </div>
            </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-navy-500">
            Yangi bildirishnomalar yo'q
        </p>
    </DashCard>
</template>

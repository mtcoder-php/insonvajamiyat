<script setup lang="ts">
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { timeAgo } from '@/lib/format';
import type { ActiveUser } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Faol foydalanuvchilar" — oxirgi kirganlar.
 */
defineProps<{ items: ActiveUser[] }>();

const tints = [
    'bg-brand-100 text-brand-700',
    'bg-amber-100 text-amber-700',
    'bg-emerald-100 text-emerald-700',
    'bg-violet-100 text-violet-700',
    'bg-rose-100 text-rose-700',
];

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

// So'nggi 15 daqiqada kirganlar "onlayn" deb belgilanadi
function isOnline(value: string | null): boolean {
    return (
        value !== null && Date.now() - new Date(value).getTime() < 15 * 60_000
    );
}
</script>

<template>
    <DashCard :title="t('Faol foydalanuvchilar')">
        <ul v-if="items.length" class="-mx-2 space-y-0.5">
            <li
                v-for="(user, index) in items"
                :key="user.id"
                class="group flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-[#f6f8fb]"
            >
                <span class="relative shrink-0">
                    <img
                        v-if="user.avatarUrl"
                        :src="user.avatarUrl"
                        alt=""
                        class="size-9 rounded-full object-cover ring-2 ring-white"
                    />
                    <span
                        v-else
                        :class="[
                            'flex size-9 items-center justify-center rounded-full text-xs font-bold ring-2 ring-white',
                            tints[index % tints.length],
                        ]"
                    >
                        {{ initials(user.name) }}
                    </span>
                    <span
                        :class="[
                            'absolute -right-0.5 -bottom-0.5 size-3 rounded-full ring-2 ring-white',
                            isOnline(user.lastSeenAt)
                                ? 'bg-emerald-500'
                                : 'bg-navy-300',
                        ]"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p
                        class="truncate text-[13px] font-semibold text-navy-950 group-hover:text-brand-700"
                    >
                        {{ user.name }}
                    </p>
                    <p class="truncate text-xs text-navy-500">
                        {{ user.role ?? '—' }}
                    </p>
                </div>
                <span
                    class="shrink-0 text-[11px] whitespace-nowrap text-navy-400"
                >
                    {{ timeAgo(user.lastSeenAt) }}
                </span>
            </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-navy-500">
            {{ t("Hozircha faol foydalanuvchilar yo'q") }}
        </p>
    </DashCard>
</template>

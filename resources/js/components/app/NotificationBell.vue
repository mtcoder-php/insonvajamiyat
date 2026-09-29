<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, BellOff } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/**
 * Header'dagi qo'ng'iroqcha: o'qilmagan bildirishnomalar soni
 * (HandleInertiaRequests → notifications.unread).
 * Bildirishnomalar ro'yxati xabarnomalar moduli bilan qo'shiladi.
 */
const unread = computed(() => usePage().props.notifications?.unread ?? 0);
const badge = computed(() =>
    unread.value > 99 ? '99+' : String(unread.value),
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="relative flex size-10 items-center justify-center rounded-full text-white/85 transition-colors hover:bg-white/10 hover:text-white"
                :aria-label="`Bildirishnomalar: ${unread} ta o'qilmagan`"
            >
                <Bell class="size-5" />
                <span
                    v-if="unread > 0"
                    class="absolute top-1 right-1 flex min-w-4.5 items-center justify-center rounded-full bg-danger px-1 text-[10px] leading-4.5 font-bold text-white ring-2 ring-navy-950"
                >
                    {{ badge }}
                </span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-72">
            <DropdownMenuLabel class="font-serif text-base">
                Bildirishnomalar
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div
                class="flex flex-col items-center gap-2 px-4 py-6 text-center text-sm text-muted-foreground"
            >
                <BellOff v-if="unread === 0" class="size-6 opacity-60" />
                <Bell v-else class="size-6 text-brand-600" />
                <span v-if="unread === 0">Yangi bildirishnomalar yo'q</span>
                <span v-else>{{ unread }} ta o'qilmagan bildirishnoma</span>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

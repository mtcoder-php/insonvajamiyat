<script setup lang="ts">
import { BookPlus, CreditCard, FilePlus2, UserPlus } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { cn } from '@/lib/utils';

/**
 * "Tezkor amallar". Tegishli admin bo'limlari tayyor bo'lgach havolaga aylanadi.
 */
const actions = [
    {
        key: 'article',
        label: 'Yangi maqola yuborish',
        icon: FilePlus2,
        button: 'border-brand-600 bg-brand-600 text-white hover:bg-brand-500',
        tint: 'bg-white/15 text-white',
    },
    {
        key: 'issue',
        label: 'Jurnal sonini yaratish',
        icon: BookPlus,
        button: 'border-line bg-white text-navy-800 hover:border-brand-200',
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'payment',
        label: "To'lov qabul qilish",
        icon: CreditCard,
        button: 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:border-emerald-300',
        tint: 'bg-white text-emerald-600',
    },
    {
        key: 'user',
        label: "Foydalanuvchi qo'shish",
        icon: UserPlus,
        button: 'border-line bg-white text-navy-800 hover:border-brand-200',
        tint: 'bg-amber-50 text-amber-600',
    },
];
</script>

<template>
    <DashCard title="Tezkor amallar">
        <div class="grid grid-cols-2 gap-2.5">
            <button
                v-for="action in actions"
                :key="action.key"
                type="button"
                disabled
                :title="`${action.label} — tez orada`"
                :class="
                    cn(
                        'group flex cursor-not-allowed flex-col items-center gap-2 rounded-xl border px-2 py-3.5 text-center transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(0,36,66,0.4)]',
                        action.button,
                    )
                "
            >
                <span
                    :class="
                        cn(
                            'flex size-10 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                            action.tint,
                        )
                    "
                >
                    <component :is="action.icon" class="size-5" />
                </span>
                <span class="text-xs leading-tight font-semibold">
                    {{ action.label }}
                </span>
            </button>
        </div>
    </DashCard>
</template>

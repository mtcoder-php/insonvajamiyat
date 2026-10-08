<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookPlus, CreditCard, FileInput, UserPlus } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index as articlesIndex } from '@/routes/admin/articles';
import { index as issuesIndex } from '@/routes/admin/issues';
import { index as paymentsIndex } from '@/routes/admin/payments';
import { create as usersCreate } from '@/routes/admin/users';

/**
 * "Tezkor amallar" — foydalanuvchi ruxsatiga mos bo'limlarga to'g'ridan-to'g'ri havolalar.
 */
type Action = {
    key: string;
    label: string;
    href: string;
    permission: string;
    icon: Component;
    button: string;
    tint: string;
};

const { can } = usePermissions();

const all: Action[] = [
    {
        key: 'articles',
        label: t('Yangi maqolalar'),
        href: articlesIndex.url({ query: { queue: 'new' } }),
        permission: 'articles.view_any',
        icon: FileInput,
        button: 'border-brand-600 bg-brand-600 text-white hover:bg-brand-500',
        tint: 'bg-white/15 text-white',
    },
    {
        key: 'issue',
        label: t('Jurnal sonini yaratish'),
        href: issuesIndex.url({ query: { create: 1 } }),
        permission: 'issues.manage',
        icon: BookPlus,
        button: 'border-line bg-white text-navy-800 hover:border-brand-200',
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'payment',
        label: t("To'lov qabul qilish"),
        href: paymentsIndex.url({ query: { tab: 'awaiting' } }),
        permission: 'payments.view',
        icon: CreditCard,
        button: 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:border-emerald-300',
        tint: 'bg-white text-emerald-600',
    },
    {
        key: 'user',
        label: t("Foydalanuvchi qo'shish"),
        href: usersCreate.url(),
        permission: 'users.manage',
        icon: UserPlus,
        button: 'border-line bg-white text-navy-800 hover:border-brand-200',
        tint: 'bg-amber-50 text-amber-600',
    },
];

const actions = computed(() => all.filter((action) => can(action.permission)));
</script>

<template>
    <DashCard v-if="actions.length" :title="t('Tezkor amallar')">
        <div class="grid grid-cols-2 gap-2.5">
            <Link
                v-for="action in actions"
                :key="action.key"
                :href="action.href"
                :class="
                    cn(
                        'group flex flex-col items-center gap-2 rounded-xl border px-2 py-3.5 text-center transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-14px_rgba(0,36,66,0.4)]',
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
            </Link>
        </div>
    </DashCard>
</template>

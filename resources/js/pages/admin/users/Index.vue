<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Eye,
    PenLine,
    RotateCcw,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
    UserRound,
    Users,
    X,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, reactive, watch } from 'vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import Pagination from '@/components/admin/ui/Pagination.vue';
import RoleBadges from '@/components/users/RoleBadges.vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import UserStatusBadge from '@/components/users/UserStatusBadge.vue';
import { formatDate, formatNumber, formatPhone, timeAgo } from '@/lib/format';
import { inputClass, primaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { create, edit, index, show } from '@/routes/admin/users';
import type {
    Paginated,
    RoleOption,
    UserCounts,
    UserFilters,
    UserListItem,
} from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Foydalanuvchilar ro'yxati: qidiruv, rol/holat filtrlari, tartiblash, sahifalash.
 */
const props = defineProps<{
    users: Paginated<UserListItem>;
    filters: UserFilters;
    counts?: UserCounts;
    roleOptions: RoleOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Foydalanuvchilar'), href: index() },
        ],
    },
});

const form = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? 'latest',
});

const hasFilters = computed(
    () =>
        Boolean(form.search || form.role || form.status) ||
        form.sort !== 'latest',
);

let timer: ReturnType<typeof setTimeout> | undefined;

function apply(): void {
    const query = Object.fromEntries(
        Object.entries(form).filter(
            ([key, value]) =>
                value !== '' && !(key === 'sort' && value === 'latest'),
        ),
    );

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
    });
}

// Qidiruv — yozib bo'lgach 350 ms kutib; boshqa filtrlar — darhol
watch(
    () => form.search,
    () => {
        clearTimeout(timer);
        timer = setTimeout(apply, 350);
    },
);
watch(() => [form.role, form.status, form.sort], apply);

function reset(): void {
    form.search = '';
    form.role = '';
    form.status = '';
    form.sort = 'latest';
}

function setStatus(status: string, role = ''): void {
    form.role = role;
    form.status = status;
}

type Stat = {
    key: string;
    label: string;
    value: number | undefined;
    icon: Component;
    tint: string;
    active: boolean;
    apply: () => void;
};

const stats = computed<Stat[]>(() => [
    {
        key: 'total',
        label: t('Jami'),
        value: props.counts?.total,
        icon: Users,
        tint: 'bg-brand-50 text-brand-600',
        active: !form.role && !form.status,
        apply: () => setStatus(''),
    },
    {
        key: 'staff',
        label: t('Xodimlar'),
        value: props.counts?.staff,
        icon: ShieldCheck,
        tint: 'bg-violet-50 text-violet-600',
        active: form.role === 'staff' && !form.status,
        apply: () => setStatus('', 'staff'),
    },
    {
        key: 'authors',
        label: t('Mualliflar'),
        value: props.counts?.authors,
        icon: UserRound,
        tint: 'bg-amber-50 text-amber-600',
        active: form.role === 'author' && !form.status,
        apply: () => setStatus('', 'author'),
    },
    {
        key: 'blocked',
        label: t('Bloklangan'),
        value: props.counts?.blocked,
        icon: Ban,
        tint: 'bg-red-50 text-red-600',
        active: form.status === 'blocked',
        apply: () => setStatus('blocked'),
    },
    {
        key: 'deleted',
        label: t("O'chirilgan"),
        value: props.counts?.deleted,
        icon: Trash2,
        tint: 'bg-navy-50 text-navy-600',
        active: form.status === 'deleted',
        apply: () => setStatus('deleted'),
    },
]);

const statuses = [
    { value: '', label: t('Barcha holatlar') },
    { value: 'active', label: t('Faol') },
    { value: 'blocked', label: t('Bloklangan') },
    { value: 'unverified', label: t('Email tasdiqlanmagan') },
    { value: 'deleted', label: t("O'chirilgan") },
];

const sorts = [
    { value: 'latest', label: t('Avval yangilari') },
    { value: 'oldest', label: t('Avval eskilari') },
    { value: 'name', label: t("Ism bo'yicha (A–Z)") },
    { value: 'last_login', label: t("Oxirgi kirish bo'yicha") },
];

function open(user: UserListItem): void {
    router.visit(show.url(user.id));
}
</script>

<template>
    <Head :title="t('Foydalanuvchilar')" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <PageHeader
            :title="t('Foydalanuvchilar')"
            :description="
                t(
                    'Xodimlar va mualliflar: rollar, holat va profil ma\'lumotlari',
                )
            "
        >
            <template #actions>
                <Link :href="create()" :class="primaryButtonClass">
                    <UserPlus class="size-4" />
                    {{ t("Foydalanuvchi qo'shish") }}
                </Link>
            </template>
        </PageHeader>

        <!-- Statistika -->
        <section
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5"
            :aria-label="t('Statistika')"
        >
            <button
                v-for="stat in stats"
                :key="stat.key"
                type="button"
                :class="
                    cn(
                        'group flex items-center gap-3 rounded-xl border bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-16px_rgba(0,36,66,0.35)]',
                        stat.active
                            ? 'border-brand-300 ring-2 ring-brand-100'
                            : 'border-line',
                    )
                "
                @click="stat.apply()"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                            stat.tint,
                        )
                    "
                >
                    <component :is="stat.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span
                        class="block truncate text-xs font-medium text-navy-500"
                        >{{ stat.label }}</span
                    >
                    <span
                        class="block text-xl leading-tight font-bold text-navy-950 tabular-nums"
                    >
                        {{
                            stat.value === undefined
                                ? '—'
                                : formatNumber(stat.value)
                        }}
                    </span>
                </span>
            </button>
        </section>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <!-- Filtrlar -->
            <div
                class="grid gap-3 border-b border-line p-4 md:grid-cols-[minmax(0,1fr)_11rem_12rem_12rem_auto]"
            >
                <label class="relative">
                    <span class="sr-only">{{ t('Qidirish') }}</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="form.search"
                        type="search"
                        :class="cn(inputClass, 'pl-9')"
                        :placeholder="
                            t('Ism, email, telefon yoki tashkilot...')
                        "
                    />
                </label>
                <SelectInput v-model="form.role" :aria-label="t('Rol')">
                    <option value="">{{ t('Barcha rollar') }}</option>
                    <option value="staff">{{ t('Barcha xodimlar') }}</option>
                    <option
                        v-for="role in roleOptions"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </SelectInput>
                <SelectInput v-model="form.status" :aria-label="t('Holat')">
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </SelectInput>
                <SelectInput v-model="form.sort" :aria-label="t('Tartib')">
                    <option v-for="s in sorts" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </option>
                </SelectInput>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-navy-600 transition-colors hover:bg-navy-50 hover:text-navy-900"
                    @click="reset"
                >
                    <X class="size-4" />
                    {{ t('Tozalash') }}
                </button>
            </div>

            <!-- Jadval (planshet va kompyuter) -->
            <div
                v-if="users.data.length"
                class="hidden overflow-x-auto md:block"
            >
                <table class="w-full min-w-[900px] text-left text-[13px]">
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                        >
                            <th class="py-3 pl-5">{{ t('Foydalanuvchi') }}</th>
                            <th class="py-3 pr-4">{{ t('Telefon') }}</th>
                            <th class="py-3 pr-4">{{ t('Rollar') }}</th>
                            <th class="py-3 pr-4">{{ t('Holat') }}</th>
                            <th class="py-3 pr-4">{{ t('Oxirgi kirish') }}</th>
                            <th class="py-3 pr-4">
                                {{ t("Ro'yxatdan o'tgan") }}
                            </th>
                            <th class="py-3 pr-5 text-right">
                                {{ t('Amallar') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            :class="
                                cn(
                                    'group cursor-pointer transition-colors hover:bg-brand-50/40',
                                    user.isDeleted && 'opacity-60',
                                )
                            "
                            @click="open(user)"
                        >
                            <td class="py-3 pl-5">
                                <div class="flex items-center gap-3">
                                    <UserAvatar
                                        :name="user.name"
                                        :url="user.avatarUrl"
                                        size="md"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-semibold text-navy-950 group-hover:text-brand-700"
                                        >
                                            {{ user.name }}
                                        </p>
                                        <p
                                            class="flex items-center gap-1.5 truncate text-xs text-navy-500"
                                        >
                                            {{ user.email }}
                                            <span
                                                v-if="!user.isVerified"
                                                class="rounded bg-amber-50 px-1 text-[10px] font-semibold text-amber-700"
                                                :title="
                                                    t('Email tasdiqlanmagan')
                                                "
                                            >
                                                {{ t('tasdiqlanmagan') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="py-3 pr-4 whitespace-nowrap text-navy-700 tabular-nums"
                            >
                                {{ formatPhone(user.phone) || '—' }}
                            </td>
                            <td class="py-3 pr-4">
                                <RoleBadges :roles="user.roles" />
                            </td>
                            <td class="py-3 pr-4">
                                <UserStatusBadge
                                    :is-blocked="user.isBlocked"
                                    :is-deleted="user.isDeleted"
                                />
                            </td>
                            <td
                                class="py-3 pr-4 whitespace-nowrap text-navy-600"
                            >
                                {{
                                    user.lastLoginAt
                                        ? timeAgo(user.lastLoginAt)
                                        : t('Kirmagan')
                                }}
                            </td>
                            <td
                                class="py-3 pr-4 whitespace-nowrap text-navy-600 tabular-nums"
                            >
                                {{ formatDate(user.createdAt) }}
                            </td>
                            <td class="py-3 pr-5" @click.stop>
                                <div class="flex justify-end gap-1">
                                    <Link
                                        :href="show(user.id)"
                                        class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-600"
                                        :title="t('Profilni ko\'rish')"
                                    >
                                        <Eye class="size-4" />
                                    </Link>
                                    <Link
                                        v-if="!user.isDeleted"
                                        :href="edit(user.id)"
                                        class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-600"
                                        :title="t('Tahrirlash')"
                                    >
                                        <PenLine class="size-4" />
                                    </Link>
                                    <Link
                                        v-else
                                        :href="show(user.id)"
                                        class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-emerald-200 hover:text-emerald-600"
                                        :title="t('Tiklash')"
                                    >
                                        <RotateCcw class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Kartochkalar (telefon) -->
            <ul v-if="users.data.length" class="divide-y divide-line md:hidden">
                <li v-for="user in users.data" :key="user.id">
                    <Link
                        :href="show(user.id)"
                        :class="
                            cn(
                                'flex items-start gap-3 p-4 transition-colors hover:bg-brand-50/40',
                                user.isDeleted && 'opacity-60',
                            )
                        "
                    >
                        <UserAvatar
                            :name="user.name"
                            :url="user.avatarUrl"
                            size="md"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-navy-950">
                                {{ user.name }}
                            </p>
                            <p class="truncate text-xs text-navy-500">
                                {{ user.email }}
                            </p>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-1.5"
                            >
                                <UserStatusBadge
                                    :is-blocked="user.isBlocked"
                                    :is-deleted="user.isDeleted"
                                />
                                <RoleBadges :roles="user.roles" />
                            </div>
                        </div>
                    </Link>
                </li>
            </ul>

            <!-- Bo'sh holat -->
            <div
                v-if="!users.data.length"
                class="flex flex-col items-center px-6 py-16 text-center"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-500"
                >
                    <Users class="size-7" />
                </span>
                <p class="mt-4 font-semibold text-navy-900">
                    {{ t('Foydalanuvchi topilmadi') }}
                </p>
                <p class="mt-1 text-sm text-navy-500">
                    {{
                        t(
                            "Qidiruv so'zini yoki filtrlarni o'zgartirib ko'ring.",
                        )
                    }}
                </p>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="mt-4 text-sm font-semibold text-brand-700 hover:text-brand-600"
                    @click="reset"
                >
                    {{ t('Filtrlarni tozalash') }}
                </button>
            </div>

            <div class="border-t border-line bg-[#fbfcfe] px-4 py-3">
                <Pagination :meta="users.meta" />
            </div>
        </section>
    </div>
</template>

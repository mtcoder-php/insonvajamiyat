<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    Info,
    Lock,
    RotateCcw,
    Save,
    ShieldCheck,
    Undo2,
    Users,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/roles';
import type { RoleRow, RolesPageProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Admin → Rollar va ruxsatlar: rol × ruxsat matritsasi.
 * Har bir rol (ustun) alohida saqlanadi; standartdan farq qiluvchi katakchalar oltin nuqta bilan.
 */
const props = defineProps<RolesPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Rollar va ruxsatlar'), href: index() },
        ],
    },
});

const ADMIN_ACCESS = 'admin.access';

/** Tahrirlanayotgan rollar: rol nomi → tanlangan ruxsatlar */
const edits = reactive<Record<string, string[]>>({});
const saving = ref<string | null>(null);
const errors = reactive<Record<string, string>>({});

function current(role: RoleRow): string[] {
    return edits[role.name] ?? role.permissions;
}

function has(role: RoleRow, permission: string): boolean {
    return current(role).includes(permission);
}

function isLocked(
    role: RoleRow,
    permission: { value: string; forAuthors: boolean },
): boolean {
    if (role.locked) {
        return true;
    }

    if (role.isStaff && permission.value === ADMIN_ACCESS) {
        return true;
    }

    return !role.isStaff && !permission.forAuthors;
}

function toggle(
    role: RoleRow,
    permission: { value: string; forAuthors: boolean },
): void {
    if (isLocked(role, permission)) {
        return;
    }

    const list = current(role);
    const next = list.includes(permission.value)
        ? list.filter((p) => p !== permission.value)
        : [...list, permission.value];

    if (sameSet(next, role.permissions)) {
        delete edits[role.name];
    } else {
        edits[role.name] = next;
    }

    delete errors[role.name];
}

function sameSet(a: string[], b: string[]): boolean {
    return a.length === b.length && a.every((x) => b.includes(x));
}

function isDirty(role: RoleRow): boolean {
    return edits[role.name] !== undefined;
}

function differsFromDefault(role: RoleRow, permission: string): boolean {
    return (
        !role.locked &&
        has(role, permission) !== role.defaults.includes(permission)
    );
}

function save(role: RoleRow, next?: () => void): void {
    if (!role.updateUrl || !isDirty(role)) {
        next?.();

        return;
    }

    saving.value = role.name;
    router.put(
        role.updateUrl,
        { permissions: current(role) },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => delete edits[role.name],
            onError: (e) =>
                (errors[role.name] =
                    Object.values(e)[0] ?? t("Saqlab bo'lmadi.")),
            onFinish: () => {
                saving.value = null;
                next?.();
            },
        },
    );
}

/** Barcha o'zgartirilgan rollarni ketma-ket saqlash */
function saveAll(): void {
    const queue = props.roles.filter((r) => isDirty(r));
    const step = (): void => {
        const role = queue.shift();

        if (role) {
            save(role, step);
        }
    };

    step();
}

function discardAll(): void {
    Object.keys(edits).forEach((key) => delete edits[key]);
}

function discard(role: RoleRow): void {
    delete edits[role.name];
    delete errors[role.name];
}

const resetting = ref<RoleRow | null>(null);
const resetOpen = ref(false);
const resetProcessing = ref(false);

function askReset(role: RoleRow): void {
    resetting.value = role;
    resetOpen.value = true;
}

function confirmReset(): void {
    const role = resetting.value;

    if (!role?.resetUrl) {
        return;
    }

    resetProcessing.value = true;
    router.post(
        role.resetUrl,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                delete edits[role.name];
                resetOpen.value = false;
            },
            onError: (e) =>
                (errors[role.name] =
                    Object.values(e)[0] ?? t("Qaytarib bo'lmadi.")),
            onFinish: () => (resetProcessing.value = false),
        },
    );
}

const dirtyCount = computed(() => Object.keys(edits).length);

const totalPermissions = computed(() =>
    props.groups.reduce((sum, g) => sum + g.permissions.length, 0),
);
</script>

<template>
    <Head :title="t('Rollar va ruxsatlar')" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            :title="t('Rollar va ruxsatlar')"
            :description="
                t(
                    'Har bir rol qaysi amallarni bajara olishini belgilang. Foydalanuvchiga rol «Foydalanuvchilar» bo\'limida beriladi.',
                )
            "
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <ShieldCheck class="size-5" />
                </span>
            </template>
        </PageHeader>

        <div
            class="flex flex-col gap-2 rounded-xl border border-brand-100 bg-brand-50/60 px-4 py-3 text-xs text-navy-700 sm:flex-row sm:items-center sm:gap-5"
        >
            <span class="inline-flex items-center gap-1.5 font-semibold">
                <Info class="size-4 text-brand-600" /> {{ t('Belgilar:') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <Lock class="size-3.5 text-navy-400" />
                {{ t("o'zgartirib bo'lmaydi") }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-gold-500" />
                {{ t('standartdan farq qiladi') }}
            </span>
            <span class="text-navy-500 sm:ml-auto">{{
                t(
                    'Bosh administrator barcha ruxsatlarga ega; muallifga faqat AI Studio berilishi mumkin.',
                )
            }}</span>
        </div>

        <section
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[1080px] border-collapse text-[13px]"
                >
                    <thead>
                        <tr
                            class="border-b border-line bg-[#f8fafc] align-bottom"
                        >
                            <th
                                class="sticky left-0 z-10 w-72 bg-[#f8fafc] px-5 py-4 text-left text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                            >
                                {{ t('Ruxsat') }}
                                <span class="block font-normal normal-case">{{
                                    t(':count ta', { count: totalPermissions })
                                }}</span>
                            </th>
                            <th
                                v-for="role in roles"
                                :key="role.name"
                                :class="
                                    cn(
                                        'w-[8.5rem] px-2 py-3 text-center align-bottom transition-colors',
                                        isDirty(role) && 'bg-gold-100/50',
                                    )
                                "
                            >
                                <p
                                    class="text-[12px] leading-tight font-bold text-navy-950"
                                >
                                    {{ role.label }}
                                </p>
                                <Link
                                    :href="role.usersUrl"
                                    class="mt-1 inline-flex items-center gap-1 text-[11px] text-navy-500 transition-colors hover:text-brand-700"
                                >
                                    <Users class="size-3" />
                                    {{ formatNumber(role.users) }}
                                </Link>
                                <div class="mt-1.5 flex justify-center">
                                    <span
                                        v-if="role.locked"
                                        class="inline-flex items-center gap-0.5 rounded bg-navy-900 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                        ><Lock class="size-2.5" />
                                        {{ t("To'liq") }}</span
                                    >
                                    <span
                                        v-else-if="
                                            role.isDefault && !isDirty(role)
                                        "
                                        class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"
                                        >{{ t('Standart') }}</span
                                    >
                                    <button
                                        v-else-if="!isDirty(role)"
                                        type="button"
                                        class="inline-flex items-center gap-0.5 rounded bg-gold-100 px-1.5 py-0.5 text-[10px] font-semibold text-gold-700 transition-colors hover:bg-gold-200"
                                        :title="
                                            t('Standart ruxsatlarga qaytarish')
                                        "
                                        @click="askReset(role)"
                                    >
                                        <RotateCcw class="size-2.5" />
                                        {{ t("O'zgartirilgan") }}
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in groups" :key="group.key">
                            <tr class="border-b border-line bg-[#fbfcfe]">
                                <td
                                    :colspan="roles.length + 1"
                                    class="sticky left-0 px-5 pt-4 pb-2 text-[11px] font-bold tracking-wide text-brand-700 uppercase"
                                >
                                    {{ group.label }}
                                </td>
                            </tr>
                            <tr
                                v-for="permission in group.permissions"
                                :key="permission.value"
                                class="group border-b border-line last:border-0 hover:bg-brand-50/30"
                            >
                                <td
                                    class="sticky left-0 z-10 bg-white px-5 py-2.5 transition-colors group-hover:bg-[#f7faff]"
                                >
                                    <p class="font-semibold text-navy-900">
                                        {{ permission.label }}
                                    </p>
                                    <p
                                        class="font-mono text-[10px] text-navy-400"
                                    >
                                        {{ permission.value }}
                                    </p>
                                </td>
                                <td
                                    v-for="role in roles"
                                    :key="role.name"
                                    :class="
                                        cn(
                                            'px-2 py-2 text-center',
                                            isDirty(role) && 'bg-gold-100/30',
                                        )
                                    "
                                >
                                    <button
                                        type="button"
                                        role="checkbox"
                                        :aria-checked="
                                            has(role, permission.value)
                                        "
                                        :aria-label="`${role.label}: ${permission.label}`"
                                        :disabled="isLocked(role, permission)"
                                        :class="
                                            cn(
                                                'relative inline-flex size-7 items-center justify-center rounded-lg border-2 transition-all duration-150',
                                                has(role, permission.value)
                                                    ? isLocked(role, permission)
                                                        ? 'border-navy-200 bg-navy-100 text-navy-500'
                                                        : 'border-brand-600 bg-brand-600 text-white shadow-[0_4px_10px_-4px_rgba(0,108,246,0.8)] hover:scale-110'
                                                    : isLocked(role, permission)
                                                      ? 'border-dashed border-line bg-[#f8fafc] text-navy-200'
                                                      : 'border-navy-200 bg-white text-transparent hover:scale-110 hover:border-brand-400',
                                                'disabled:cursor-not-allowed',
                                            )
                                        "
                                        @click="toggle(role, permission)"
                                    >
                                        <Lock
                                            v-if="
                                                isLocked(role, permission) &&
                                                !has(role, permission.value)
                                            "
                                            class="size-3"
                                        />
                                        <Check
                                            v-else
                                            class="size-4"
                                            :stroke-width="3"
                                        />
                                        <span
                                            v-if="
                                                differsFromDefault(
                                                    role,
                                                    permission.value,
                                                )
                                            "
                                            class="absolute -top-1 -right-1 size-2 rounded-full bg-gold-500 ring-2 ring-white"
                                        />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-line bg-[#f8fafc]">
                            <td
                                class="sticky left-0 z-10 bg-[#f8fafc] px-5 py-3 text-xs text-navy-500"
                            >
                                {{
                                    dirtyCount
                                        ? t(
                                              ":dirtyCount ta rol o'zgartirildi — saqlang",
                                              { dirtyCount: dirtyCount },
                                          )
                                        : "O'zgarishlar yo'q"
                                }}
                            </td>
                            <td
                                v-for="role in roles"
                                :key="role.name"
                                class="px-2 py-3 text-center align-top"
                            >
                                <div
                                    v-if="isDirty(role)"
                                    class="flex flex-col items-center gap-1"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg bg-brand-600 px-2.5 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-px hover:bg-brand-500 disabled:opacity-60"
                                        :disabled="saving === role.name"
                                        @click="save(role)"
                                    >
                                        <Save class="size-3.5" />
                                        {{ t('Saqlash') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-0.5 text-[11px] font-medium text-navy-500 hover:text-navy-900"
                                        @click="discard(role)"
                                    >
                                        <Undo2 class="size-3" />
                                        {{ t('Bekor') }}
                                    </button>
                                </div>
                                <p
                                    v-if="errors[role.name]"
                                    class="mt-1 text-[11px] leading-tight text-red-600"
                                >
                                    {{ errors[role.name] }}
                                </p>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <Transition
            enter-from-class="translate-y-4 opacity-0"
            leave-to-class="translate-y-4 opacity-0"
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-150"
        >
            <div
                v-if="dirtyCount"
                class="sticky bottom-3 z-20 flex flex-wrap items-center gap-3 rounded-xl border border-gold-300 bg-white/95 px-4 py-3 shadow-[0_14px_34px_-16px_rgba(0,36,66,0.5)] backdrop-blur"
            >
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-gold-100 text-gold-700"
                >
                    <ShieldCheck class="size-4" />
                </span>
                <p class="mr-auto text-[13px] text-navy-800">
                    <b>{{ dirtyCount }}</b>
                    {{ t("ta rolda saqlanmagan o'zgarish:") }}
                    <span class="text-navy-500">{{
                        roles
                            .filter((r) => isDirty(r))
                            .map((r) => r.label)
                            .join(', ')
                    }}</span>
                </p>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-navy-600 transition-colors hover:bg-navy-50"
                    @click="discardAll"
                >
                    <Undo2 class="size-4" /> {{ t('Bekor qilish') }}
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:opacity-60"
                    :disabled="saving !== null"
                    @click="saveAll"
                >
                    <Save class="size-4" /> {{ t('Hammasini saqlash') }}
                </button>
            </div>
        </Transition>

        <ActionDialog
            v-model:open="resetOpen"
            :title="t('Standart ruxsatlarga qaytarish')"
            :description="
                t(
                    '«:role» roli tizimdagi standart ruxsatlar to\'plamiga qaytariladi. Qo\'lda qilingan o\'zgarishlar bekor bo\'ladi.',
                    { role: resetting?.label ?? '' },
                )
            "
            :icon="RotateCcw"
            :confirm-text="t('Qaytarish')"
            :processing="resetProcessing"
            @confirm="confirmReset"
        />
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Ban,
    BookOpenCheck,
    Building2,
    CircleAlert,
    Clock,
    CreditCard,
    FileText,
    GraduationCap,
    KeyRound,
    LockOpen,
    Mail,
    MailCheck,
    MailWarning,
    MapPin,
    PenLine,
    Phone,
    RotateCcw,
    Send,
    ShieldCheck,
    ShieldOff,
    Sparkles,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import AvatarUploader from '@/components/users/AvatarUploader.vue';
import RoleBadges from '@/components/users/RoleBadges.vue';
import UserStatusBadge from '@/components/users/UserStatusBadge.vue';
import {
    formatDate,
    formatDateTime,
    formatNumber,
    formatPhone,
    formatSum,
    timeAgo,
} from '@/lib/format';
import {
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import {
    block,
    destroy,
    edit,
    index,
    restore,
    unblock,
} from '@/routes/admin/users';
import {
    destroy as destroyAvatar,
    store as storeAvatar,
} from '@/routes/admin/users/avatar';
import {
    reset as sendReset,
    update as updatePassword,
} from '@/routes/admin/users/password';
import type { UserActivity, UserArticleItem, UserDetail } from '@/types';

/**
 * Foydalanuvchi profili (admin ko'rinishi): ma'lumotlar, faoliyat,
 * rasm, bloklash, parol, o'chirish / tiklash.
 */
const props = defineProps<{
    user: UserDetail;
    activity?: UserActivity;
    articles?: UserArticleItem[];
    passwordRules: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Foydalanuvchilar', href: index() },
        ],
    },
});

const editable = computed(() => props.user.can.update && !props.user.isDeleted);

// --- Bloklash
const blockOpen = ref(false);
const blockForm = useForm({ reason: '' });

function submitBlock(): void {
    blockForm.post(block.url(props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            blockOpen.value = false;
            blockForm.reset();
        },
    });
}

// Server qoidasi xatosi (masalan, "O'zingizni bloklay olmaysiz")
const blockGuardError = computed(
    () => (blockForm.errors as Record<string, string | undefined>).user,
);

const unblockOpen = ref(false);
const unblocking = ref(false);

function submitUnblock(): void {
    router.delete(unblock.url(props.user.id), {
        preserveScroll: true,
        onStart: () => (unblocking.value = true),
        onFinish: () => {
            unblocking.value = false;
            unblockOpen.value = false;
        },
    });
}

// --- Parol
const passwordOpen = ref(false);
const passwordForm = useForm({ password: '', password_confirmation: '' });

function generatePassword(): void {
    const chars =
        'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*?';
    const values = crypto.getRandomValues(new Uint32Array(14));
    const password = Array.from(values, (v) => chars[v % chars.length]).join(
        '',
    );

    passwordForm.password = password;
    passwordForm.password_confirmation = password;
}

function submitPassword(): void {
    passwordForm.put(updatePassword.url(props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            passwordOpen.value = false;
            passwordForm.reset();
        },
    });
}

const resetOpen = ref(false);
const sendingReset = ref(false);

function submitReset(): void {
    router.post(
        sendReset.url(props.user.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (sendingReset.value = true),
            onFinish: () => {
                sendingReset.value = false;
                resetOpen.value = false;
            },
        },
    );
}

// --- O'chirish / tiklash
const deleteOpen = ref(false);
const deleting = ref(false);

function submitDelete(): void {
    router.delete(destroy.url(props.user.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
}

const restoring = ref(false);

function submitRestore(): void {
    router.post(
        restore.url(props.user.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (restoring.value = true),
            onFinish: () => (restoring.value = false),
        },
    );
}

const personal = computed(() => [
    { label: "To'liq ism", value: props.user.fullName, icon: UserRound },
    { label: 'Elektron pochta', value: props.user.email, icon: Mail },
    { label: 'Telefon', value: formatPhone(props.user.phone), icon: Phone },
    { label: 'Shahar', value: props.user.city, icon: MapPin },
]);

const academic = computed(() => [
    { label: 'Tashkilot', value: props.user.organization },
    { label: "Kafedra / bo'lim", value: props.user.department },
    { label: 'Lavozim', value: props.user.position },
    { label: 'Ilmiy daraja', value: props.user.academicDegree },
    { label: 'Ilmiy unvon', value: props.user.academicTitle },
    { label: 'ORCID', value: props.user.orcid },
]);

const hasAcademic = computed(
    () => academic.value.some((item) => item.value) || Boolean(props.user.bio),
);

const stats = computed(() => [
    {
        label: 'Yuborilgan maqolalar',
        value: props.activity ? formatNumber(props.activity.articles) : '—',
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        label: 'Nashr etilgan',
        value: props.activity
            ? formatNumber(props.activity.publishedArticles)
            : '—',
        icon: BookOpenCheck,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        label: "To'lovlar",
        value: props.activity ? formatSum(props.activity.paidTotal) : '—',
        icon: CreditCard,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: "AI so'rovlari",
        value: props.activity ? formatNumber(props.activity.aiRequests) : '—',
        icon: Sparkles,
        tint: 'bg-amber-50 text-amber-600',
    },
]);

const pill: Record<string, string> = {
    new: 'bg-brand-50 text-brand-700',
    reviewing: 'bg-amber-50 text-amber-700',
    revision: 'bg-red-50 text-red-700',
    accepted: 'bg-emerald-50 text-emerald-700',
    published: 'bg-violet-50 text-violet-700',
    other: 'bg-navy-50 text-navy-600',
};

const actionButton =
    'inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-sm font-semibold transition-all hover:-translate-y-px disabled:pointer-events-none disabled:opacity-60';
</script>

<template>
    <Head :title="user.name" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <Link
            :href="index()"
            class="group inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-navy-500 hover:text-brand-700"
        >
            <ArrowLeft
                class="size-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            Foydalanuvchilar ro'yxati
        </Link>

        <!-- Profil sarlavhasi -->
        <section
            class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div class="relative isolate h-28 bg-navy-950 sm:h-32">
                <div
                    class="absolute inset-0 -z-10 bg-girih opacity-[0.07]"
                    aria-hidden="true"
                />
                <div
                    class="absolute -top-20 right-10 -z-10 size-64 rounded-full bg-brand-500/30 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    class="absolute -bottom-24 left-1/3 -z-10 size-56 rounded-full bg-gold-500/15 blur-3xl"
                    aria-hidden="true"
                />
            </div>
            <div
                class="flex flex-col gap-5 px-5 pb-5 sm:px-6 lg:flex-row lg:items-end"
            >
                <div class="-mt-16 shrink-0 self-center lg:self-auto">
                    <AvatarUploader
                        :name="user.name"
                        :url="user.avatarUrl"
                        :store-url="storeAvatar.url(user.id)"
                        :destroy-url="destroyAvatar.url(user.id)"
                        :disabled="!editable"
                        class="[&>div]:hidden [&>p]:hidden"
                    />
                </div>
                <div class="min-w-0 flex-1 text-center lg:pt-4 lg:text-left">
                    <h1
                        class="font-sans text-2xl font-bold tracking-tight text-navy-950"
                    >
                        {{ user.name }}
                    </h1>
                    <p
                        v-if="user.organization || user.position"
                        class="mt-0.5 text-sm text-navy-500"
                    >
                        {{
                            [user.position, user.organization]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                    <div
                        class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5 lg:justify-start"
                    >
                        <UserStatusBadge
                            :is-blocked="user.isBlocked"
                            :is-deleted="user.isDeleted"
                        />
                        <RoleBadges :roles="user.roles" />
                        <span
                            :class="
                                cn(
                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                                    user.isVerified
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                        : 'bg-amber-50 text-amber-700 ring-amber-200',
                                )
                            "
                        >
                            <MailCheck v-if="user.isVerified" class="size-3" />
                            <MailWarning v-else class="size-3" />
                            {{
                                user.isVerified
                                    ? 'Email tasdiqlangan'
                                    : 'Email tasdiqlanmagan'
                            }}
                        </span>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center justify-center gap-2 lg:justify-end"
                >
                    <template v-if="user.isDeleted">
                        <button
                            v-if="user.can.update"
                            type="button"
                            :class="primaryButtonClass"
                            :disabled="restoring"
                            @click="submitRestore"
                        >
                            <RotateCcw class="size-4" />
                            Tiklash
                        </button>
                    </template>
                    <template v-else-if="editable">
                        <Link :href="edit(user.id)" :class="primaryButtonClass">
                            <PenLine class="size-4" />
                            Tahrirlash
                        </Link>
                        <button
                            type="button"
                            :class="
                                cn(
                                    actionButton,
                                    'border-line bg-white text-navy-800 hover:border-brand-300 hover:text-brand-700',
                                )
                            "
                            @click="passwordOpen = true"
                        >
                            <KeyRound class="size-4" />
                            Parol
                        </button>
                        <template v-if="user.can.block">
                            <button
                                v-if="user.isBlocked"
                                type="button"
                                :class="
                                    cn(
                                        actionButton,
                                        'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300',
                                    )
                                "
                                @click="unblockOpen = true"
                            >
                                <LockOpen class="size-4" />
                                Blokdan chiqarish
                            </button>
                            <button
                                v-else
                                type="button"
                                :class="
                                    cn(
                                        actionButton,
                                        'border-amber-200 bg-amber-50 text-amber-800 hover:border-amber-300',
                                    )
                                "
                                @click="blockOpen = true"
                            >
                                <Ban class="size-4" />
                                Bloklash
                            </button>
                        </template>
                        <button
                            v-if="user.can.delete"
                            type="button"
                            :class="
                                cn(
                                    actionButton,
                                    'border-red-200 bg-white text-red-600 hover:bg-red-50',
                                )
                            "
                            @click="deleteOpen = true"
                        >
                            <Trash2 class="size-4" />
                            O'chirish
                        </button>
                    </template>
                </div>
            </div>
        </section>

        <!-- Ogohlantirishlar -->
        <div
            v-if="user.isBlocked && !user.isDeleted"
            class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0" />
            <div>
                <p class="font-semibold">
                    Akkaunt bloklangan<span v-if="user.blockedAt">
                        — {{ formatDateTime(user.blockedAt) }}</span
                    >
                </p>
                <p v-if="user.blockedReason" class="mt-0.5 text-red-700">
                    Sabab: {{ user.blockedReason }}
                </p>
            </div>
        </div>
        <div
            v-if="user.isDeleted"
            class="flex items-start gap-3 rounded-xl border border-navy-200 bg-navy-50 px-4 py-3 text-sm text-navy-700"
        >
            <Trash2 class="mt-0.5 size-4 shrink-0" />
            <p>
                Bu akkaunt o'chirilgan. Foydalanuvchi tizimga kira olmaydi;
                ma'lumotlari saqlangan va akkauntni tiklash mumkin.
            </p>
        </div>

        <!-- Faoliyat -->
        <section
            class="grid grid-cols-2 gap-3 xl:grid-cols-4"
            aria-label="Faoliyat"
        >
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="group flex items-center gap-3 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-16px_rgba(0,36,66,0.35)]"
            >
                <span
                    :class="
                        cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-full transition-transform group-hover:scale-110',
                            stat.tint,
                        )
                    "
                >
                    <component :is="stat.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-xs text-navy-500">{{
                        stat.label
                    }}</span>
                    <span
                        class="block truncate text-lg leading-tight font-bold text-navy-950 tabular-nums"
                        >{{ stat.value }}</span
                    >
                </span>
            </div>
        </section>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="flex min-w-0 flex-col gap-5">
                <SectionCard title="Shaxsiy ma'lumotlar" :icon="UserRound">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div
                            v-for="item in personal"
                            :key="item.label"
                            class="flex gap-3"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#f3f6fa] text-navy-500"
                            >
                                <component :is="item.icon" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <dt class="text-xs text-navy-500">
                                    {{ item.label }}
                                </dt>
                                <dd
                                    class="truncate text-sm font-medium text-navy-900"
                                >
                                    {{ item.value || '—' }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                </SectionCard>

                <SectionCard title="Ilmiy ma'lumotlar" :icon="GraduationCap">
                    <template v-if="hasAcademic">
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            <div
                                v-for="item in academic"
                                :key="item.label"
                                class="border-b border-dashed border-line pb-2"
                            >
                                <dt class="text-xs text-navy-500">
                                    {{ item.label }}
                                </dt>
                                <dd class="text-sm font-medium text-navy-900">
                                    <a
                                        v-if="
                                            item.label === 'ORCID' && item.value
                                        "
                                        :href="`https://orcid.org/${item.value}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-brand-700 hover:underline"
                                        >{{ item.value }}</a
                                    >
                                    <template v-else>{{
                                        item.value || '—'
                                    }}</template>
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-if="user.bio"
                            class="mt-4 rounded-lg bg-[#f8fafc] p-3 text-sm leading-relaxed whitespace-pre-line text-navy-700"
                        >
                            {{ user.bio }}
                        </p>
                    </template>
                    <p
                        v-else
                        class="flex items-center gap-2 text-sm text-navy-500"
                    >
                        <Building2 class="size-4" />
                        Ilmiy ma'lumotlar kiritilmagan
                    </p>
                </SectionCard>

                <SectionCard title="So'nggi maqolalar" :icon="FileText">
                    <ul
                        v-if="articles?.length"
                        class="-my-1 divide-y divide-line"
                    >
                        <li
                            v-for="article in articles"
                            :key="article.id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-md bg-[#f3efe6] text-gold-700"
                            >
                                <FileText class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="line-clamp-1 text-sm font-medium text-navy-900"
                                    >{{ article.title }}</span
                                >
                                <span class="text-xs text-navy-400">{{
                                    formatDate(article.createdAt)
                                }}</span>
                            </span>
                            <span
                                :class="
                                    cn(
                                        'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        pill[article.statusGroup] ?? pill.other,
                                    )
                                "
                            >
                                {{ article.statusLabel }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-navy-500">Maqolalar yo'q</p>
                </SectionCard>
            </div>

            <aside class="flex flex-col gap-5">
                <SectionCard title="Kirish ma'lumotlari" :icon="Clock">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">Oxirgi kirish</dt>
                            <dd class="text-right font-medium text-navy-900">
                                {{
                                    user.lastLoginAt
                                        ? timeAgo(user.lastLoginAt)
                                        : 'Kirmagan'
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">IP manzil</dt>
                            <dd class="font-mono text-xs text-navy-900">
                                {{ user.lastLoginIp ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">Ro'yxatdan o'tgan</dt>
                            <dd class="font-medium text-navy-900 tabular-nums">
                                {{ formatDate(user.createdAt) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-navy-500">Email tasdiqlangan</dt>
                            <dd class="font-medium text-navy-900 tabular-nums">
                                {{
                                    user.emailVerifiedAt
                                        ? formatDate(user.emailVerifiedAt)
                                        : '—'
                                }}
                            </dd>
                        </div>
                    </dl>
                </SectionCard>

                <SectionCard title="Xavfsizlik" :icon="ShieldCheck">
                    <div
                        :class="
                            cn(
                                'flex items-center gap-3 rounded-lg p-3',
                                user.twoFactorEnabled
                                    ? 'bg-emerald-50'
                                    : 'bg-[#f8fafc]',
                            )
                        "
                    >
                        <ShieldCheck
                            v-if="user.twoFactorEnabled"
                            class="size-5 text-emerald-600"
                        />
                        <ShieldOff v-else class="size-5 text-navy-400" />
                        <div>
                            <p class="text-sm font-semibold text-navy-900">
                                Ikki bosqichli himoya
                            </p>
                            <p class="text-xs text-navy-500">
                                {{
                                    user.twoFactorEnabled
                                        ? 'Yoqilgan'
                                        : "O'chirilgan"
                                }}
                            </p>
                        </div>
                    </div>
                    <div v-if="editable" class="mt-3 grid gap-2">
                        <button
                            type="button"
                            :class="
                                cn(
                                    secondaryButtonClass,
                                    'h-9 w-full justify-start',
                                )
                            "
                            @click="passwordOpen = true"
                        >
                            <KeyRound class="size-4 text-brand-600" />
                            Yangi parol o'rnatish
                        </button>
                        <button
                            type="button"
                            :class="
                                cn(
                                    secondaryButtonClass,
                                    'h-9 w-full justify-start',
                                )
                            "
                            @click="resetOpen = true"
                        >
                            <Send class="size-4 text-brand-600" />
                            Tiklash havolasini yuborish
                        </button>
                    </div>
                </SectionCard>
            </aside>
        </div>
    </div>

    <!-- Oynalar -->
    <ActionDialog
        v-model:open="blockOpen"
        :icon="Ban"
        tone="danger"
        title="Foydalanuvchini bloklash"
        :description="`${user.name} tizimdan chiqariladi va qayta kira olmaydi.`"
        confirm-text="Bloklash"
        :processing="blockForm.processing"
        @confirm="submitBlock"
    >
        <FormField
            label="Sabab (ixtiyoriy)"
            for="block-reason"
            :error="blockForm.errors.reason"
        >
            <textarea
                id="block-reason"
                v-model="blockForm.reason"
                rows="3"
                maxlength="500"
                :class="textareaClass"
                placeholder="Masalan: qoidabuzarlik"
            />
        </FormField>
        <p v-if="blockGuardError" class="mt-2 text-xs font-medium text-red-600">
            {{ blockGuardError }}
        </p>
    </ActionDialog>

    <ActionDialog
        v-model:open="unblockOpen"
        :icon="LockOpen"
        title="Blokdan chiqarish"
        :description="`${user.name} yana tizimga kira oladi.`"
        confirm-text="Blokdan chiqarish"
        :processing="unblocking"
        @confirm="submitUnblock"
    />

    <ActionDialog
        v-model:open="passwordOpen"
        :icon="KeyRound"
        title="Yangi parol o'rnatish"
        description="Yangi parolni foydalanuvchiga xavfsiz yo'l bilan yetkazing."
        confirm-text="Saqlash"
        :processing="passwordForm.processing"
        @confirm="submitPassword"
    >
        <div class="grid gap-3">
            <FormField
                label="Yangi parol"
                for="new-password"
                :error="passwordForm.errors.password"
            >
                <PasswordInput
                    id="new-password"
                    v-model="passwordForm.password"
                    :class="inputClass"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
            </FormField>
            <FormField
                label="Parolni takrorlang"
                for="new-password-confirmation"
            >
                <PasswordInput
                    id="new-password-confirmation"
                    v-model="passwordForm.password_confirmation"
                    :class="inputClass"
                    autocomplete="new-password"
                />
            </FormField>
            <button
                type="button"
                class="inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-600"
                @click="generatePassword"
            >
                <Sparkles class="size-3.5" />
                Kuchli parol yaratish
            </button>
        </div>
    </ActionDialog>

    <ActionDialog
        v-model:open="resetOpen"
        :icon="Send"
        title="Parolni tiklash havolasi"
        :description="`${user.email} manziliga parolni tiklash havolasi yuboriladi.`"
        confirm-text="Yuborish"
        :processing="sendingReset"
        @confirm="submitReset"
    />

    <ActionDialog
        v-model:open="deleteOpen"
        :icon="Trash2"
        tone="danger"
        title="Foydalanuvchini o'chirish"
        :description="`${user.name} akkaunti o'chiriladi. Ma'lumotlar saqlanadi va keyin tiklash mumkin.`"
        confirm-text="O'chirish"
        :processing="deleting"
        @confirm="submitDelete"
    />
</template>

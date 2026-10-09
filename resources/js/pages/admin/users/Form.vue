<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    GraduationCap,
    KeyRound,
    LoaderCircle,
    Save,
    ShieldCheck,
    Sparkles,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import AvatarUploader from '@/components/users/AvatarUploader.vue';
import { formatPhone } from '@/lib/format';
import {
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index, show, store, update } from '@/routes/admin/users';
import {
    destroy as destroyAvatar,
    store as storeAvatar,
} from '@/routes/admin/users/avatar';
import type { RoleName, RoleOption, UserDetail } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Foydalanuvchi qo'shish / tahrirlash.
 * Yangi foydalanuvchida rasm forma bilan birga yuboriladi; tahrirlashda — darhol.
 */
const props = defineProps<{
    user: UserDetail | null;
    roleOptions: RoleOption[];
    canGrantSuperAdmin: boolean;
    passwordRules: string;
}>();

const isEdit = computed(() => props.user !== null);
const title = computed(() =>
    props.user ? `${props.user.name} — tahrirlash` : t('Yangi foydalanuvchi'),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Foydalanuvchilar'), href: index() },
        ],
    },
});

const locales = computed(() => usePage().props.locales);

const form = useForm({
    last_name: props.user?.lastName ?? '',
    first_name: props.user?.firstName ?? '',
    middle_name: props.user?.middleName ?? '',
    email: props.user?.email ?? '',
    phone: formatPhone(props.user?.phone),
    locale: props.user?.locale ?? 'uz',
    roles: (props.user?.roles.map((r) => r.value) ?? []) as RoleName[],
    email_verified: props.user ? props.user.isVerified : true,
    position: props.user?.position ?? '',
    organization: props.user?.organization ?? '',
    department: props.user?.department ?? '',
    academic_degree: props.user?.academicDegree ?? '',
    academic_title: props.user?.academicTitle ?? '',
    orcid: props.user?.orcid ?? '',
    city: props.user?.city ?? '',
    bio: props.user?.bio ?? '',
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
});

const staffRoles = computed(() => props.roleOptions.filter((r) => r.staff));
const authorRole = computed(() => props.roleOptions.find((r) => !r.staff));

function toggleRole(role: RoleName): void {
    form.roles = form.roles.includes(role)
        ? form.roles.filter((r) => r !== role)
        : [...form.roles, role];
}

function roleDisabled(role: RoleName): boolean {
    return role === 'super_admin' && !props.canGrantSuperAdmin;
}

// Kuchli tasodifiy parol (harf, raqam, belgi)
function generatePassword(): void {
    const chars =
        'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*?';
    const values = crypto.getRandomValues(new Uint32Array(14));
    const password = Array.from(values, (v) => chars[v % chars.length]).join(
        '',
    );

    form.password = password;
    form.password_confirmation = password;
}

function submit(): void {
    if (props.user) {
        form.transform((data) => {
            const {
                avatar: _avatar,
                password: _p,
                password_confirmation: _pc,
                ...rest
            } = data;

            return rest;
        }).put(update.url(props.user.id), { preserveScroll: true });

        return;
    }

    form.post(store.url(), { forceFormData: true, preserveScroll: true });
}

const fieldClass = (error?: string) =>
    cn(inputClass, error && 'border-red-400 ring-4 ring-red-50');
</script>

<template>
    <Head :title="title" />

    <form
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6"
        novalidate
        @submit.prevent="submit"
    >
        <PageHeader
            :title="
                isEdit
                    ? t('Foydalanuvchini tahrirlash')
                    : t('Yangi foydalanuvchi')
            "
            :description="
                isEdit
                    ? user?.name
                    : t(
                          'Xodim yoki muallif akkauntini yarating va rollarini belgilang',
                      )
            "
        >
            <template #before>
                <Link
                    :href="user ? show(user.id) : index()"
                    class="group mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-navy-500 hover:text-brand-700"
                >
                    <ArrowLeft
                        class="size-3.5 transition-transform group-hover:-translate-x-0.5"
                    />
                    {{ user ? t('Profilga qaytish') : "Ro'yxatga qaytish" }}
                </Link>
            </template>
            <template #actions>
                <Link
                    :href="user ? show(user.id) : index()"
                    :class="secondaryButtonClass"
                >
                    {{ t('Bekor qilish') }}
                </Link>
                <button
                    type="submit"
                    :class="primaryButtonClass"
                    :disabled="form.processing"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <Save v-else class="size-4" />
                    {{ isEdit ? t('Saqlash') : t('Yaratish') }}
                </button>
            </template>
        </PageHeader>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="flex min-w-0 flex-col gap-5">
                <SectionCard
                    :title="t('Shaxsiy ma\'lumotlar')"
                    :description="
                        t(
                            'Ism-familiya ilmiy uslubda ko\'rsatiladi: Karimov M. A.',
                        )
                    "
                    :icon="UserRound"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <FormField
                            :label="t('Familiya')"
                            for="last_name"
                            required
                            :error="form.errors.last_name"
                        >
                            <input
                                id="last_name"
                                v-model="form.last_name"
                                :class="fieldClass(form.errors.last_name)"
                                autocomplete="family-name"
                            />
                        </FormField>
                        <FormField
                            :label="t('Ism')"
                            for="first_name"
                            required
                            :error="form.errors.first_name"
                        >
                            <input
                                id="first_name"
                                v-model="form.first_name"
                                :class="fieldClass(form.errors.first_name)"
                                autocomplete="given-name"
                            />
                        </FormField>
                        <FormField
                            :label="t('Otasining ismi')"
                            for="middle_name"
                            :error="form.errors.middle_name"
                        >
                            <input
                                id="middle_name"
                                v-model="form.middle_name"
                                :class="fieldClass(form.errors.middle_name)"
                            />
                        </FormField>
                        <FormField
                            :label="t('Elektron pochta')"
                            for="email"
                            required
                            :error="form.errors.email"
                            class="sm:col-span-2"
                        >
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :class="fieldClass(form.errors.email)"
                                autocomplete="off"
                            />
                        </FormField>
                        <FormField
                            :label="t('Telefon')"
                            for="phone"
                            :error="form.errors.phone"
                            hint="+998 90 123 45 67"
                        >
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                :class="fieldClass(form.errors.phone)"
                                placeholder="+998"
                            />
                        </FormField>
                        <FormField
                            :label="t('Interfeys tili')"
                            for="locale"
                            :error="form.errors.locale"
                        >
                            <SelectInput id="locale" v-model="form.locale">
                                <option
                                    v-for="l in locales"
                                    :key="l.code"
                                    :value="l.code"
                                >
                                    {{ l.label }}
                                </option>
                            </SelectInput>
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard
                    :title="t('Ilmiy ma\'lumotlar')"
                    :description="
                        t('Ixtiyoriy — muallif va taqrizchilar uchun')
                    "
                    :icon="GraduationCap"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField
                            :label="t('Tashkilot')"
                            for="organization"
                            :error="form.errors.organization"
                            class="sm:col-span-2"
                        >
                            <input
                                id="organization"
                                v-model="form.organization"
                                :class="fieldClass(form.errors.organization)"
                                :placeholder="
                                    t(
                                        'Masalan: O\'zbekiston Milliy universiteti',
                                    )
                                "
                            />
                        </FormField>
                        <FormField
                            :label="t('Kafedra / bo\'lim')"
                            for="department"
                            :error="form.errors.department"
                        >
                            <input
                                id="department"
                                v-model="form.department"
                                :class="fieldClass(form.errors.department)"
                            />
                        </FormField>
                        <FormField
                            :label="t('Lavozim')"
                            for="position"
                            :error="form.errors.position"
                        >
                            <input
                                id="position"
                                v-model="form.position"
                                :class="fieldClass(form.errors.position)"
                            />
                        </FormField>
                        <FormField
                            :label="t('Ilmiy daraja')"
                            for="academic_degree"
                            :error="form.errors.academic_degree"
                        >
                            <input
                                id="academic_degree"
                                v-model="form.academic_degree"
                                :class="fieldClass(form.errors.academic_degree)"
                                :placeholder="t('PhD, DSc')"
                            />
                        </FormField>
                        <FormField
                            :label="t('Ilmiy unvon')"
                            for="academic_title"
                            :error="form.errors.academic_title"
                        >
                            <input
                                id="academic_title"
                                v-model="form.academic_title"
                                :class="fieldClass(form.errors.academic_title)"
                                :placeholder="t('Dotsent, professor')"
                            />
                        </FormField>
                        <FormField
                            label="ORCID"
                            for="orcid"
                            :error="form.errors.orcid"
                            hint="0000-0000-0000-0000"
                        >
                            <input
                                id="orcid"
                                v-model="form.orcid"
                                :class="fieldClass(form.errors.orcid)"
                                placeholder="0000-0000-0000-0000"
                            />
                        </FormField>
                        <FormField
                            :label="t('Shahar')"
                            for="city"
                            :error="form.errors.city"
                        >
                            <input
                                id="city"
                                v-model="form.city"
                                :class="fieldClass(form.errors.city)"
                            />
                        </FormField>
                        <FormField
                            :label="t('Qisqacha ma\'lumot')"
                            for="bio"
                            :error="form.errors.bio"
                            class="sm:col-span-2"
                        >
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                :class="textareaClass"
                                maxlength="2000"
                            />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard
                    v-if="!isEdit"
                    :title="t('Parol')"
                    :description="
                        t(
                            'Foydalanuvchi keyin o\'z profilidan almashtirishi mumkin',
                        )
                    "
                    :icon="KeyRound"
                >
                    <template #actions>
                        <button
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-brand-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:bg-brand-50"
                            @click="generatePassword"
                        >
                            <Sparkles class="size-3.5" />
                            {{ t('Parol yaratish') }}
                        </button>
                    </template>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField
                            :label="t('Parol')"
                            for="password"
                            required
                            :error="form.errors.password"
                        >
                            <PasswordInput
                                id="password"
                                v-model="form.password"
                                :class="fieldClass(form.errors.password)"
                                autocomplete="new-password"
                                :passwordrules="passwordRules"
                            />
                        </FormField>
                        <FormField
                            :label="t('Parolni takrorlang')"
                            for="password_confirmation"
                            required
                        >
                            <PasswordInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :class="inputClass"
                                autocomplete="new-password"
                            />
                        </FormField>
                    </div>
                </SectionCard>

                <div class="flex justify-end gap-2">
                    <Link
                        :href="user ? show(user.id) : index()"
                        :class="secondaryButtonClass"
                    >
                        {{ t('Bekor qilish') }}
                    </Link>
                    <button
                        type="submit"
                        :class="primaryButtonClass"
                        :disabled="form.processing"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <Save v-else class="size-4" />
                        {{
                            isEdit
                                ? "O'zgarishlarni saqlash"
                                : t('Foydalanuvchini yaratish')
                        }}
                    </button>
                </div>
            </div>

            <aside class="flex flex-col gap-5">
                <SectionCard
                    :title="t('Profil rasmi')"
                    class="xl:sticky xl:top-[calc(var(--app-header-h,4rem)+1.25rem)] xl:self-start"
                >
                    <AvatarUploader
                        v-if="user"
                        :name="`${form.last_name} ${form.first_name}`.trim()"
                        :url="user.avatarUrl"
                        :store-url="storeAvatar.url(user.id)"
                        :destroy-url="destroyAvatar.url(user.id)"
                    />
                    <AvatarUploader
                        v-else
                        v-model="form.avatar"
                        mode="deferred"
                        :name="`${form.last_name} ${form.first_name}`.trim()"
                        :error="form.errors.avatar"
                    />
                </SectionCard>

                <SectionCard
                    :title="t('Rollar')"
                    :description="t('Bir nechta rol berish mumkin')"
                    :icon="ShieldCheck"
                >
                    <p
                        class="mb-2 text-[11px] font-semibold tracking-wider text-navy-400 uppercase"
                    >
                        {{ t('Tahririyat xodimlari') }}
                    </p>
                    <div class="grid gap-1.5">
                        <label
                            v-for="role in staffRoles"
                            :key="role.value"
                            :class="
                                cn(
                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-all',
                                    form.roles.includes(role.value)
                                        ? 'border-brand-300 bg-brand-50/60 text-navy-950'
                                        : 'border-line text-navy-700 hover:border-brand-200 hover:bg-[#f8fafc]',
                                    roleDisabled(role.value) &&
                                        'cursor-not-allowed opacity-50',
                                )
                            "
                            :title="
                                roleDisabled(role.value)
                                    ? t('Faqat Bosh administrator bera oladi')
                                    : undefined
                            "
                        >
                            <input
                                type="checkbox"
                                class="size-4 rounded border-navy-300 accent-brand-600"
                                :checked="form.roles.includes(role.value)"
                                :disabled="roleDisabled(role.value)"
                                @change="toggleRole(role.value)"
                            />
                            <span class="font-medium">{{ role.label }}</span>
                        </label>
                    </div>
                    <template v-if="authorRole">
                        <p
                            class="mt-4 mb-2 text-[11px] font-semibold tracking-wider text-navy-400 uppercase"
                        >
                            {{ t('Mualliflar') }}
                        </p>
                        <label
                            :class="
                                cn(
                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-all',
                                    form.roles.includes(authorRole.value)
                                        ? 'border-gold-300 bg-gold-100/60 text-navy-950'
                                        : 'border-line text-navy-700 hover:border-gold-300 hover:bg-[#fdfaf3]',
                                )
                            "
                        >
                            <input
                                type="checkbox"
                                class="size-4 rounded border-navy-300 accent-gold-600"
                                :checked="form.roles.includes(authorRole.value)"
                                @change="toggleRole(authorRole.value)"
                            />
                            <span class="font-medium">{{
                                authorRole.label
                            }}</span>
                        </label>
                    </template>
                    <p
                        v-if="form.errors.roles"
                        class="mt-2 text-xs font-medium text-red-600"
                    >
                        {{ form.errors.roles }}
                    </p>
                </SectionCard>

                <SectionCard :title="t('Holat')">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            v-model="form.email_verified"
                            type="checkbox"
                            class="mt-0.5 size-4 rounded border-navy-300 accent-brand-600"
                        />
                        <span>
                            <span
                                class="block text-sm font-semibold text-navy-900"
                                >{{ t('Email tasdiqlangan') }}</span
                            >
                            <span class="block text-xs text-navy-500">
                                {{
                                    t(
                                        "Belgilanmasa, foydalanuvchi kirgach emailini tasdiqlashi kerak bo'ladi",
                                    )
                                }}
                            </span>
                        </span>
                    </label>
                </SectionCard>
            </aside>
        </div>
    </form>
</template>

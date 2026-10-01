<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    GraduationCap,
    LoaderCircle,
    MailWarning,
    Save,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import AvatarUploader from '@/components/users/AvatarUploader.vue';
import RoleBadges from '@/components/users/RoleBadges.vue';
import { formatDate, formatPhone } from '@/lib/format';
import {
    inputClass,
    primaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { edit, update } from '@/routes/profile';
import {
    destroy as destroyAvatar,
    store as storeAvatar,
} from '@/routes/profile/avatar';
import { send } from '@/routes/verification';
import type { UserDetail } from '@/types';

/**
 * Shaxsiy profil: rasm, shaxsiy va ilmiy ma'lumotlar, akkauntni o'chirish.
 */
const props = defineProps<{
    profile: UserDetail;
    mustVerifyEmail: boolean;
    status?: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Profil sozlamalari', href: edit() }],
    },
});

const locales = computed(() => usePage().props.locales);

const form = useForm({
    last_name: props.profile.lastName,
    first_name: props.profile.firstName,
    middle_name: props.profile.middleName ?? '',
    email: props.profile.email,
    phone: formatPhone(props.profile.phone),
    locale: props.profile.locale,
    position: props.profile.position ?? '',
    organization: props.profile.organization ?? '',
    department: props.profile.department ?? '',
    academic_degree: props.profile.academicDegree ?? '',
    academic_title: props.profile.academicTitle ?? '',
    orcid: props.profile.orcid ?? '',
    city: props.profile.city ?? '',
    bio: props.profile.bio ?? '',
});

function submit(): void {
    form.patch(update.url(), { preserveScroll: true });
}

const fieldClass = (error?: string) =>
    cn(inputClass, error && 'border-red-400 ring-4 ring-red-50');
</script>

<template>
    <Head title="Profil sozlamalari" />

    <div class="grid items-start gap-5 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="flex flex-col gap-5 xl:sticky xl:top-5">
            <SectionCard title="Profil rasmi">
                <AvatarUploader
                    :name="profile.name"
                    :url="profile.avatarUrl"
                    :store-url="storeAvatar.url()"
                    :destroy-url="destroyAvatar.url()"
                />
            </SectionCard>

            <SectionCard title="Akkaunt">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="mb-1 text-xs text-navy-500">Rollar</dt>
                        <dd><RoleBadges :roles="profile.roles" /></dd>
                    </div>
                    <div class="flex items-center gap-2 text-navy-600">
                        <CalendarDays class="size-4 text-navy-400" />
                        <span
                            >Ro'yxatdan o'tgan:
                            <strong class="font-semibold text-navy-900">{{
                                formatDate(profile.createdAt)
                            }}</strong></span
                        >
                    </div>
                </dl>
            </SectionCard>
        </aside>

        <div class="flex min-w-0 flex-col gap-5">
            <form
                class="flex flex-col gap-5"
                novalidate
                @submit.prevent="submit"
            >
                <div
                    v-if="mustVerifyEmail && !profile.isVerified"
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                >
                    <MailWarning class="size-5 shrink-0 text-amber-600" />
                    <p class="min-w-0 flex-1">
                        Elektron pochtangiz tasdiqlanmagan.
                        <span
                            v-if="status === 'verification-link-sent'"
                            class="font-semibold text-emerald-700"
                        >
                            Yangi tasdiqlash havolasi yuborildi.
                        </span>
                    </p>
                    <Link
                        :href="send()"
                        as="button"
                        class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 shadow-sm ring-1 ring-amber-200 transition hover:bg-amber-100"
                    >
                        Havolani qayta yuborish
                    </Link>
                </div>

                <SectionCard
                    title="Shaxsiy ma'lumotlar"
                    description="Ism-familiyangiz maqolalarda ilmiy uslubda ko'rsatiladi"
                    :icon="UserRound"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <FormField
                            label="Familiya"
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
                            label="Ism"
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
                            label="Otasining ismi"
                            for="middle_name"
                            :error="form.errors.middle_name"
                        >
                            <input
                                id="middle_name"
                                v-model="form.middle_name"
                                :class="fieldClass(form.errors.middle_name)"
                                autocomplete="additional-name"
                            />
                        </FormField>
                        <FormField
                            label="Elektron pochta"
                            for="email"
                            required
                            :error="form.errors.email"
                            hint="O'zgartirsangiz, yangi manzilni tasdiqlashingiz kerak bo'ladi"
                            class="sm:col-span-2"
                        >
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :class="fieldClass(form.errors.email)"
                                autocomplete="email"
                            />
                        </FormField>
                        <FormField
                            label="Telefon"
                            for="phone"
                            :error="form.errors.phone"
                        >
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                :class="fieldClass(form.errors.phone)"
                                autocomplete="tel"
                                placeholder="+998"
                            />
                        </FormField>
                        <FormField
                            label="Interfeys tili"
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
                    title="Ilmiy ma'lumotlar"
                    description="Maqola yuborishda va mualliflar sahifasida ishlatiladi"
                    :icon="GraduationCap"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Tashkilot"
                            for="organization"
                            :error="form.errors.organization"
                            class="sm:col-span-2"
                        >
                            <input
                                id="organization"
                                v-model="form.organization"
                                :class="fieldClass(form.errors.organization)"
                                autocomplete="organization"
                            />
                        </FormField>
                        <FormField
                            label="Kafedra / bo'lim"
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
                            label="Lavozim"
                            for="position"
                            :error="form.errors.position"
                        >
                            <input
                                id="position"
                                v-model="form.position"
                                :class="fieldClass(form.errors.position)"
                                autocomplete="organization-title"
                            />
                        </FormField>
                        <FormField
                            label="Ilmiy daraja"
                            for="academic_degree"
                            :error="form.errors.academic_degree"
                        >
                            <input
                                id="academic_degree"
                                v-model="form.academic_degree"
                                :class="fieldClass(form.errors.academic_degree)"
                                placeholder="PhD, DSc"
                            />
                        </FormField>
                        <FormField
                            label="Ilmiy unvon"
                            for="academic_title"
                            :error="form.errors.academic_title"
                        >
                            <input
                                id="academic_title"
                                v-model="form.academic_title"
                                :class="fieldClass(form.errors.academic_title)"
                                placeholder="Dotsent, professor"
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
                            label="Shahar"
                            for="city"
                            :error="form.errors.city"
                        >
                            <input
                                id="city"
                                v-model="form.city"
                                :class="fieldClass(form.errors.city)"
                                autocomplete="address-level2"
                            />
                        </FormField>
                        <FormField
                            label="Qisqacha ma'lumot"
                            for="bio"
                            :error="form.errors.bio"
                            class="sm:col-span-2"
                        >
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                maxlength="2000"
                                :class="textareaClass"
                            />
                        </FormField>
                    </div>
                </SectionCard>

                <div class="flex items-center justify-end gap-3">
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-emerald-600"
                    >
                        Saqlandi
                    </p>
                    <button
                        type="submit"
                        :class="primaryButtonClass"
                        :disabled="form.processing"
                        data-test="update-profile-button"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <Save v-else class="size-4" />
                        Saqlash
                    </button>
                </div>
            </form>

            <DeleteUser />
        </div>
    </div>
</template>

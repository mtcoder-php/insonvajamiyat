<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, LoaderCircle, Save, ShieldCheck } from '@lucide/vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SocialAccountsCard from '@/components/settings/SocialAccountsCard.vue';
import { inputClass, primaryButtonClass } from '@/lib/formStyles';
import { edit } from '@/routes/security';
import { t } from '@/lib/i18n';
import type { SocialAccountItem } from '@/types';

/**
 * Xavfsizlik: parol, bog'langan akkauntlar (Google / ORCID) va ikki bosqichli himoya (2FA).
 * Google / ORCID orqali ro'yxatdan o'tgan foydalanuvchida parol bo'lmasligi mumkin —
 * u holda parol joriy parolsiz o'rnatiladi, 2FA esa paroldan keyin ochiladi.
 */
// oxfmt-ignore
type Props = {
    passwordRules: string;
    hasPassword: boolean;
    socialAccounts: SocialAccountItem[];
} &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Xavfsizlik', href: edit() }],
    },
});
</script>

<template>
    <Head :title="t('Xavfsizlik')" />

    <div class="grid items-start gap-5 xl:grid-cols-2">
        <SectionCard
            :title="
                hasPassword ? t('Parolni almashtirish') : t('Parol o\'rnatish')
            "
            :description="
                hasPassword
                    ? t(
                          'Kamida 12 belgi: katta-kichik harf, raqam va belgi aralash bo\'lishi kerak',
                      )
                    : t(
                          'Siz Google / ORCID orqali kirasiz. Parol o\'rnatsangiz, email va parol bilan ham kira olasiz.',
                      )
            "
            :icon="KeyRound"
        >
            <Form
                v-bind="SecurityController.update.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="grid gap-4"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <FormField
                    v-if="hasPassword"
                    :label="t('Joriy parol')"
                    for="current_password"
                    :error="errors.current_password"
                >
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        :class="inputClass"
                        autocomplete="current-password"
                    />
                </FormField>
                <FormField
                    :label="t('Yangi parol')"
                    for="password"
                    :error="errors.password"
                >
                    <PasswordInput
                        id="password"
                        name="password"
                        :class="inputClass"
                        autocomplete="new-password"
                        :passwordrules="props.passwordRules"
                    />
                </FormField>
                <FormField
                    :label="t('Yangi parolni takrorlang')"
                    for="password_confirmation"
                    :error="errors.password_confirmation"
                >
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        :class="inputClass"
                        autocomplete="new-password"
                        :passwordrules="props.passwordRules"
                    />
                </FormField>
                <div class="flex items-center justify-end gap-3">
                    <p
                        v-if="recentlySuccessful"
                        class="text-sm font-medium text-emerald-600"
                    >
                        {{ t('Saqlandi') }}
                    </p>
                    <button
                        type="submit"
                        :class="primaryButtonClass"
                        :disabled="processing"
                        data-test="update-password-button"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="size-4 animate-spin"
                        />
                        <Save v-else class="size-4" />
                        {{
                            hasPassword
                                ? t('Parolni saqlash')
                                : t("Parolni o'rnatish")
                        }}
                    </button>
                </div>
            </Form>
        </SectionCard>

        <SocialAccountsCard
            v-if="socialAccounts.length"
            :accounts="socialAccounts"
            :has-password="hasPassword"
        />

        <SectionCard
            v-if="canManageTwoFactor"
            :title="t('Ikki bosqichli himoya (2FA)')"
            :description="
                t('Akkauntingizni parol o\'g\'irlanishidan himoya qiladi')
            "
            :icon="ShieldCheck"
        >
            <p
                v-if="!hasPassword"
                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                {{
                    t(
                        "Ikki bosqichli himoyani yoqish uchun avval parol o'rnating.",
                    )
                }}
            </p>
            <ManageTwoFactor
                v-else
                :canManageTwoFactor="canManageTwoFactor"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />
        </SectionCard>
    </div>
</template>

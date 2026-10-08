<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BadgeCheck, Mail, Phone, User, UserPlus } from '@lucide/vue';
import SocialProviderIcon from '@/components/auth/SocialProviderIcon.vue';
import InputIcon from '@/components/form/InputIcon.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { t } from '@/lib/i18n';
import { login } from '@/routes';
import { store } from '@/routes/social/register';
import type { SocialProviderOption } from '@/types';

/**
 * Google / ORCID orqali birinchi marta kirgan muallif: ism-familiya, telefon va
 * (provayder tasdiqlamagan bo'lsa) email kiritiladi — parol shart emas.
 * Backend: App\Http\Controllers\Auth\SocialRegisterController.
 */
defineOptions({
    layout: {
        title: "Ro'yxatdan o'tishni yakunlang",
        description:
            "Bir nechta ma'lumotni tasdiqlang — hisobingiz darhol tayyor bo'ladi",
        wide: true,
    },
});

defineProps<{
    provider: SocialProviderOption;
    lastName: string;
    firstName: string;
    email: string;
    /** Provayder tasdiqlagan email — o'zgartirilmaydi */
    emailLocked: boolean;
    orcid: string | null;
}>();
</script>

<template>
    <Head :title="t('Ro\'yxatdan o\'tishni yakunlang')" />

    <div
        class="mb-6 flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/60 px-4 py-3"
    >
        <SocialProviderIcon :provider="provider" class="size-8" />
        <div class="min-w-0 text-sm">
            <p class="font-semibold text-navy-900">
                {{
                    t(':provider akkauntingiz tasdiqlandi', {
                        provider: provider.label,
                    })
                }}
            </p>
            <p class="truncate text-navy-500">
                <template v-if="orcid">ORCID iD: {{ orcid }}</template>
                <template v-else-if="email">{{ email }}</template>
                <template v-else>{{
                    t("Hisob yaratish uchun ma'lumotlarni to'ldiring")
                }}</template>
            </p>
        </div>
    </div>

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid content-start gap-2">
                <Label for="first_name">
                    {{ t('Ism') }} <span class="text-danger">*</span>
                </Label>
                <InputIcon :icon="User">
                    <Input
                        id="first_name"
                        type="text"
                        required
                        v-focus
                        autocomplete="given-name"
                        name="first_name"
                        :default-value="firstName"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.first_name" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="last_name">
                    {{ t('Familiya') }} <span class="text-danger">*</span>
                </Label>
                <InputIcon :icon="User">
                    <Input
                        id="last_name"
                        type="text"
                        required
                        autocomplete="family-name"
                        name="last_name"
                        :default-value="lastName"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.last_name" />
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid content-start gap-2">
                <Label for="email">
                    {{ t('Elektron pochta') }}
                    <span class="text-danger">*</span>
                </Label>
                <InputIcon :icon="Mail">
                    <Input
                        v-if="emailLocked"
                        id="email"
                        type="email"
                        readonly
                        :default-value="email"
                        class="h-11 cursor-default bg-navy-50/60 pr-10 pl-10 text-navy-700"
                    />
                    <Input
                        v-else
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        name="email"
                        :default-value="email"
                        placeholder="email@example.com"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <p
                    v-if="emailLocked"
                    class="flex items-center gap-1 text-xs font-medium text-emerald-700"
                >
                    <BadgeCheck class="size-3.5" />
                    {{
                        t(':provider tomonidan tasdiqlangan', {
                            provider: provider.label,
                        })
                    }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    {{ t('Shu manzilga tasdiqlash havolasi yuboriladi') }}
                </p>
                <InputError :message="errors.email" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="phone">
                    {{ t('Telefon raqam') }}
                    <span class="text-danger">*</span>
                </Label>
                <InputIcon :icon="Phone">
                    <Input
                        id="phone"
                        type="tel"
                        required
                        autocomplete="tel"
                        inputmode="tel"
                        name="phone"
                        placeholder="+998 90 123 45 67"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.phone" />
            </div>
        </div>

        <p class="text-xs text-muted-foreground">
            {{
                t(
                    "Parol shart emas — keyingi safar ham shu tugma orqali kirasiz. Xohlasangiz, keyin «Sozlamalar → Xavfsizlik» bo'limida parol o'rnatishingiz mumkin.",
                )
            }}
        </p>

        <Button
            type="submit"
            size="lg"
            class="h-11 w-full text-base"
            :disabled="processing"
            data-test="social-register-button"
        >
            <Spinner v-if="processing" />
            <UserPlus v-else class="size-4" />
            {{ t('Hisob yaratish') }}
        </Button>

        <p class="text-center text-sm text-muted-foreground">
            {{ t('Hisobingiz bormi?') }}
            <TextLink :href="login()">{{ t('Tizimga kirish') }}</TextLink>
        </p>
    </Form>
</template>

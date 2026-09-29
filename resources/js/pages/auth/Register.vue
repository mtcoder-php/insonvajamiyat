<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, Mail, Phone, User, UserPlus } from '@lucide/vue';
import InputIcon from '@/components/form/InputIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

/**
 * Ro'yxatdan o'tish (dizayn: register_login.png, "Ro'yxatdan o'tish" kartasi).
 * Faqat mualliflar uchun — xodim hisoblarini Super Admin yaratadi.
 * Tashkilot, ilmiy daraja va yo'nalishlar keyin profil onboarding'ida to'ldiriladi.
 */
defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: "Ro'yxatdan o'tish",
        description:
            'Muallif sifatida hisob yarating va maqolalaringizni yuboring',
        wide: true,
    },
});
</script>

<template>
    <Head title="Ro'yxatdan o'tish" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <fieldset class="grid gap-5">
            <legend class="mb-4 font-serif text-lg font-semibold text-navy-950">
                Shaxsiy ma'lumotlar
            </legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">
                        Ism <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="User">
                        <Input
                            id="first_name"
                            type="text"
                            required
                            v-focus
                            :tabindex="1"
                            autocomplete="given-name"
                            name="first_name"
                            placeholder="Muxtor"
                            class="h-11 pl-10"
                        />
                    </InputIcon>
                    <InputError :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">
                        Familiya <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="User">
                        <Input
                            id="last_name"
                            type="text"
                            required
                            :tabindex="2"
                            autocomplete="family-name"
                            name="last_name"
                            placeholder="Karimov"
                            class="h-11 pl-10"
                        />
                    </InputIcon>
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="email">
                        Elektron pochta <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="Mail">
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="3"
                            autocomplete="email"
                            name="email"
                            placeholder="email@example.com"
                            class="h-11 pl-10"
                        />
                    </InputIcon>
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">
                        Telefon raqam <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="Phone">
                        <Input
                            id="phone"
                            type="tel"
                            required
                            :tabindex="4"
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

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="password">
                        Parol <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="Lock">
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="5"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Kamida 8 ta belgi"
                            :passwordrules="passwordRules"
                            class="h-11 pl-10"
                        />
                    </InputIcon>
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        Parolni tasdiqlang <span class="text-danger">*</span>
                    </Label>
                    <InputIcon :icon="Lock">
                        <PasswordInput
                            id="password_confirmation"
                            required
                            :tabindex="6"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Parolni qayta kiriting"
                            :passwordrules="passwordRules"
                            class="h-11 pl-10"
                        />
                    </InputIcon>
                    <InputError :message="errors.password_confirmation" />
                </div>
            </div>
        </fieldset>

        <p class="text-xs text-muted-foreground">
            Ro'yxatdan o'tgach, elektron pochtangizga tasdiqlash havolasi
            yuboriladi. Tashkilot va ilmiy daraja kabi ma'lumotlarni keyin
            profilingizda to'ldirasiz.
        </p>

        <Button
            type="submit"
            size="lg"
            class="h-11 w-full text-base"
            tabindex="7"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            <UserPlus v-else class="size-4" />
            Hisob yaratish
        </Button>

        <p class="text-center text-sm text-muted-foreground">
            Hisobingiz bormi?
            <TextLink :href="login()" :tabindex="8">Tizimga kirish</TextLink>
        </p>
    </Form>
</template>

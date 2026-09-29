<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
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
 * Ro'yxatdan o'tish — faqat mualliflar uchun (TZ 4.1.2).
 * Xodimlar (muharrir, taqrizchi, ...) hisobini faqat Super Admin yaratadi.
 */
defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: "Ro'yxatdan o'tish",
        description:
            'Muallif sifatida hisob yarating va maqolalaringizni yuboring',
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
        <div class="grid gap-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="last_name">Familiya</Label>
                    <Input
                        id="last_name"
                        type="text"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="family-name"
                        name="last_name"
                        placeholder="Karimov"
                    />
                    <InputError :message="errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="first_name">Ism</Label>
                    <Input
                        id="first_name"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="given-name"
                        name="first_name"
                        placeholder="Muxtor"
                    />
                    <InputError :message="errors.first_name" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="email">Elektron pochta</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Parol</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Parol"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Parolni tasdiqlang</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Parolni qayta kiriting"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Hisob yaratish
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Hisobingiz bormi?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Kirish</TextLink
            >
        </div>
    </Form>
</template>

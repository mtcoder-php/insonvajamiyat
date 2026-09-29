<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight, Lock, LogIn, Mail } from '@lucide/vue';
import InputIcon from '@/components/form/InputIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

/**
 * Tizimga kirish (dizayn: register_login.png, "Tizimga kirish" kartasi).
 * Mualliflar ham, xodimlar ham shu yerdan kiradi — rolga qarab
 * /dashboard avtomatik yo'naltiradi (kabinet yoki admin panel).
 */
defineOptions({
    layout: {
        title: 'Tizimga kirish',
        description: 'Hisob qaydnomangiz orqali tizimga kiring',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Kirish" />

    <div
        v-if="status"
        class="mb-6 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm font-medium text-success-ink"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email">Elektron pochta</Label>
                <InputIcon :icon="Mail">
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Parol</Label>
                <InputIcon :icon="Lock">
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Parolingizni kiriting"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <Label
                    for="remember"
                    class="flex items-center gap-2.5 font-normal"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Meni eslab qolish</span>
                </Label>

                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-sm"
                    :tabindex="5"
                >
                    Parolni unutdingizmi?
                </TextLink>
            </div>

            <Button
                type="submit"
                size="lg"
                class="h-11 w-full text-base"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                <LogIn v-else class="size-4" />
                Tizimga kirish
            </Button>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            Hali akkauntingiz yo'qmi?
            <TextLink
                :href="register()"
                :tabindex="6"
                class="inline-flex items-center gap-1"
            >
                Ro'yxatdan o'tish
                <ArrowRight class="size-3.5" />
            </TextLink>
        </p>
    </Form>
</template>

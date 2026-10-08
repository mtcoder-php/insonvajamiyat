<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import { t } from '@/lib/i18n';

defineOptions({
    layout: {
        title: 'Parolni tasdiqlang',
        description:
            "Bu himoyalangan bo'lim. Davom etish uchun parolingizni qayta kiriting.",
    },
});
</script>

<template>
    <Head :title="t('Parolni tasdiqlash')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">{{ t('Parol') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block h-11 w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="h-11 w-full text-base"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('Tasdiqlash') }}
                </Button>
            </div>
        </div>
    </Form>
</template>

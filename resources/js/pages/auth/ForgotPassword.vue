<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail, Send } from '@lucide/vue';
import InputIcon from '@/components/form/InputIcon.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { t } from '@/lib/i18n';

defineOptions({
    layout: {
        title: 'Parolni tiklash',
        description:
            'Elektron pochtangizni kiriting — parolni tiklash havolasini yuboramiz',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('Parolni tiklash')" />

    <div
        v-if="status"
        class="mb-6 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm font-medium text-success-ink"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">{{ t('Elektron pochta') }}</Label>
                <InputIcon :icon="Mail">
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-focus
                        placeholder="email@example.com"
                        class="h-11 pl-10"
                    />
                </InputIcon>
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    size="lg"
                    class="h-11 w-full text-base"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    <Send v-else class="size-4" />
                    {{ t('Tiklash havolasini yuborish') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('Yoki') }}</span>
            <TextLink :href="login()">{{ t('tizimga kirish') }}</TextLink>
        </div>
    </div>
</template>

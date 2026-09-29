<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Elektron pochtani tasdiqlang',
        description:
            "Pochtangizga yuborilgan havolani bosib, manzilingizni tasdiqlang. Xat kelmagan bo'lsa, 'Spam' papkasini ham tekshiring.",
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Pochtani tasdiqlash" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-6 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm font-medium text-success-ink"
    >
        Ro'yxatdan o'tishda ko'rsatilgan manzilga yangi tasdiqlash havolasi
        yuborildi.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            Havolani qayta yuborish
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Tizimdan chiqish
        </TextLink>
    </Form>
</template>

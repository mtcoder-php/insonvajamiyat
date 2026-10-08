<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { KeyRound, TriangleAlert, Trash2 } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    dangerButtonClass,
    inputClass,
    secondaryButtonClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';
import { edit as securityEdit } from '@/routes/security';

/**
 * Akkauntni o'chirish (parol bilan tasdiqlanadi). Akkaunt yumshoq o'chiriladi —
 * administrator kerak bo'lsa tiklashi mumkin.
 * Parolsiz (Google / ORCID) foydalanuvchi avval Xavfsizlik bo'limida parol o'rnatadi.
 */
withDefaults(defineProps<{ hasPassword?: boolean }>(), { hasPassword: true });

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <section
        class="rounded-xl border border-red-200 bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <div class="flex flex-wrap items-center gap-4 p-5">
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"
            >
                <TriangleAlert class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t("Akkauntni o'chirish") }}
                </h2>
                <p class="mt-0.5 text-xs text-navy-500">
                    {{
                        t(
                            "Akkauntingiz o'chiriladi va tizimga kira olmaysiz. Ehtiyot bo'ling.",
                        )
                    }}
                </p>
            </div>
            <Link
                v-if="!hasPassword"
                :href="securityEdit()"
                :class="cn(secondaryButtonClass, 'h-9')"
            >
                <KeyRound class="size-4" />
                {{ t("Avval parol o'rnating") }}
            </Link>
            <Dialog v-else>
                <DialogTrigger as-child>
                    <button
                        type="button"
                        :class="cn(dangerButtonClass, 'h-9')"
                        data-test="delete-user-button"
                    >
                        <Trash2 class="size-4" />
                        {{ t("Akkauntni o'chirish") }}
                    </button>
                </DialogTrigger>
                <DialogContent
                    class="gap-0 overflow-hidden border-line bg-white p-0 text-navy-900 sm:max-w-md"
                >
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <div class="flex gap-4 p-6">
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"
                            >
                                <Trash2 class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <DialogTitle
                                    class="font-sans text-base font-bold text-navy-950"
                                >
                                    {{ t("Akkauntni o'chirmoqchimisiz?") }}
                                </DialogTitle>
                                <DialogDescription
                                    class="mt-1 text-sm leading-relaxed text-navy-600"
                                >
                                    {{
                                        t(
                                            'Tasdiqlash uchun parolingizni kiriting.',
                                        )
                                    }}
                                </DialogDescription>
                                <div class="mt-4 grid gap-1.5">
                                    <label
                                        for="delete-password"
                                        class="sr-only"
                                        >{{ t('Parol') }}</label
                                    >
                                    <PasswordInput
                                        id="delete-password"
                                        name="password"
                                        ref="passwordInput"
                                        :class="inputClass"
                                        :placeholder="t('Parol')"
                                    />
                                    <InputError :message="errors.password" />
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex justify-end gap-2 border-t border-line bg-[#f8fafc] px-6 py-3"
                        >
                            <DialogClose as-child>
                                <button
                                    type="button"
                                    :class="cn(secondaryButtonClass, 'h-9')"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('Bekor qilish') }}
                                </button>
                            </DialogClose>
                            <button
                                type="submit"
                                :class="cn(dangerButtonClass, 'h-9')"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t("O'chirish") }}
                            </button>
                        </div>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </section>
</template>

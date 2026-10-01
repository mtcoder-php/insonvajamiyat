<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Lock, MessageSquareText, Send } from '@lucide/vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { EditorialNote } from '@/types';

/**
 * Tahririyat izohlari — ichki yozuvlar (muallifga ko'rinmaydi).
 * Muharrir ish joyi va nashr jarayoni sahifalarida ishlatiladi.
 */
const props = withDefaults(
    defineProps<{
        notes: EditorialNote[];
        url: string;
        /** Partial reload uchun prop nomi (masalan, 'selected' yoki 'article') */
        reload: string;
        title?: string;
    }>(),
    { title: 'Muharrir izohlari' },
);

const form = useForm({ body: '' });

function submit(): void {
    form.post(props.url, {
        preserveScroll: true,
        only: [props.reload],
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <section>
        <h3
            class="mb-3 flex items-center gap-2 text-[13px] font-bold text-navy-950"
        >
            <MessageSquareText class="size-4 text-brand-600" />
            {{ title }}
            <span
                class="inline-flex items-center gap-1 rounded bg-navy-50 px-1.5 py-0.5 text-[10px] font-medium text-navy-500"
            >
                <Lock class="size-3" />
                muallifga ko'rinmaydi
            </span>
        </h3>

        <ul v-if="notes.length" class="mb-3 grid gap-2">
            <li
                v-for="note in notes"
                :key="note.id"
                class="flex gap-3 rounded-lg border border-line bg-[#f8fafd] p-3"
            >
                <UserAvatar :name="note.author" :url="note.avatar" size="sm" />
                <div class="min-w-0 flex-1">
                    <p
                        class="flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <span class="text-[13px] font-semibold text-navy-900">
                            {{ note.author }}
                            <span
                                v-if="note.role"
                                class="font-normal text-navy-500"
                                >({{ note.role }})</span
                            >
                        </span>
                        <span class="text-[11px] text-navy-400 tabular-nums">
                            {{ formatDate(note.createdAt) }}
                            {{ formatTime(note.createdAt) }}
                        </span>
                    </p>
                    <p
                        class="mt-1 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                    >
                        {{ note.body }}
                    </p>
                </div>
            </li>
        </ul>

        <form class="flex items-end gap-2" @submit.prevent="submit">
            <div class="min-w-0 flex-1">
                <textarea
                    v-model="form.body"
                    rows="2"
                    maxlength="2000"
                    placeholder="Izoh yozish..."
                    :aria-invalid="!!form.errors.body"
                    :class="cn(textareaClass, 'min-h-12')"
                    @keydown.ctrl.enter.prevent="submit"
                    @keydown.meta.enter.prevent="submit"
                />
                <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">
                    {{ form.errors.body }}
                </p>
            </div>
            <button
                type="submit"
                class="group inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-700 disabled:opacity-60"
                :disabled="form.processing || !form.body.trim()"
            >
                <Send
                    class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                />
                Yuborish
            </button>
        </form>
    </section>
</template>

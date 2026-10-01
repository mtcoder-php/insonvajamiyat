<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Building2,
    CheckCheck,
    LoaderCircle,
    MessagesSquare,
    Paperclip,
    Send,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ThreadMessage } from '@/types';

/**
 * Muallif ↔ tahririyat yozishmasi (kabinet va admin panel uchun umumiy).
 * O'z xabarlari o'ngda, qarshi tomon xabarlari chapda; Ctrl+Enter — yuborish.
 */
const props = withDefaults(
    defineProps<{
        messages: ThreadMessage[];
        sendUrl: string | null;
        emptyText?: string;
        placeholder?: string;
    }>(),
    {
        emptyText: "Hozircha xabarlar yo'q.",
        placeholder: 'Xabaringizni yozing...',
    },
);

const MAX = 5000;

const form = useForm<{ body: string; attachment: File | null }>({
    body: '',
    attachment: null,
});

const errors = computed(() => form.errors as Record<string, string>);
const fileInput = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);

function scrollToBottom(): void {
    void nextTick(() => {
        if (list.value) {
            list.value.scrollTop = list.value.scrollHeight;
        }
    });
}

onMounted(scrollToBottom);
watch(() => props.messages.length, scrollToBottom);

function pickFile(event: Event): void {
    form.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function clearFile(): void {
    form.attachment = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function send(): void {
    if (!props.sendUrl || form.body.trim().length < 2 || form.processing) {
        return;
    }

    form.post(props.sendUrl, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            clearFile();
        },
    });
}

// Sana ajratgichi: kun o'zgarganda ko'rsatiladi
const dayOf = (iso: string): string => iso.slice(0, 10);
const showDay = (index: number): boolean =>
    index === 0 ||
    dayOf(props.messages[index].createdAt) !==
        dayOf(props.messages[index - 1].createdAt);
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            ref="list"
            class="flex max-h-[28rem] min-h-40 flex-col gap-3 overflow-y-auto rounded-xl bg-[#f5f8fc] p-3 sm:p-4"
        >
            <template v-for="(message, i) in messages" :key="message.id">
                <p
                    v-if="showDay(i)"
                    class="my-1 self-center rounded-full bg-white px-2.5 py-0.5 text-[10px] font-semibold text-navy-500 shadow-sm"
                >
                    {{ formatDate(message.createdAt) }}
                </p>
                <div
                    :class="
                        cn(
                            'flex max-w-[88%] items-end gap-2',
                            message.mine
                                ? 'flex-row-reverse self-end'
                                : 'self-start',
                        )
                    "
                >
                    <span
                        :class="
                            cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-full text-white',
                                message.side === 'editorial'
                                    ? 'bg-navy-900'
                                    : 'bg-brand-600',
                            )
                        "
                    >
                        <Building2
                            v-if="message.side === 'editorial'"
                            class="size-3.5"
                        />
                        <UserRound v-else class="size-3.5" />
                    </span>
                    <div
                        :class="
                            cn(
                                'rounded-2xl px-3.5 py-2.5 shadow-[0_1px_2px_rgba(0,30,60,0.06)] transition-shadow hover:shadow-[0_8px_20px_-12px_rgba(0,36,66,0.4)]',
                                message.mine
                                    ? 'rounded-br-md bg-brand-600 text-white'
                                    : 'rounded-bl-md bg-white text-navy-800',
                            )
                        "
                    >
                        <p
                            :class="
                                cn(
                                    'mb-0.5 text-[11px] font-semibold',
                                    message.mine
                                        ? 'text-brand-100'
                                        : 'text-navy-500',
                                )
                            "
                        >
                            {{ message.mine ? 'Siz' : message.sender }}
                        </p>
                        <p
                            class="text-[13px] leading-relaxed whitespace-pre-line"
                        >
                            {{ message.body }}
                        </p>
                        <a
                            v-if="message.attachmentUrl"
                            :href="message.attachmentUrl"
                            :class="
                                cn(
                                    'mt-2 inline-flex max-w-full items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold transition-colors',
                                    message.mine
                                        ? 'bg-white/15 text-white hover:bg-white/25'
                                        : 'bg-brand-50 text-brand-700 hover:bg-brand-100',
                                )
                            "
                        >
                            <Paperclip class="size-3.5 shrink-0" />
                            <span class="truncate">{{
                                message.attachmentName
                            }}</span>
                        </a>
                        <p
                            :class="
                                cn(
                                    'mt-1 flex items-center justify-end gap-1 text-[10px] tabular-nums',
                                    message.mine
                                        ? 'text-brand-100'
                                        : 'text-navy-400',
                                )
                            "
                        >
                            {{ formatTime(message.createdAt) }}
                            <CheckCheck
                                v-if="message.mine && message.readAt"
                                class="size-3.5"
                                aria-label="O'qilgan"
                            />
                        </p>
                    </div>
                </div>
            </template>

            <div
                v-if="!messages.length"
                class="flex flex-1 flex-col items-center justify-center gap-2 py-6 text-center text-xs text-navy-500"
            >
                <MessagesSquare class="size-8 text-navy-300" />
                {{ emptyText }}
            </div>
        </div>

        <form v-if="sendUrl" class="grid gap-2" @submit.prevent="send">
            <textarea
                v-model="form.body"
                rows="3"
                :maxlength="MAX"
                :placeholder="placeholder"
                :aria-invalid="!!errors.body"
                :class="cn(textareaClass, 'min-h-20')"
                @keydown.ctrl.enter.prevent="send"
                @keydown.meta.enter.prevent="send"
            />
            <p v-if="errors.body" class="text-xs text-red-600">
                {{ errors.body }}
            </p>
            <p v-if="errors.attachment" class="text-xs text-red-600">
                {{ errors.attachment }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                    @click="fileInput?.click()"
                >
                    <Paperclip class="size-4" /> Fayl
                </button>
                <span
                    v-if="form.attachment"
                    class="inline-flex max-w-56 items-center gap-1 rounded-lg bg-brand-50 px-2 py-1 text-xs font-medium text-brand-800"
                >
                    <span class="truncate">{{ form.attachment.name }}</span>
                    <button
                        type="button"
                        class="rounded p-0.5 hover:bg-brand-100"
                        aria-label="Faylni olib tashlash"
                        @click="clearFile"
                    >
                        <X class="size-3.5" />
                    </button>
                </span>
                <input
                    ref="fileInput"
                    type="file"
                    accept=".pdf,.doc,.docx,.xlsx,.png,.jpg,.jpeg,.zip"
                    class="hidden"
                    @change="pickFile"
                />
                <span
                    class="ml-auto hidden text-[11px] text-navy-400 sm:inline"
                >
                    Ctrl + Enter
                </span>
                <button
                    type="submit"
                    :disabled="form.processing || form.body.trim().length < 2"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-600 px-4 text-xs font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-60"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <Send v-else class="size-4" />
                    Yuborish
                </button>
            </div>
        </form>
    </div>
</template>

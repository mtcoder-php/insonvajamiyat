<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Camera, LoaderCircle, Trash2, Upload } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { AvatarError, prepareAvatar } from '@/lib/image';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

/**
 * Profil rasmini yuklash.
 *
 *  - "immediate" rejim (profil sahifasi): rasm tanlanishi bilan `storeUrl` ga yuboriladi,
 *    o'chirish — `destroyUrl` ga DELETE.
 *  - "deferred" rejim (yangi foydalanuvchi formasi): tanlangan fayl v-model orqali
 *    formaga qaytadi va forma bilan birga yuboriladi.
 *
 * Rasm brauzerda kvadrat qilib kesiladi va 512 px gacha kichraytiriladi (lib/image.ts).
 * Rasmni ustiga sudrab tashlash ham mumkin.
 */
const props = withDefaults(
    defineProps<{
        name: string;
        url?: string | null;
        mode?: 'immediate' | 'deferred';
        storeUrl?: string;
        destroyUrl?: string;
        error?: string;
        disabled?: boolean;
    }>(),
    {
        url: null,
        mode: 'immediate',
        storeUrl: undefined,
        destroyUrl: undefined,
        error: undefined,
        disabled: false,
    },
);

const file = defineModel<File | null>({ default: null });

const input = ref<HTMLInputElement | null>(null);
const preview = ref<string | null>(null);
const localError = ref<string | null>(null);
const busy = ref(false);
const dragging = ref(false);

const shownUrl = computed(() => preview.value ?? props.url);
const message = computed(() => localError.value ?? props.error ?? null);
const canRemove = computed(() =>
    props.mode === 'deferred'
        ? file.value !== null
        : Boolean(props.url && props.destroyUrl),
);

function setPreview(next: File | null): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = next ? URL.createObjectURL(next) : null;
}

// Forma tozalanganda (reset) ko'rinish ham tozalansin
watch(file, (value) => {
    if (props.mode === 'deferred' && value === null) {
        setPreview(null);
    }
});

onBeforeUnmount(() => setPreview(null));

async function handle(selected: File | undefined): Promise<void> {
    if (!selected || props.disabled) {
        return;
    }

    localError.value = null;
    busy.value = true;

    try {
        const prepared = await prepareAvatar(selected);

        if (props.mode === 'deferred') {
            setPreview(prepared);
            file.value = prepared;

            return;
        }

        if (!props.storeUrl) {
            return;
        }

        setPreview(prepared);

        await new Promise<void>((resolve) => {
            router.post(
                props.storeUrl as string,
                { avatar: prepared },
                {
                    forceFormData: true,
                    preserveScroll: true,
                    onError: (errors) => {
                        localError.value =
                            errors.avatar ?? t('Rasm yuklanmadi.');
                        setPreview(null);
                    },
                    onSuccess: () => setPreview(null),
                    onFinish: () => resolve(),
                },
            );
        });
    } catch (error) {
        localError.value =
            error instanceof AvatarError
                ? error.message
                : t('Rasm yuklanmadi.');
    } finally {
        busy.value = false;

        if (input.value) {
            input.value.value = '';
        }
    }
}

function onChange(event: Event): void {
    void handle((event.target as HTMLInputElement).files?.[0]);
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    void handle(event.dataTransfer?.files?.[0]);
}

function remove(): void {
    localError.value = null;

    if (props.mode === 'deferred') {
        file.value = null;

        return;
    }

    if (!props.destroyUrl) {
        return;
    }

    busy.value = true;
    router.delete(props.destroyUrl, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
    });
}
</script>

<template>
    <div class="flex flex-col items-center gap-3 text-center">
        <button
            type="button"
            :disabled="disabled || busy"
            :class="
                cn(
                    'group relative rounded-full outline-none focus-visible:ring-4 focus-visible:ring-brand-200 disabled:cursor-not-allowed',
                    dragging && 'ring-4 ring-brand-300',
                )
            "
            :aria-label="t('Rasmni almashtirish')"
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <UserAvatar
                :name="name"
                :url="shownUrl"
                size="xl"
                class="shadow-[0_12px_28px_-14px_rgba(0,36,66,0.55)] ring-4"
            />
            <span
                :class="
                    cn(
                        'absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-full bg-navy-950/55 text-xs font-semibold text-white opacity-0 transition-opacity duration-200',
                        !disabled &&
                            'group-hover:opacity-100 group-focus-visible:opacity-100',
                        busy && 'opacity-100',
                    )
                "
            >
                <LoaderCircle v-if="busy" class="size-6 animate-spin" />
                <template v-else>
                    <Camera class="size-6" />
                    {{ t('Almashtirish') }}
                </template>
            </span>
            <span
                v-if="!disabled"
                class="absolute right-1 bottom-1 flex size-8 items-center justify-center rounded-full bg-brand-600 text-white shadow-md ring-4 ring-white transition-transform group-hover:scale-110"
                aria-hidden="true"
            >
                <Camera class="size-4" />
            </span>
        </button>

        <input
            ref="input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onChange"
        />

        <div
            v-if="!disabled"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <button
                type="button"
                class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-white px-3 text-xs font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700 hover:shadow-sm disabled:opacity-50"
                :disabled="busy"
                @click="input?.click()"
            >
                <Upload class="size-3.5" />
                {{ t('Rasm yuklash') }}
            </button>
            <button
                v-if="canRemove"
                type="button"
                class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-white px-3 text-xs font-semibold text-red-600 transition-all hover:-translate-y-px hover:border-red-200 hover:bg-red-50 disabled:opacity-50"
                :disabled="busy"
                @click="remove"
            >
                <Trash2 class="size-3.5" />
                {{ t("O'chirish") }}
            </button>
        </div>
        <p class="text-[11px] leading-snug text-navy-400">
            {{ t('JPG, PNG yoki WEBP · kamida 96×96 px') }}<br />
            {{ t('Rasm avtomatik kvadrat qilib kesiladi') }}
        </p>
        <p v-if="message" class="text-xs font-medium text-red-600">
            {{ message }}
        </p>
    </div>
</template>

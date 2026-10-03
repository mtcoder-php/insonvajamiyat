<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Bot,
    Check,
    Copy,
    FileDown,
    LoaderCircle,
    Save,
    TriangleAlert,
    UserPen,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiRequestDetail } from '@/types';
import { copyText } from './aiMeta';

/**
 * Translator natijasi: manba ↔ tarjima (inline tahrirlanadi), versiyalar va Word yuklab olish.
 */
const props = defineProps<{ request: AiRequestDetail }>();

const translation = computed(() => props.request.translation ?? null);
const selected = ref<number>(translation.value?.versions[0]?.version ?? 1);
const current = computed(
    () =>
        translation.value?.versions.find((v) => v.version === selected.value) ??
        translation.value?.versions[0] ??
        null,
);
const draft = ref(current.value?.content ?? '');
const saving = ref(false);
const copied = ref(false);
const error = ref<string | null>(null);

watch(current, (version) => (draft.value = version?.content ?? ''));
watch(
    () => translation.value?.versions[0]?.version,
    (latest) => {
        if (latest) {
            selected.value = latest;
        }
    },
);

const dirty = computed(
    () => draft.value.trim() !== (current.value?.content ?? '').trim(),
);

function save(): void {
    if (!translation.value) {
        return;
    }

    saving.value = true;
    error.value = null;
    router.post(
        translation.value.saveUrl,
        { content: draft.value },
        {
            preserveScroll: true,
            only: ['current'],
            onError: (errors) =>
                (error.value = errors.content ?? "Saqlab bo'lmadi."),
            onFinish: () => (saving.value = false),
        },
    );
}

async function copy(): Promise<void> {
    copied.value = await copyText(draft.value);
    setTimeout(() => (copied.value = false), 1600);
}
</script>

<template>
    <div v-if="translation" class="grid grid-cols-1 gap-4">
        <p
            v-if="translation.truncated"
            class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900"
        >
            <TriangleAlert class="mt-0.5 size-4 shrink-0 text-amber-600" />
            Tarjimaning bir qismi model javob chegarasiga yetgani sababli
            qisqargan bo'lishi mumkin. Matn oxirini tekshiring yoki qismlarga
            bo'lib tarjima qiling.
        </p>

        <div
            class="grid grid-cols-1 overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] lg:grid-cols-2 lg:divide-x lg:divide-line"
        >
            <div class="flex min-w-0 flex-col">
                <div
                    class="border-b border-line bg-[#fafcff] px-4 py-2.5 text-xs font-semibold text-navy-500"
                >
                    {{ translation.source }} · asl matn
                </div>
                <div
                    class="max-h-[30rem] min-h-72 overflow-y-auto px-5 py-4 text-[13.5px] leading-7 whitespace-pre-wrap text-navy-700"
                >
                    {{ request.input }}
                </div>
            </div>
            <div
                class="flex min-w-0 flex-col border-t border-line lg:border-t-0"
            >
                <div
                    class="flex items-center justify-between gap-2 border-b border-line bg-emerald-50/50 px-4 py-2.5 text-xs font-semibold text-emerald-800"
                >
                    <span
                        >{{ translation.target }} · tarjima (v{{
                            current?.version
                        }})</span
                    >
                    <span
                        v-if="dirty"
                        class="text-[11px] font-medium text-amber-700"
                        >Saqlanmagan o'zgarishlar</span
                    >
                </div>
                <textarea
                    v-model="draft"
                    :readonly="!request.own"
                    class="max-h-[30rem] min-h-72 flex-1 resize-y border-0 px-5 py-4 text-[13.5px] leading-7 text-navy-900 outline-none focus:bg-[#fcfffd] focus:ring-0"
                    aria-label="Tarjima matni"
                />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                v-if="request.own"
                type="button"
                :disabled="saving || !dirty"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-60"
                @click="save"
            >
                <LoaderCircle v-if="saving" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                Yangi versiya sifatida saqlash
            </button>
            <a
                v-if="current"
                :href="current.downloadUrl"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-4 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
            >
                <FileDown class="size-4" /> Word (.docx)
            </a>
            <button
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-4 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                @click="copy"
            >
                <Check v-if="copied" class="size-4 text-emerald-600" />
                <Copy v-else class="size-4" />
                {{ copied ? 'Nusxalandi' : 'Nusxalash' }}
            </button>
            <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        </div>

        <section
            class="rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <h3 class="mb-3 font-sans text-[14px] font-bold text-navy-950">
                Versiyalar
                <span class="ml-1 text-xs font-medium text-navy-400"
                    >({{ translation.versions.length }})</span
                >
            </h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="version in translation.versions"
                    :key="version.version"
                    type="button"
                    :class="
                        cn(
                            'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-left text-xs transition-all hover:-translate-y-px',
                            selected === version.version
                                ? 'border-brand-300 bg-brand-50 text-brand-800 shadow-sm'
                                : 'border-line bg-white text-navy-700 hover:border-brand-200',
                        )
                    "
                    @click="selected = version.version"
                >
                    <Bot v-if="version.isAi" class="size-4 text-emerald-600" />
                    <UserPen v-else class="size-4 text-brand-600" />
                    <span>
                        <b class="block"
                            >v{{ version.version }} · {{ version.author }}</b
                        >
                        <span class="text-[11px] text-navy-500">{{
                            formatDateTime(version.createdAt)
                        }}</span>
                    </span>
                </button>
            </div>
        </section>
    </div>
    <p
        v-else
        class="rounded-lg bg-red-50 px-4 py-6 text-center text-sm text-red-700"
    >
        Tarjima hujjati topilmadi.
    </p>
</template>

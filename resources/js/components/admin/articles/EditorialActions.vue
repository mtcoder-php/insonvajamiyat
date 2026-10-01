<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleX,
    FilePenLine,
    LoaderCircle,
    MessageCircle,
    PlayCircle,
    ShieldCheck,
    UsersRound,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import DecisionDialog from '@/components/admin/articles/DecisionDialog.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { cn } from '@/lib/utils';
import type { EditorialArticle, EditorialDecisionKey } from '@/types';

/**
 * O'ng ustun "Amallar": mas'ul muharrir, ko'rib chiqishga olish, qarorlar.
 * Taqrizchi tayinlash va muallifga xabar — keyingi bosqichlarda.
 */
const props = defineProps<{
    article: EditorialArticle;
    editors: { id: number; name: string }[];
}>();

const editorForm = useForm({
    editor_id: props.article.handlingEditor?.id ?? null,
});

watch(
    () => props.article.handlingEditor?.id ?? null,
    (id) => (editorForm.editor_id = id),
);

function assign(): void {
    editorForm.put(props.article.urls.editor, { preserveScroll: true });
}

const starting = ref(false);

function startReview(): void {
    starting.value = true;
    router.post(
        props.article.urls.startReview,
        {},
        { preserveScroll: true, onFinish: () => (starting.value = false) },
    );
}

const decision = ref<EditorialDecisionKey | null>(null);
const dialogOpen = ref(false);

function open(key: EditorialDecisionKey): void {
    decision.value = key;
    dialogOpen.value = true;
}

const available = (key: EditorialDecisionKey): boolean =>
    props.article.availableDecisions.includes(key);

const item =
    'group flex w-full items-center gap-3 rounded-lg border px-3.5 py-2.5 text-[13px] font-medium transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50 enabled:hover:-translate-y-0.5';
</script>

<template>
    <DashCard title="Amallar">
        <div class="grid gap-2">
            <div v-if="article.can.assign" class="mb-2 grid gap-1.5">
                <label
                    for="handling-editor"
                    class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >Mas'ul muharrir</label
                >
                <div class="flex gap-2">
                    <SelectInput
                        id="handling-editor"
                        v-model="editorForm.editor_id"
                        class="flex-1"
                    >
                        <option :value="null">— Biriktirilmagan —</option>
                        <option
                            v-for="editor in editors"
                            :key="editor.id"
                            :value="editor.id"
                        >
                            {{ editor.name }}
                        </option>
                    </SelectInput>
                    <button
                        type="button"
                        class="h-10 shrink-0 rounded-lg border border-line px-3 text-xs font-semibold text-navy-700 transition-colors hover:border-brand-300 hover:text-brand-700 disabled:opacity-40"
                        :disabled="
                            editorForm.processing ||
                            editorForm.editor_id ===
                                (article.handlingEditor?.id ?? null)
                        "
                        @click="assign"
                    >
                        Saqlash
                    </button>
                </div>
            </div>
            <p
                v-else-if="article.handlingEditor"
                class="mb-2 text-xs text-navy-500"
            >
                Mas'ul muharrir:
                <span class="font-semibold text-navy-800">{{
                    article.handlingEditor.name
                }}</span>
            </p>

            <button
                v-if="article.can.startReview"
                type="button"
                :class="
                    cn(
                        item,
                        'border-brand-600 bg-brand-600 font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] hover:bg-brand-700',
                    )
                "
                :disabled="starting"
                @click="startReview"
            >
                <LoaderCircle
                    v-if="starting"
                    class="size-[18px] animate-spin"
                />
                <PlayCircle v-else class="size-[18px]" />
                Ko'rib chiqishga olish
            </button>

            <button
                type="button"
                disabled
                title="Taqrizchilar bosqichida qo'shiladi"
                :class="cn(item, 'border-line text-navy-700')"
            >
                <UsersRound class="size-[18px] text-navy-400" />
                <span class="flex-1 text-left">Taqrizchilarni tayinlash</span>
                <span
                    class="rounded bg-navy-50 px-1.5 text-[10px] text-navy-400"
                    >tez orada</span
                >
            </button>
            <button
                type="button"
                disabled
                title="Xabarlar bosqichida qo'shiladi"
                :class="cn(item, 'border-line text-navy-700')"
            >
                <MessageCircle class="size-[18px] text-navy-400" />
                <span class="flex-1 text-left">Muallifga xabar yuborish</span>
                <span
                    class="rounded bg-navy-50 px-1.5 text-[10px] text-navy-400"
                    >tez orada</span
                >
            </button>

            <template v-if="article.can.decide">
                <button
                    type="button"
                    :disabled="!available('request_revision')"
                    :class="
                        cn(
                            item,
                            'border-line text-navy-800 enabled:hover:border-amber-300 enabled:hover:bg-amber-50 enabled:hover:text-amber-800',
                        )
                    "
                    @click="open('request_revision')"
                >
                    <FilePenLine class="size-[18px] text-amber-600" />
                    Tuzatish talab qilish
                </button>
                <button
                    type="button"
                    :disabled="!available('reject')"
                    :class="
                        cn(
                            item,
                            'border-red-200 bg-red-50/50 text-red-700 enabled:hover:bg-red-50',
                        )
                    "
                    @click="open('reject')"
                >
                    <CircleX class="size-[18px]" />
                    Maqolani rad etish
                </button>
                <button
                    type="button"
                    :disabled="!available('accept')"
                    :class="
                        cn(
                            item,
                            'border-navy-950 bg-navy-950 font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,30,60,0.9)] enabled:hover:bg-navy-900',
                        )
                    "
                    @click="open('accept')"
                >
                    <CircleCheck class="size-[18px]" />
                    Qabul qilish (nashrga)
                </button>
            </template>

            <p
                v-if="
                    article.can.decide &&
                    !article.availableDecisions.length &&
                    !article.can.startReview
                "
                class="mt-1 text-xs leading-relaxed text-navy-500"
            >
                Maqolaning hozirgi holatida («{{ article.statusLabel }}») qaror
                berilmaydi.
            </p>
        </div>

        <div
            class="mt-4 flex items-start gap-3 rounded-lg bg-[#f5f8fc] p-3 text-xs leading-relaxed text-navy-600"
        >
            <ShieldCheck class="mt-0.5 size-5 shrink-0 text-brand-600" />
            Ichki izohlar va taqrizchilar ma'lumotlari muallifga ko'rinmaydi
            (blind review).
        </div>

        <DecisionDialog
            v-model:open="dialogOpen"
            :article="article"
            :decision="decision"
        />
    </DashCard>
</template>

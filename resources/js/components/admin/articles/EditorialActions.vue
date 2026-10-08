<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    BookCheck,
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
import { t } from '@/lib/i18n';

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

const emit = defineEmits<{ invite: []; message: [] }>();

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
    <DashCard :title="t('Amallar')">
        <div class="grid gap-2">
            <div v-if="article.can.assign" class="mb-2 grid gap-1.5">
                <label
                    for="handling-editor"
                    class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >{{ t("Mas'ul muharrir") }}</label
                >
                <div class="flex gap-2">
                    <SelectInput
                        id="handling-editor"
                        v-model="editorForm.editor_id"
                        class="flex-1"
                    >
                        <option :value="null">
                            {{ t('— Biriktirilmagan —') }}
                        </option>
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
                        {{ t('Saqlash') }}
                    </button>
                </div>
            </div>
            <p
                v-else-if="article.handlingEditor"
                class="mb-2 text-xs text-navy-500"
            >
                {{ t("Mas'ul muharrir:") }}
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
                {{ t("Ko'rib chiqishga olish") }}
            </button>

            <button
                type="button"
                :disabled="!article.can.invite"
                :title="
                    article.can.invite
                        ? undefined
                        : t(
                              'Taqrizchi faqat ko\'rib chiqilayotgan maqolaga tayinlanadi',
                          )
                "
                :class="
                    cn(
                        item,
                        'border-line text-navy-800 enabled:hover:border-brand-300 enabled:hover:bg-brand-50 enabled:hover:text-brand-700',
                    )
                "
                @click="emit('invite')"
            >
                <UsersRound class="size-[18px] text-brand-600" />
                <span class="flex-1 text-left">{{
                    t('Taqrizchilarni tayinlash')
                }}</span>
                <span
                    v-if="article.reviews.length"
                    class="rounded-full bg-brand-50 px-1.5 text-[10px] font-semibold text-brand-700 tabular-nums"
                    >{{
                        article.reviews.filter((r) => r.status === 'completed')
                            .length
                    }}/{{
                        article.reviews.filter(
                            (r) =>
                                r.status !== 'cancelled' &&
                                r.status !== 'declined',
                        ).length
                    }}</span
                >
            </button>
            <button
                type="button"
                :disabled="!article.can.message"
                :class="
                    cn(
                        item,
                        'border-line text-navy-800 enabled:hover:border-brand-300 enabled:hover:bg-brand-50 enabled:hover:text-brand-800',
                    )
                "
                @click="emit('message')"
            >
                <MessageCircle class="size-[18px] text-brand-600" />
                <span class="flex-1 text-left">{{
                    t('Muallifga xabar yuborish')
                }}</span>
                <span
                    v-if="article.messages.length"
                    class="rounded-full bg-brand-50 px-1.5 text-[10px] font-semibold text-brand-700"
                    >{{ article.messages.length }}</span
                >
            </button>

            <Link
                v-if="article.urls.production"
                :href="article.urls.production"
                :class="
                    cn(
                        item,
                        'border-teal-200 bg-teal-50 text-teal-800 hover:border-teal-300 hover:bg-teal-100',
                    )
                "
            >
                <BookCheck class="size-[18px] text-teal-600" />
                {{ t('Nashr jarayonida ochish') }}
            </Link>

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
                    {{ t('Tuzatish talab qilish') }}
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
                    {{ t('Maqolani rad etish') }}
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
                    {{ t('Qabul qilish (nashrga)') }}
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
                {{
                    t(
                        'Maqolaning hozirgi holatida («:status») qaror berilmaydi.',
                        {
                            status: article.statusLabel,
                        },
                    )
                }}
            </p>
        </div>

        <div
            class="mt-4 flex items-start gap-3 rounded-lg bg-[#f5f8fc] p-3 text-xs leading-relaxed text-navy-600"
        >
            <ShieldCheck class="mt-0.5 size-5 shrink-0 text-brand-600" />
            {{
                t(
                    "Ichki izohlar va taqrizchilar ma'lumotlari muallifga ko'rinmaydi (blind review).",
                )
            }}
        </div>

        <DecisionDialog
            v-model:open="dialogOpen"
            :article="article"
            :decision="decision"
        />
    </DashCard>
</template>

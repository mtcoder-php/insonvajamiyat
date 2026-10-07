<script setup lang="ts">
import { FolderTree, PenLine, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';
import type { SettingsSubject } from '@/types';
import DeleteDialog from './DeleteDialog.vue';
import SubjectDialog from './SubjectDialog.vue';

/** Ilmiy yo'nalishlar ro'yxati (rukn/fan sohalari) */
const props = defineProps<{ subjects: SettingsSubject[]; storeUrl: string }>();

const editing = ref<SettingsSubject | null>(null);
const formOpen = ref(false);
const removing = ref<SettingsSubject | null>(null);
const deleteOpen = ref(false);

const byId = computed(() => new Map(props.subjects.map((s) => [s.id, s])));

function create(): void {
    editing.value = null;
    formOpen.value = true;
}

function edit(subject: SettingsSubject): void {
    editing.value = subject;
    formOpen.value = true;
}

function remove(subject: SettingsSubject): void {
    removing.value = subject;
    deleteOpen.value = true;
}
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4"
        >
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    Ilmiy yo'nalishlar
                </h2>
                <p class="text-xs text-navy-500">
                    {{ subjects.length }} ta · saytdagi filtrlar, maqola
                    yuborish formasi va hisobotlarda ishlatiladi
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                @click="create"
            >
                <Plus class="size-4" /> Yo'nalish qo'shish
            </button>
        </header>

        <div class="overflow-x-auto">
            <table
                class="w-full min-w-[44rem] text-left text-[13px] text-navy-800"
            >
                <thead
                    class="bg-[#fafcff] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                >
                    <tr>
                        <th class="px-4 py-2.5">Nomi</th>
                        <th class="px-4 py-2.5">Shifr</th>
                        <th class="px-4 py-2.5 text-right">Maqolalar</th>
                        <th class="px-4 py-2.5">Holat</th>
                        <th class="px-4 py-2.5 text-right">Tartib</th>
                        <th class="px-4 py-2.5 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr
                        v-for="subject in subjects"
                        :key="subject.id"
                        class="group transition-colors hover:bg-brand-50/40"
                    >
                        <td class="px-4 py-2.5">
                            <span
                                :class="
                                    cn(
                                        'flex items-center gap-2',
                                        subject.parentId && 'pl-5',
                                    )
                                "
                            >
                                <FolderTree
                                    v-if="subject.parentId"
                                    class="size-3.5 shrink-0 text-navy-300"
                                />
                                <span class="font-medium text-navy-900">{{
                                    subject.name
                                }}</span>
                            </span>
                            <span
                                class="mt-0.5 flex gap-1"
                                :class="subject.parentId ? 'pl-10' : ''"
                            >
                                <span
                                    v-for="lang in ['ru', 'en'] as const"
                                    :key="lang"
                                    :class="
                                        cn(
                                            'rounded px-1 text-[10px] font-bold uppercase',
                                            subject.translations[lang]
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-navy-50 text-navy-300',
                                        )
                                    "
                                    :title="
                                        subject.translations[lang] ||
                                        'Tarjima yo\'q'
                                    "
                                    >{{ lang }}</span
                                >
                                <span
                                    v-if="subject.parentId"
                                    class="text-[11px] text-navy-400"
                                >
                                    ·
                                    {{ byId.get(subject.parentId)?.name }}</span
                                >
                            </span>
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-navy-600">
                            {{ subject.code ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ subject.articlesCount }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span
                                :class="
                                    cn(
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                        subject.isActive
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : 'bg-navy-50 text-navy-500 ring-line',
                                    )
                                "
                                >{{
                                    subject.isActive ? 'Faol' : 'Nofaol'
                                }}</span
                            >
                        </td>
                        <td
                            class="px-4 py-2.5 text-right text-navy-500 tabular-nums"
                        >
                            {{ subject.sortOrder }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <div
                                class="inline-flex gap-1 opacity-70 transition-opacity group-hover:opacity-100"
                            >
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                    aria-label="Tahrirlash"
                                    @click="edit(subject)"
                                >
                                    <PenLine class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                    aria-label="O'chirish"
                                    @click="remove(subject)"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!subjects.length">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-sm text-navy-400"
                        >
                            Yo'nalishlar hali qo'shilmagan
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SubjectDialog
            v-model:open="formOpen"
            :subject="editing"
            :store-url="storeUrl"
            :parents="subjects"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            title="Yo'nalishni o'chirish"
            :description="`«${removing?.name ?? ''}» o'chiriladi. Maqolasi bor yo'nalishni o'chirib bo'lmaydi — uni faolsizlantiring.`"
        />
    </section>
</template>

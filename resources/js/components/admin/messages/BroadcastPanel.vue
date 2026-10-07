<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    TriangleAlert,
    Check,
    ChevronDown,
    CircleCheck,
    Clock,
    LoaderCircle,
    Mail,
    Megaphone,
    Send,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { formatDateTime, formatNumber } from '@/lib/format';
import {
    inputClass,
    primaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { BroadcastAudienceOption, BroadcastItem } from '@/types';

/**
 * Ommaviy xabar: qabul qiluvchilar guruhi (sonlari bilan), mavzu, matn, email ham yuborish;
 * yuborishdan oldin tasdiqlash. Pastda — yuborilgan xabarlar tarixi va holati.
 */
const props = defineProps<{
    audiences: BroadcastAudienceOption[];
    history: BroadcastItem[];
    url: string;
}>();

const MAX = 5000;

const form = useForm({
    audience: props.audiences[0]?.value ?? 'authors',
    subject: '',
    body: '',
    send_email: true,
});

const selected = computed(() =>
    props.audiences.find((a) => a.value === form.audience),
);

const confirmOpen = ref(false);
const expanded = ref<string | null>(null);

function submit(): void {
    form.post(props.url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirmOpen.value = false;
            form.reset('subject', 'body');
        },
        onError: () => (confirmOpen.value = false),
    });
}

const statusMeta: Record<
    BroadcastItem['status'],
    { label: string; class: string }
> = {
    queued: { label: 'Navbatda', class: 'bg-slate-100 text-slate-600' },
    sending: { label: 'Yuborilmoqda', class: 'bg-amber-50 text-amber-700' },
    sent: { label: 'Yuborildi', class: 'bg-emerald-50 text-emerald-700' },
    failed: { label: 'Xato', class: 'bg-red-50 text-red-700' },
};
</script>

<template>
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <SectionCard
            title="Yangi ommaviy xabar"
            description="Tanlangan guruhdagi har bir foydalanuvchiga bildirishnoma (va ixtiyoriy email) yuboriladi"
            :icon="Megaphone"
        >
            <form
                class="grid grid-cols-1 gap-4"
                @submit.prevent="confirmOpen = true"
            >
                <div>
                    <p class="mb-2 text-[13px] font-semibold text-navy-800">
                        Kimga <span class="text-red-500">*</span>
                    </p>
                    <div
                        class="grid grid-cols-1 gap-2 sm:grid-cols-2 2xl:grid-cols-3"
                        role="radiogroup"
                    >
                        <button
                            v-for="audience in audiences"
                            :key="audience.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.audience === audience.value"
                            :class="
                                cn(
                                    'group flex items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition-all',
                                    form.audience === audience.value
                                        ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-200'
                                        : 'border-line bg-white hover:-translate-y-px hover:border-brand-200 hover:shadow-sm',
                                )
                            "
                            @click="form.audience = audience.value"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                                        form.audience === audience.value
                                            ? 'bg-brand-600 text-white'
                                            : 'bg-[#eef3fa] text-navy-500 group-hover:text-brand-600',
                                    )
                                "
                            >
                                <Check
                                    v-if="form.audience === audience.value"
                                    class="size-4"
                                    :stroke-width="3"
                                />
                                <Users v-else class="size-4" />
                            </span>
                            <span class="min-w-0">
                                <span
                                    class="block truncate text-[13px] font-semibold text-navy-900"
                                    >{{ audience.label }}</span
                                >
                                <span
                                    class="block text-xs text-navy-500 tabular-nums"
                                    >{{
                                        formatNumber(audience.count)
                                    }}
                                    kishi</span
                                >
                            </span>
                        </button>
                    </div>
                    <p
                        v-if="form.errors.audience"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.audience }}
                    </p>
                </div>

                <FormField
                    label="Mavzu"
                    for="b-subject"
                    required
                    :error="form.errors.subject"
                >
                    <input
                        id="b-subject"
                        v-model="form.subject"
                        maxlength="200"
                        :class="inputClass"
                        placeholder="Navbatdagi son uchun maqolalar qabuli boshlandi"
                    />
                </FormField>
                <FormField
                    label="Matn"
                    for="b-body"
                    required
                    :error="form.errors.body"
                    :hint="`${formatNumber(form.body.length)} / ${formatNumber(MAX)} · xatboshilarni bo'sh qator bilan ajrating`"
                >
                    <textarea
                        id="b-body"
                        v-model="form.body"
                        rows="8"
                        :maxlength="MAX"
                        :class="textareaClass"
                        placeholder="Hurmatli mualliflar! ..."
                    />
                </FormField>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-line px-3.5 py-3 transition-colors hover:border-brand-200"
                >
                    <input
                        v-model="form.send_email"
                        type="checkbox"
                        class="mt-0.5 size-4 accent-brand-600"
                    />
                    <span>
                        <span
                            class="flex items-center gap-1.5 text-[13px] font-semibold text-navy-900"
                        >
                            <Mail class="size-3.5 text-brand-600" /> Email ham
                            yuborish
                        </span>
                        <span class="block text-xs text-navy-500"
                            >Emaili tasdiqlangan foydalanuvchilarga. Aks holda —
                            faqat saytdagi bildirishnoma.</span
                        >
                    </span>
                </label>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="
                            form.processing ||
                            !form.subject.trim() ||
                            form.body.trim().length < 10 ||
                            !selected?.count
                        "
                        :class="primaryButtonClass"
                    >
                        <Send class="size-4" />
                        Yuborish
                        <span
                            v-if="selected"
                            class="rounded-full bg-white/20 px-1.5 text-xs tabular-nums"
                            >{{ formatNumber(selected.count) }}</span
                        >
                    </button>
                </div>
            </form>
        </SectionCard>

        <SectionCard
            title="Yuborilganlar"
            :description="`So'nggi ${history.length} ta`"
            :icon="Clock"
        >
            <ul v-if="history.length" class="-mx-5 -my-5 divide-y divide-line">
                <li v-for="item in history" :key="item.uuid">
                    <button
                        type="button"
                        class="group w-full px-5 py-3 text-left transition-colors hover:bg-brand-50/40"
                        :aria-expanded="expanded === item.uuid"
                        @click="
                            expanded = expanded === item.uuid ? null : item.uuid
                        "
                    >
                        <span class="flex items-start gap-2">
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block text-[13px] font-semibold [overflow-wrap:anywhere] text-navy-900 transition-colors group-hover:text-brand-700"
                                    >{{ item.subject }}</span
                                >
                                <span class="mt-0.5 block text-xs text-navy-500"
                                    >{{ item.audience }} ·
                                    {{ formatNumber(item.sent) }}/{{
                                        formatNumber(item.recipients)
                                    }}
                                    <template v-if="item.sendEmail">
                                        · email</template
                                    ></span
                                >
                            </span>
                            <span
                                :class="
                                    cn(
                                        'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        statusMeta[item.status].class,
                                    )
                                "
                            >
                                <LoaderCircle
                                    v-if="item.status === 'sending'"
                                    class="size-3 animate-spin"
                                />
                                <CircleCheck
                                    v-else-if="item.status === 'sent'"
                                    class="size-3"
                                />
                                <TriangleAlert
                                    v-else-if="item.status === 'failed'"
                                    class="size-3"
                                />
                                {{ statusMeta[item.status].label }}
                            </span>
                            <ChevronDown
                                :class="
                                    cn(
                                        'mt-0.5 size-4 shrink-0 text-navy-300 transition-transform',
                                        expanded === item.uuid && 'rotate-180',
                                    )
                                "
                            />
                        </span>
                        <span
                            class="mt-1 block text-[11px] text-navy-400 tabular-nums"
                            >{{ item.sender }} ·
                            {{ formatDateTime(item.createdAt) }}</span
                        >
                    </button>
                    <div
                        v-if="expanded === item.uuid"
                        class="mx-5 mb-3 rounded-lg bg-[#f6f9fd] px-3 py-2.5 text-xs leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line text-navy-700"
                    >
                        {{ item.body }}
                        <p v-if="item.error" class="mt-2 text-red-600">
                            {{ item.error }}
                        </p>
                    </div>
                </li>
            </ul>
            <p
                v-else
                class="rounded-lg border border-dashed border-line px-4 py-8 text-center text-sm text-navy-400"
            >
                Hali ommaviy xabar yuborilmagan
            </p>
        </SectionCard>

        <ActionDialog
            v-model:open="confirmOpen"
            title="Xabarni yuborish"
            :description="`«${form.subject}» ${formatNumber(selected?.count ?? 0)} ta foydalanuvchiga (${selected?.label ?? ''}) ${form.send_email ? 'bildirishnoma va email' : 'bildirishnoma'} sifatida yuboriladi. Yuborilgan xabarni qaytarib bo'lmaydi.`"
            :icon="Megaphone"
            confirm-text="Ha, yuborish"
            :processing="form.processing"
            @confirm="submit"
        />
    </div>
</template>

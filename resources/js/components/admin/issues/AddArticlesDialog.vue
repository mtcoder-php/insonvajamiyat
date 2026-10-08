<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BadgeCheck, Check, FilePlus2, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { AvailableArticle } from '@/types';
import { t, tc } from '@/lib/i18n';

/**
 * Songa maqola qo'shish: qabul qilingan va hali hech bir songa biriktirilmagan maqolalar.
 */
const props = defineProps<{ url: string; articles: AvailableArticle[] }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm<{ article_ids: number[] }>({ article_ids: [] });
const search = ref('');

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        search.value = '';
    }
});

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.articles.filter(
        (a) =>
            !q ||
            a.title.toLowerCase().includes(q) ||
            a.author.toLowerCase().includes(q) ||
            a.code.includes(q),
    );
});

function toggle(id: number): void {
    form.article_ids = form.article_ids.includes(id)
        ? form.article_ids.filter((x) => x !== id)
        : [...form.article_ids, id];
}

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    form.post(props.url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('Songa maqola qo\'shish')"
        :description="
            t(
                'Qabul qilingan va nashrga tayyorlanayotgan maqolalar. Tanlanganlar son oxiriga qo\'shiladi.',
            )
        "
        :icon="FilePlus2"
        :confirm-text="
            t('Qo\'shish (:count)', { count: form.article_ids.length })
        "
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-3">
            <p
                v-if="errors.article_ids"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ errors.article_ids }}
            </p>
            <label class="relative block">
                <span class="sr-only">{{ t('Qidirish') }}</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('Sarlavha, muallif yoki ID...')"
                    :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                />
            </label>
            <ul
                class="-mx-1 grid max-h-72 gap-1 overflow-y-auto px-1"
                role="listbox"
                aria-multiselectable="true"
            >
                <li v-for="article in filtered" :key="article.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="form.article_ids.includes(article.id)"
                        :class="
                            cn(
                                'flex w-full items-start gap-3 rounded-lg border px-3 py-2 text-left transition-colors',
                                form.article_ids.includes(article.id)
                                    ? 'border-brand-400 bg-brand-50'
                                    : 'border-line hover:border-brand-200',
                            )
                        "
                        @click="toggle(article.id)"
                    >
                        <span
                            :class="
                                cn(
                                    'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border',
                                    form.article_ids.includes(article.id)
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-navy-200',
                                )
                            "
                        >
                            <Check
                                v-if="form.article_ids.includes(article.id)"
                                class="size-3.5"
                                :stroke-width="3"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="line-clamp-2 text-[13px] font-semibold text-navy-900"
                                >{{ article.title }}</span
                            >
                            <span
                                class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-navy-500"
                            >
                                #{{ article.code }} · {{ article.author }} ·
                                {{ article.statusLabel }}
                                <template v-if="article.pagesCount"
                                    >·
                                    {{
                                        tc(':count bet', article.pagesCount)
                                    }}</template
                                >
                                <span
                                    v-if="article.approved"
                                    class="inline-flex items-center gap-0.5 font-semibold text-emerald-700"
                                >
                                    <BadgeCheck class="size-3" />
                                    {{ t('tasdiqlangan') }}
                                </span>
                            </span>
                        </span>
                    </button>
                </li>
                <li
                    v-if="!filtered.length"
                    class="py-6 text-center text-xs text-navy-500"
                >
                    {{
                        t(
                            "Biriktirilmagan maqola yo'q. Maqolalar muharrir «Qabul qilish» qaroridan keyin shu yerda chiqadi.",
                        )
                    }}
                </li>
            </ul>
        </div>
    </ActionDialog>
</template>

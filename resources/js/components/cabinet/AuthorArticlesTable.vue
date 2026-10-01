<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronDown,
    ExternalLink,
    Eye,
    FileText,
    History,
    PenLine,
} from '@lucide/vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDate, formatTime } from '@/lib/format';
import type { AuthorArticle } from '@/types';

/**
 * "Mening maqolalarim" jadvali (dashboard va ro'yxat sahifasi uchun umumiy).
 * offset — sahifalangan ro'yxatda tartib raqami uchun.
 */
const { offset = 0 } = defineProps<{
    items: AuthorArticle[];
    offset?: number;
}>();

const editable = (article: AuthorArticle): boolean =>
    article.statusGroup === 'draft' || article.statusGroup === 'revision';
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-[13px]">
            <thead>
                <tr
                    class="bg-[#f5f8fc] text-xs font-semibold text-navy-600 [&>th]:py-2.5 [&>th:first-child]:rounded-l-lg [&>th:last-child]:rounded-r-lg"
                >
                    <th class="w-12 pr-2 pl-4">№</th>
                    <th class="pr-4">Maqola nomi</th>
                    <th class="pr-4">Jurnal soni</th>
                    <th class="pr-4">Status</th>
                    <th class="pr-4">Oxirgi yangilanish</th>
                    <th class="pr-4 text-right">Amallar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <tr
                    v-for="(article, index) in items"
                    :key="article.id"
                    class="group transition-colors duration-200 hover:bg-brand-50/40"
                >
                    <td
                        class="py-3.5 pl-4 align-top font-semibold text-navy-500 tabular-nums"
                    >
                        {{ offset + index + 1 }}
                    </td>
                    <td class="py-3.5 pr-4 align-top">
                        <Link
                            :href="article.url"
                            class="line-clamp-2 max-w-md min-w-56 font-semibold text-navy-900 transition-colors group-hover:text-brand-700"
                        >
                            {{ article.title }}
                        </Link>
                        <div
                            v-if="article.keywords.length"
                            class="mt-1.5 flex flex-wrap gap-1"
                        >
                            <span
                                v-for="keyword in article.keywords"
                                :key="keyword"
                                class="rounded-md bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-brand-100 ring-inset"
                            >
                                {{ keyword }}
                            </span>
                        </div>
                    </td>
                    <td class="py-3.5 pr-4 align-top whitespace-nowrap">
                        <p class="font-medium text-navy-800">
                            {{ article.issue ?? '—' }}
                        </p>
                        <p
                            v-if="article.submittedAt"
                            class="mt-0.5 text-xs text-navy-500 tabular-nums"
                        >
                            {{ formatDate(article.submittedAt) }}
                        </p>
                    </td>
                    <td class="py-3.5 pr-4 align-top">
                        <ArticleStatusPill
                            :group="article.statusGroup"
                            :label="article.statusLabel"
                        />
                    </td>
                    <td
                        class="py-3.5 pr-4 align-top whitespace-nowrap text-navy-700 tabular-nums"
                    >
                        <p>{{ formatDate(article.updatedAt) }}</p>
                        <p class="mt-0.5 text-xs text-navy-500">
                            {{ formatTime(article.updatedAt) }}
                        </p>
                    </td>
                    <td class="py-3.5 pr-4 align-top">
                        <div
                            class="ml-auto flex w-max overflow-hidden rounded-lg border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-200 group-hover:border-brand-200 group-hover:shadow-[0_6px_14px_-10px_rgba(0,108,246,0.6)]"
                        >
                            <Link
                                :href="article.url"
                                class="px-3 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                            >
                                Tafsilotlar
                            </Link>
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="flex items-center border-l border-line px-2 text-navy-500 transition-colors outline-none hover:bg-brand-50 hover:text-brand-700 focus-visible:bg-brand-50 data-[state=open]:bg-brand-50 data-[state=open]:text-brand-700"
                                    :aria-label="`${article.title} — amallar`"
                                >
                                    <ChevronDown class="size-4" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-52">
                                    <DropdownMenuItem as-child>
                                        <Link :href="article.url">
                                            <Eye class="size-4" />
                                            Batafsil ko'rish
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <Link :href="`${article.url}#tarix`">
                                            <History class="size-4" />
                                            Holat tarixi
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="article.publicUrl"
                                        as-child
                                    >
                                        <a
                                            :href="article.publicUrl"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <ExternalLink class="size-4" />
                                            Saytda ko'rish
                                        </a>
                                    </DropdownMenuItem>
                                    <template v-if="editable(article)">
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            disabled
                                            title="Tez orada"
                                        >
                                            <PenLine class="size-4" />
                                            {{
                                                article.statusGroup ===
                                                'revision'
                                                    ? 'Tuzatilgan variantni yuborish'
                                                    : 'Tahrirlash'
                                            }}
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div
            v-if="!items.length"
            class="flex flex-col items-center gap-2 py-12 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-600"
            >
                <FileText class="size-5" />
            </span>
            <slot name="empty">
                <p class="text-sm text-navy-500">Hozircha maqolalar yo'q</p>
            </slot>
        </div>
    </div>
</template>

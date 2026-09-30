<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, Globe } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { update } from '@/routes/locale';
import type { LocaleCode } from '@/types';

/**
 * Til tanlagich: 🌐 UZ ▾ → O'zbekcha / Русский / English.
 *   tone="dark"  — to'q ko'k topbar ichida
 *   tone="light" — oq fonda (mobil menyu)
 */
const props = withDefaults(defineProps<{ tone?: 'dark' | 'light' }>(), {
    tone: 'dark',
});

const page = usePage();
const current = computed(() => page.props.locale);
const locales = computed(() => page.props.locales);

function select(code: LocaleCode): void {
    if (code === current.value) {
        return;
    }

    router.post(update.url(), { locale: code }, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'group inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs font-semibold tracking-wide uppercase transition-colors outline-none focus-visible:ring-2',
                        props.tone === 'dark'
                            ? 'text-white/90 hover:bg-white/10 hover:text-white focus-visible:ring-white/40 data-[state=open]:bg-white/10'
                            : 'border border-line text-navy-800 hover:bg-navy-50 focus-visible:ring-brand-200',
                    )
                "
                :aria-label="`Sayt tili: ${current.toUpperCase()}`"
            >
                <Globe class="size-3.5" />
                {{ current }}
                <ChevronDown
                    class="size-3.5 opacity-70 transition-transform group-data-[state=open]:rotate-180"
                />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" :side-offset="6" class="w-40 p-1">
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.code"
                :class="
                    cn(
                        'cursor-pointer justify-between rounded-md px-3 py-2 text-sm',
                        locale.code === current
                            ? 'bg-navy-50 font-semibold text-navy-950'
                            : 'text-navy-700',
                    )
                "
                :lang="locale.code"
                @select="select(locale.code)"
            >
                {{ locale.label }}
                <Check
                    v-if="locale.code === current"
                    class="size-4 text-brand-600"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

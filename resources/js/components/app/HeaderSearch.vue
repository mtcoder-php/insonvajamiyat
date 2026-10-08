<script setup lang="ts">
import { Search } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * Admin header qidiruvi. Ctrl+K (macOS'da ⌘K) — maydonga fokus, Esc — chiqish.
 * Natijalar sahifasi qidiruv moduli bilan ulanadi (`search` hodisasi).
 */
const emit = defineEmits<{ search: [query: string] }>();

const input = ref<HTMLInputElement | null>(null);
const query = ref('');
const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPad/.test(navigator.platform);

function onKeydown(event: KeyboardEvent): void {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        input.value?.focus();
        input.value?.select();
    }
}

function submit(): void {
    const value = query.value.trim();

    if (value !== '') {
        emit('search', value);
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <form role="search" class="group relative w-full" @submit.prevent="submit">
        <label for="admin-search" class="sr-only">{{ t('Qidirish') }}</label>
        <Search
            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-navy-400 transition-colors group-focus-within:text-brand-600"
        />
        <input
            id="admin-search"
            ref="input"
            v-model="query"
            type="search"
            autocomplete="off"
            :placeholder="
                t(
                    'Maqola, muallif, ISSN yoki boshqa kalit so\'z bo\'yicha qidirish...',
                )
            "
            class="h-10 w-full rounded-lg border border-white/60 bg-white pr-20 pl-10 text-sm text-navy-900 shadow-sm transition-all duration-300 placeholder:text-navy-400 hover:shadow-md focus:border-brand-300 focus:shadow-[0_8px_24px_-8px_rgba(0,0,0,0.45)] focus:ring-4 focus:ring-brand-400/25 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
            @keydown.esc="input?.blur()"
        />
        <kbd
            class="pointer-events-none absolute top-1/2 right-2.5 hidden -translate-y-1/2 items-center gap-0.5 rounded-md border border-navy-100 bg-navy-50 px-1.5 py-0.5 font-sans text-[11px] font-medium text-navy-500 sm:inline-flex"
        >
            {{ isMac ? '⌘' : 'Ctrl' }} + K
        </kbd>
    </form>
</template>

<script setup lang="ts">
import { Pin } from '@lucide/vue';
import { formatDateLong } from '@/lib/format';
import type { PostItem } from '@/types';

/**
 * "E'lonlar" — o'ng ustundagi qisqa ro'yxat.
 */
defineProps<{ items: PostItem[] }>();
</script>

<template>
    <section
        v-if="items.length"
        class="rounded-xl border border-line bg-white p-6"
    >
        <h3 class="font-serif text-lg font-semibold text-navy-950">E'lonlar</h3>
        <ul class="mt-4 space-y-5">
            <li
                v-for="item in items"
                :key="item.id"
                class="border-l-2 border-gold-400 pl-4"
            >
                <time
                    v-if="item.publishedAt"
                    :datetime="item.publishedAt"
                    class="text-xs font-medium text-navy-400"
                >
                    {{ formatDateLong(item.publishedAt) }}
                </time>
                <p
                    class="mt-0.5 flex items-start gap-1.5 text-sm leading-snug font-semibold text-navy-900"
                >
                    <Pin
                        v-if="item.isPinned"
                        class="mt-0.5 size-3.5 shrink-0 text-gold-600"
                        aria-label="Qadalgan"
                    />
                    {{ item.title }}
                </p>
                <p
                    v-if="item.excerpt"
                    class="mt-1 text-xs leading-relaxed text-navy-500"
                >
                    {{ item.excerpt }}
                </p>
            </li>
        </ul>
    </section>
</template>

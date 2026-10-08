<script setup lang="ts">
import { computed } from 'vue';

/**
 * Tahririyat matni (statik sahifalar): HTML'siz, xavfsiz chiziladi.
 *   - xatboshilar bo'sh qator bilan ajratiladi;
 *   - "- " bilan boshlangan qatorlar — ro'yxat, "1. " — raqamli ro'yxat;
 *   - **qalin** matn.
 */
const props = defineProps<{ text: string }>();

type Inline = { text: string; bold: boolean };
type Block =
    | { type: 'p'; lines: Inline[][] }
    | { type: 'ul' | 'ol'; items: Inline[][] };

function inline(line: string): Inline[] {
    return line
        .split(/(\*\*[^*]+\*\*)/g)
        .filter((part) => part !== '')
        .map((part) =>
            part.startsWith('**') && part.endsWith('**') && part.length > 4
                ? { text: part.slice(2, -2), bold: true }
                : { text: part, bold: false },
        );
}

const blocks = computed<Block[]>(() =>
    props.text
        .replace(/\r\n?/g, '\n')
        .split(/\n\s*\n/)
        .map((chunk) => chunk.split('\n').map((line) => line.trim()))
        .map((lines) => lines.filter((line) => line !== ''))
        .filter((lines) => lines.length > 0)
        .map((lines): Block => {
            if (lines.every((line) => /^[-•]\s+/.test(line))) {
                return {
                    type: 'ul',
                    items: lines.map((line) =>
                        inline(line.replace(/^[-•]\s+/, '')),
                    ),
                };
            }

            if (lines.every((line) => /^\d+[.)]\s+/.test(line))) {
                return {
                    type: 'ol',
                    items: lines.map((line) =>
                        inline(line.replace(/^\d+[.)]\s+/, '')),
                    ),
                };
            }

            return { type: 'p', lines: lines.map(inline) };
        }),
);
</script>

<template>
    <div class="space-y-4 text-[15px] leading-[1.8] text-navy-700">
        <template v-for="(block, b) in blocks" :key="b">
            <p v-if="block.type === 'p'">
                <template v-for="(line, l) in block.lines" :key="l">
                    <br v-if="l > 0" />
                    <template v-for="(part, i) in line" :key="i">
                        <strong
                            v-if="part.bold"
                            class="font-semibold text-navy-900"
                            >{{ part.text }}</strong
                        >
                        <template v-else>{{ part.text }}</template>
                    </template>
                </template>
            </p>
            <component :is="block.type" v-else class="list-none space-y-2 pl-1">
                <li
                    v-for="(item, i) in block.items"
                    :key="i"
                    class="relative pl-7"
                >
                    <span
                        v-if="block.type === 'ul'"
                        class="absolute top-[0.7em] left-1.5 size-1.5 rounded-full bg-gold-500"
                        aria-hidden="true"
                    />
                    <span
                        v-else
                        class="absolute top-[0.2em] left-0 flex size-5 items-center justify-center rounded-full bg-brand-50 text-[11px] font-bold text-brand-700 tabular-nums"
                        aria-hidden="true"
                        >{{ i + 1 }}</span
                    >
                    <template v-for="(part, p) in item" :key="p">
                        <strong
                            v-if="part.bold"
                            class="font-semibold text-navy-900"
                            >{{ part.text }}</strong
                        >
                        <template v-else>{{ part.text }}</template>
                    </template>
                </li>
            </component>
        </template>
    </div>
</template>

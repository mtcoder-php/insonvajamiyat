<script setup lang="ts">
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Check,
    Heading2,
    Heading3,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    Minus,
    Pilcrow,
    Quote,
    Redo2,
    RemoveFormatting,
    Strikethrough,
    Underline,
    Undo2,
    X,
} from '@lucide/vue';
import TextAlign from '@tiptap/extension-text-align';
import { CharacterCount, Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import type { Component } from 'vue';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { formatNumber } from '@/lib/format';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * Matn muharriri (Tiptap): sarlavhalar, qalin/kursiv/tagiga chizilgan, ro'yxatlar, iqtibos,
 * havola, tekislash, ajratuvchi chiziq. v-model — HTML (bo'sh bo'lsa ''). Server tomonda
 * App\Support\Html\HtmlSanitizer ruxsat etilgan teglardan boshqasini olib tashlaydi.
 */
const props = withDefaults(
    defineProps<{
        placeholder?: string;
        /** Belgilar chegarasi (faqat matn, teglarsiz) */
        maxlength?: number;
        invalid?: boolean;
        /** Tahrirlash maydonining minimal balandligi */
        minHeight?: string;
    }>(),
    { placeholder: '', maxlength: 20000, invalid: false, minHeight: '18rem' },
);

const model = defineModel<string>({ required: true });

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            code: false,
            codeBlock: false,
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                protocols: ['http', 'https', 'mailto', 'tel'],
            },
        }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Placeholder.configure({ placeholder: () => props.placeholder }),
        CharacterCount.configure({ limit: props.maxlength }),
    ],
    editorProps: {
        attributes: {
            class: 'article-prose max-w-none px-4 py-3 outline-none',
        },
    },
    onUpdate: ({ editor: instance }) => {
        model.value = instance.isEmpty ? '' : instance.getHTML();
    },
});

// Tashqaridan o'zgarsa (masalan, boshqa yozuvni ochganda) — muharrirga yuklanadi
watch(model, (value) => {
    const instance = editor.value;

    if (!instance) {
        return;
    }

    const current = instance.isEmpty ? '' : instance.getHTML();

    if (value !== current) {
        instance.commands.setContent(value || '', { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const characters = computed(
    () => editor.value?.storage.characterCount.characters() ?? 0,
);

type Tool = {
    key: string;
    icon: Component;
    label: string;
    run: () => void;
    active?: () => boolean;
    disabled?: () => boolean;
};

function chain() {
    return editor.value!.chain().focus();
}

const groups = computed<Tool[][]>(() => [
    [
        {
            key: 'paragraph',
            icon: Pilcrow,
            label: t('Oddiy matn'),
            run: () => chain().setParagraph().run(),
            active: () => !!editor.value?.isActive('paragraph'),
        },
        {
            key: 'h2',
            icon: Heading2,
            label: t('Sarlavha'),
            run: () => chain().toggleHeading({ level: 2 }).run(),
            active: () => !!editor.value?.isActive('heading', { level: 2 }),
        },
        {
            key: 'h3',
            icon: Heading3,
            label: t('Kichik sarlavha'),
            run: () => chain().toggleHeading({ level: 3 }).run(),
            active: () => !!editor.value?.isActive('heading', { level: 3 }),
        },
    ],
    [
        {
            key: 'bold',
            icon: Bold,
            label: t('Qalin'),
            run: () => chain().toggleBold().run(),
            active: () => !!editor.value?.isActive('bold'),
        },
        {
            key: 'italic',
            icon: Italic,
            label: t('Kursiv'),
            run: () => chain().toggleItalic().run(),
            active: () => !!editor.value?.isActive('italic'),
        },
        {
            key: 'underline',
            icon: Underline,
            label: t('Tagiga chizilgan'),
            run: () => chain().toggleUnderline().run(),
            active: () => !!editor.value?.isActive('underline'),
        },
        {
            key: 'strike',
            icon: Strikethrough,
            label: t('Ustidan chizilgan'),
            run: () => chain().toggleStrike().run(),
            active: () => !!editor.value?.isActive('strike'),
        },
    ],
    [
        {
            key: 'bullet',
            icon: List,
            label: t("Belgili ro'yxat"),
            run: () => chain().toggleBulletList().run(),
            active: () => !!editor.value?.isActive('bulletList'),
        },
        {
            key: 'ordered',
            icon: ListOrdered,
            label: t("Raqamli ro'yxat"),
            run: () => chain().toggleOrderedList().run(),
            active: () => !!editor.value?.isActive('orderedList'),
        },
        {
            key: 'quote',
            icon: Quote,
            label: t('Iqtibos'),
            run: () => chain().toggleBlockquote().run(),
            active: () => !!editor.value?.isActive('blockquote'),
        },
        {
            key: 'hr',
            icon: Minus,
            label: t('Ajratuvchi chiziq'),
            run: () => chain().setHorizontalRule().run(),
        },
    ],
    [
        {
            key: 'left',
            icon: AlignLeft,
            label: t('Chapga'),
            run: () => chain().setTextAlign('left').run(),
            active: () => !!editor.value?.isActive({ textAlign: 'left' }),
        },
        {
            key: 'center',
            icon: AlignCenter,
            label: t("O'rtaga"),
            run: () => chain().setTextAlign('center').run(),
            active: () => !!editor.value?.isActive({ textAlign: 'center' }),
        },
        {
            key: 'right',
            icon: AlignRight,
            label: t("O'ngga"),
            run: () => chain().setTextAlign('right').run(),
            active: () => !!editor.value?.isActive({ textAlign: 'right' }),
        },
        {
            key: 'justify',
            icon: AlignJustify,
            label: t('Ikki tomonga'),
            run: () => chain().setTextAlign('justify').run(),
            active: () => !!editor.value?.isActive({ textAlign: 'justify' }),
        },
    ],
    [
        {
            key: 'link',
            icon: Link2,
            label: t('Havola'),
            run: openLink,
            active: () => !!editor.value?.isActive('link'),
        },
        {
            key: 'unlink',
            icon: Link2Off,
            label: t('Havolani olib tashlash'),
            run: () => chain().extendMarkRange('link').unsetLink().run(),
            disabled: () => !editor.value?.isActive('link'),
        },
        {
            key: 'clear',
            icon: RemoveFormatting,
            label: t('Formatlashni tozalash'),
            run: () => chain().unsetAllMarks().clearNodes().run(),
        },
    ],
    [
        {
            key: 'undo',
            icon: Undo2,
            label: t('Bekor qilish'),
            run: () => chain().undo().run(),
            disabled: () => !editor.value?.can().undo(),
        },
        {
            key: 'redo',
            icon: Redo2,
            label: t('Qaytarish'),
            run: () => chain().redo().run(),
            disabled: () => !editor.value?.can().redo(),
        },
    ],
]);

// ——— Havola kiritish paneli
const linkOpen = ref(false);
const linkUrl = ref('');
const linkInput = ref<HTMLInputElement | null>(null);

function openLink(): void {
    linkUrl.value = (editor.value?.getAttributes('link').href as string) ?? '';
    linkOpen.value = true;
    nextTick(() => linkInput.value?.focus());
}

function applyLink(): void {
    let url = linkUrl.value.trim();

    if (url === '') {
        chain().extendMarkRange('link').unsetLink().run();
    } else {
        if (!/^(https?:|mailto:|tel:|\/)/i.test(url)) {
            url = `https://${url}`;
        }

        chain().extendMarkRange('link').setLink({ href: url }).run();
    }

    linkOpen.value = false;
}
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-lg border bg-white shadow-[0_1px_2px_rgba(0,30,60,0.04)] transition focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-100',
                invalid
                    ? 'border-red-400 ring-4 ring-red-100'
                    : 'border-line hover:border-navy-200',
            )
        "
    >
        <!-- Asboblar paneli -->
        <div
            class="flex flex-wrap items-center gap-1 border-b border-line bg-[#f8fafd] px-2 py-1.5"
            role="toolbar"
            :aria-label="t('Matnni formatlash')"
        >
            <template v-for="(group, index) in groups" :key="index">
                <span
                    v-if="index > 0"
                    class="mx-0.5 h-5 w-px bg-line"
                    aria-hidden="true"
                />
                <button
                    v-for="tool in group"
                    :key="tool.key"
                    type="button"
                    :title="tool.label"
                    :aria-label="tool.label"
                    :aria-pressed="tool.active ? tool.active() : undefined"
                    :disabled="
                        !editor || (tool.disabled ? tool.disabled() : false)
                    "
                    :class="
                        cn(
                            'flex size-8 items-center justify-center rounded-md transition-all duration-150 disabled:pointer-events-none disabled:opacity-35',
                            tool.active?.()
                                ? 'bg-brand-600 text-white shadow-[0_6px_14px_-8px_rgba(0,108,246,0.9)]'
                                : 'text-navy-600 hover:-translate-y-px hover:bg-white hover:text-brand-700 hover:shadow-sm',
                        )
                    "
                    @click="tool.run"
                >
                    <component :is="tool.icon" class="size-4" />
                </button>
            </template>
        </div>

        <!-- Havola paneli -->
        <form
            v-if="linkOpen"
            class="flex items-center gap-2 border-b border-line bg-brand-50/50 px-3 py-2"
            @submit.prevent="applyLink"
        >
            <Link2 class="size-4 shrink-0 text-brand-600" />
            <input
                ref="linkInput"
                v-model="linkUrl"
                type="text"
                inputmode="url"
                placeholder="https://…"
                class="h-8 min-w-0 flex-1 rounded-md border border-line bg-white px-2.5 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100"
                @keydown.esc.prevent="linkOpen = false"
            />
            <button
                type="submit"
                class="flex size-8 items-center justify-center rounded-md bg-brand-600 text-white transition-colors hover:bg-brand-500"
                :aria-label="t('Saqlash')"
            >
                <Check class="size-4" />
            </button>
            <button
                type="button"
                class="flex size-8 items-center justify-center rounded-md text-navy-500 transition-colors hover:bg-white hover:text-navy-900"
                :aria-label="t('Bekor qilish')"
                @click="linkOpen = false"
            >
                <X class="size-4" />
            </button>
        </form>

        <EditorContent
            :editor="editor"
            class="rich-editor overflow-y-auto"
            :style="{ minHeight, maxHeight: '36rem' }"
        />

        <div
            class="flex justify-end border-t border-line bg-[#fafbfd] px-3 py-1 text-[11px] text-navy-400 tabular-nums"
        >
            {{ formatNumber(characters) }} / {{ formatNumber(maxlength) }}
        </div>
    </div>
</template>

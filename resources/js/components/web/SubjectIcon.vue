<script setup lang="ts">
import {
    BookMarked,
    BookOpen,
    Feather,
    Flower2,
    Lightbulb,
    Network,
    Palette,
    UserRound,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';

/**
 * Ilmiy yo'nalish ikonkasi (slug bo'yicha, database/seeders/SubjectSeeder.php).
 * Admin qo'shgan yangi yo'nalishlar uchun — kitob belgisi ikonkasi.
 */
const props = defineProps<{ slug?: string | null }>();

const icons: Record<string, Component> = {
    history: BookOpen,
    ethnology: Users,
    ethnography: Flower2,
    anthropology: UserRound,
    philosophy: Lightbulb,
    philology: Feather,
    sociology: Network,
    'social-sciences': Network,
    'cultural-studies': Palette,
};

const icon = computed(() => (props.slug && icons[props.slug]) || BookMarked);
</script>

<template>
    <component :is="icon" aria-hidden="true" />
</template>

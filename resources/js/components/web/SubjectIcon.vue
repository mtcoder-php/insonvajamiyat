<script setup lang="ts">
import {
    BookOpen,
    Feather,
    Flower2,
    Landmark,
    Lightbulb,
    Network,
    Palette,
    PersonStanding,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';

/**
 * Ilmiy yo'nalish ikonkasi (slug bo'yicha, database/seeders/SubjectSeeder.php).
 * Admin qo'shgan yangi yo'nalishlar uchun — kitob ikonkasi.
 */
const props = defineProps<{ slug?: string | null }>();

const icons: Record<string, Component> = {
    history: Landmark,
    ethnology: Users,
    ethnography: Flower2,
    anthropology: PersonStanding,
    philosophy: Lightbulb,
    philology: Feather,
    sociology: Network,
    'cultural-studies': Palette,
};

const icon = computed(() => (props.slug && icons[props.slug]) || BookOpen);
</script>

<template>
    <component :is="icon" aria-hidden="true" />
</template>

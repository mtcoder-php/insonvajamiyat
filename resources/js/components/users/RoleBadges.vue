<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { RoleName, UserRole } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Rollar — har bir rol o'z rangida.
 */
defineProps<{ roles: UserRole[] }>();

const tones: Record<RoleName, string> = {
    super_admin: 'bg-navy-900 text-white ring-navy-900',
    chief_editor: 'bg-violet-50 text-violet-700 ring-violet-200',
    editor: 'bg-brand-50 text-brand-700 ring-brand-200',
    reviewer: 'bg-amber-50 text-amber-700 ring-amber-200',
    layout_editor: 'bg-sky-50 text-sky-700 ring-sky-200',
    content_manager: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    author: 'bg-[#f6f1e7] text-gold-700 ring-gold-200',
};
</script>

<template>
    <span v-if="roles.length" class="inline-flex flex-wrap gap-1">
        <span
            v-for="role in roles"
            :key="role.value"
            :class="
                cn(
                    'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                    tones[role.value],
                )
            "
        >
            {{ role.label }}
        </span>
    </span>
    <span v-else class="text-xs text-navy-400">{{ t("Rol yo'q") }}</span>
</template>

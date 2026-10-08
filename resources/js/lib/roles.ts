import { t, tk } from '@/lib/i18n';
import type { RoleName } from '@/types';

/** Rol nomlari (o'zbekcha kalit) — app/Enums/RoleName.php::label() bilan bir xil; ko'rsatishda t() */
export const roleLabels: Record<RoleName, string> = {
    super_admin: tk('Bosh administrator'),
    chief_editor: tk('Bosh muharrir'),
    editor: tk('Muharrir'),
    reviewer: tk('Taqrizchi'),
    layout_editor: tk('Texnik xodim'),
    content_manager: tk('Kontent-menejer'),
    author: tk('Muallif'),
};

/** Foydalanuvchining eng yuqori roli (header'da ko'rsatish uchun) */
const priority: RoleName[] = [
    'super_admin',
    'chief_editor',
    'editor',
    'layout_editor',
    'content_manager',
    'reviewer',
    'author',
];

export function primaryRoleLabel(roles: RoleName[]): string {
    const role = priority.find((r) => roles.includes(r));

    return role ? t(roleLabels[role]) : t('Foydalanuvchi');
}

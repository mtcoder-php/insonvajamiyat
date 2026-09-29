import type { RoleName } from '@/types';

/** Rol nomlari o'zbek tilida — app/Enums/RoleName.php::label() bilan bir xil */
export const roleLabels: Record<RoleName, string> = {
    super_admin: 'Bosh administrator',
    chief_editor: 'Bosh muharrir',
    editor: 'Muharrir',
    reviewer: 'Taqrizchi',
    layout_editor: 'Texnik xodim',
    content_manager: 'Kontent-menejer',
    author: 'Muallif',
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

    return role ? roleLabels[role] : 'Foydalanuvchi';
}

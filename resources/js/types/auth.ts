export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

/** Rol nomlari — app/Enums/RoleName.php bilan bir xil */
export type RoleName =
    | 'super_admin'
    | 'chief_editor'
    | 'editor'
    | 'reviewer'
    | 'layout_editor'
    | 'content_manager'
    | 'author';

/**
 * HandleInertiaRequests::authPayload() bilan mos.
 * permissions: Super Admin uchun ['*'].
 */
export type Auth = {
    user: User | null;
    roles: RoleName[];
    permissions: string[];
    isStaff: boolean;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

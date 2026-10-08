export type User = {
    id: number;
    name: string;
    email: string;
    /** Profil rasmi URL (HandleInertiaRequests::authPayload) */
    avatar?: string | null;
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

/** Google / ORCID orqali kirish — App\Services\Auth\OAuth\OAuthProviders::enabled() */
export type SocialProviderKey = 'google' | 'orcid';

export type SocialProviderOption = {
    key: SocialProviderKey;
    label: string;
    /** public/images/social/{key}.svg bo'lsa — rasmiy belgi, aks holda umumiy ikonka */
    icon: string | null;
};

/** Sozlamalar → Xavfsizlik: bog'langan akkauntlar (SecurityController::edit) */
export type SocialAccountItem = SocialProviderOption & {
    enabled: boolean;
    linked: boolean;
    email: string | null;
    identifier: string | null;
    linkedAt: string | null;
};

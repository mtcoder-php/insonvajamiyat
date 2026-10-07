/* ------------------------------------------------------------------
 * Admin → Rollar va ruxsatlar (RoleMatrix), Tizim sozlamalari (SystemSettings)
 * ------------------------------------------------------------------ */

export type RoleRow = {
    name: string;
    label: string;
    isStaff: boolean;
    locked: boolean;
    users: number;
    permissions: string[];
    defaults: string[];
    isDefault: boolean;
    usersUrl: string;
    updateUrl: string | null;
    resetUrl: string | null;
};

export type PermissionGroup = {
    key: string;
    label: string;
    permissions: { value: string; label: string; forAuthors: boolean }[];
};

export type RolesPageProps = {
    roles: RoleRow[];
    groups: PermissionGroup[];
};

export type SystemTab = 'journal' | 'contacts' | 'payment' | 'mail' | 'status';

export type JournalSettings = {
    name: string | null;
    subtitle: string | null;
    description: string | null;
    issn: string | null;
    eissn: string | null;
    doi_prefix: string | null;
    frequency: string | null;
    plagiarism_max: number | string | null;
    contact_email: string | null;
    contact_phone: string | null;
    contact_address: string | null;
    social_telegram: string | null;
    social_facebook: string | null;
    social_instagram: string | null;
    social_youtube: string | null;
    social_linkedin: string | null;
    payment_recipient: string | null;
    payment_bank: string | null;
    payment_account: string | null;
    payment_mfo: string | null;
    payment_inn: string | null;
};

export type MailSettings = {
    mailer: 'smtp' | 'log';
    host: string | null;
    port: number | string | null;
    scheme: 'smtp' | 'smtps';
    username: string | null;
    from_address: string | null;
    from_name: string | null;
};

export type SystemStatusRow = {
    key: string;
    label: string;
    value: string;
    state: 'ok' | 'warning' | 'error' | 'off';
    hint: string | null;
};

export type SystemPageProps = {
    tab: SystemTab;
    /** `journal` nomi umumiy (shared) prop bilan to'qnashmasligi uchun */
    journalForm: JournalSettings;
    mailForm: MailSettings;
    mailPassword: { set: boolean; source: 'database' | 'env' | 'none' };
    status: SystemStatusRow[];
    urls: { index: string; journal: string; mail: string; mailTest: string };
};

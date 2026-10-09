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
    readiness: LaunchReadinessReport | null;
    urls: { index: string; journal: string; mail: string; mailTest: string };
};

/** "Ishga tushirishga tayyorlik" — App\\Services\\Settings\\LaunchReadiness::report */
export type LaunchReadinessCheck = SystemStatusRow & { group: string };

export type LaunchReadinessReport = {
    ready: boolean;
    counts: Record<SystemStatusRow['state'], number>;
    groups: { key: string; label: string; checks: LaunchReadinessCheck[] }[];
};

/* ---------- Zaxira nusxa (BackupService) ---------- */

export type BackupStatus = 'queued' | 'running' | 'done' | 'failed';

export type BackupItem = {
    uuid: string;
    type: 'full' | 'database' | 'files';
    typeLabel: string;
    status: BackupStatus;
    trigger: 'manual' | 'schedule';
    size: number | null;
    filesCount: number | null;
    durationMs: number | null;
    error: string | null;
    creator: string | null;
    createdAt: string | null;
    fileName: string | null;
    downloadUrl: string | null;
    destroyUrl: string;
};

export type BackupSettings = {
    enabled: boolean;
    time: string;
    type: 'full' | 'database' | 'files';
    keep: number;
};

export type BackupsPageProps = {
    backups: BackupItem[];
    stats: {
        count: number;
        totalSize: number;
        last: { createdAt: string | null; type: string } | null;
        freeSpace: number | null;
        nextRun: string | null;
    };
    settings: BackupSettings;
    types: { value: BackupSettings['type']; label: string }[];
    database: string;
    urls: { store: string; settings: string };
};

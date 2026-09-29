/** HandleInertiaRequests::journalPayload() bilan mos (config/journal.php) */
export type JournalSocialNetwork =
    | 'telegram'
    | 'facebook'
    | 'instagram'
    | 'youtube'
    | 'linkedin';

export type Journal = {
    name: string;
    subtitle: string;
    description: string;
    issn: string | null;
    contact: {
        email: string | null;
        phone: string | null;
        address: string | null;
    };
    socials: Partial<Record<JournalSocialNetwork, string>>;
};

export type NotificationSummary = {
    unread: number;
};

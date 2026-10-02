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
    eissn: string | null;
    doiPrefix: string | null;
    frequency: string | null;
    contact: {
        email: string | null;
        phone: string | null;
        address: string | null;
    };
    socials: Partial<Record<JournalSocialNetwork, string>>;
};

/** Bildirishnoma (App\Services\Notifications\NotificationCenter::item) */
export type NotificationItem = {
    id: string;
    kind: string;
    title: string;
    articleTitle: string | null;
    body: string | null;
    url: string | null;
    openUrl: string;
    read: boolean;
    createdAt: string | null;
};

/** Header'dagi qo'ng'iroqcha: o'qilmaganlar soni va so'nggi bildirishnomalar */
export type NotificationSummary = {
    unread: number;
    items: NotificationItem[];
};

/** Sayt tillari (HandleInertiaRequests → locales, App\Enums\Language) */
export type LocaleCode = 'uz' | 'ru' | 'en';

export type LocaleOption = {
    code: LocaleCode;
    label: string;
};

import { t } from '@/lib/i18n';
import type { JournalSocialNetwork } from '@/types';

/** Ijtimoiy tarmoq nomi (brend nomlari tarjima qilinmaydi) */
const NAMES: Record<JournalSocialNetwork, string> = {
    telegram: 'Telegram',
    facebook: 'Facebook',
    instagram: 'Instagram',
    youtube: 'YouTube',
    linkedin: 'LinkedIn',
};

/** Ekran o'quvchi uchun havola nomi: "Telegram (yangi oynada ochiladi)" */
export function socialLabel(network: JournalSocialNetwork): string {
    return t(':name (yangi oynada ochiladi)', {
        name: NAMES[network] ?? network,
    });
}

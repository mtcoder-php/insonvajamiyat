/**
 * Ilmiy yo'nalishlar uchun rang yorliqlari (maqola kartochkasidagi badge).
 * Tailwind sinflari to'liq yozilgan — build vaqtida topilishi uchun.
 */
type SubjectTone = { badge: string; dot: string };

const tones: Record<string, SubjectTone> = {
    history: { badge: 'bg-teal-50 text-teal-800', dot: 'bg-teal-600' },
    ethnology: {
        badge: 'bg-emerald-50 text-emerald-800',
        dot: 'bg-emerald-600',
    },
    ethnography: { badge: 'bg-rose-50 text-rose-800', dot: 'bg-rose-500' },
    anthropology: {
        badge: 'bg-violet-50 text-violet-800',
        dot: 'bg-violet-600',
    },
    philosophy: { badge: 'bg-indigo-50 text-indigo-800', dot: 'bg-indigo-600' },
    philology: { badge: 'bg-orange-50 text-orange-800', dot: 'bg-orange-500' },
    sociology: { badge: 'bg-sky-50 text-sky-800', dot: 'bg-sky-600' },
    'social-sciences': { badge: 'bg-sky-50 text-sky-800', dot: 'bg-sky-600' },
    'cultural-studies': {
        badge: 'bg-amber-50 text-amber-800',
        dot: 'bg-amber-500',
    },
};

const fallback: SubjectTone = {
    badge: 'bg-navy-50 text-navy-800',
    dot: 'bg-navy-600',
};

export function subjectTone(slug: string | null | undefined): SubjectTone {
    return (slug && tones[slug]) || fallback;
}

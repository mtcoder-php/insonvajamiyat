/**
 * Admin va profil formalari uchun umumiy Tailwind klasslari (yorug' dizayn).
 */
export const inputClass =
    'h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-navy-900 shadow-[0_1px_2px_rgba(0,30,60,0.04)] outline-none transition placeholder:text-navy-300 hover:border-navy-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:bg-navy-50/60 aria-invalid:border-red-400 aria-invalid:ring-red-100';

export const textareaClass = inputClass.replace(
    'h-10',
    'min-h-24 py-2.5 leading-relaxed',
);

export const primaryButtonClass =
    'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 hover:shadow-[0_12px_24px_-10px_rgba(0,108,246,0.9)] active:translate-y-0 disabled:pointer-events-none disabled:opacity-60';

export const secondaryButtonClass =
    'inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-line bg-white px-4 text-sm font-semibold text-navy-800 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700 hover:shadow-sm disabled:pointer-events-none disabled:opacity-60';

export const dangerButtonClass =
    'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(220,38,38,0.8)] transition-all hover:-translate-y-px hover:bg-red-500 disabled:pointer-events-none disabled:opacity-60';

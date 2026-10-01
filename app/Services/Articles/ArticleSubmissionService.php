<?php

namespace App\Services\Articles;

use App\Enums\ArticleFileType;
use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVersionType;
use App\Enums\Language;
use App\Enums\SubmissionStep;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Muallif tomonidan yangi maqola yuborish (7 bosqichli forma).
 *
 * Maqola birinchi bosqichdan keyin "Qoralama" sifatida saqlanadi, keyingi bosqichlar
 * shu qoralamani to'ldiradi. Yuborishda barcha bosqichlar to'liqligi tekshiriladi,
 * birinchi versiya (article_versions) yaratiladi va holat ArticleWorkflow orqali
 * "Yuborildi" ga, pullik turda esa "To'lov kutilmoqda" ga o'tkaziladi.
 */
class ArticleSubmissionService
{
    public const MAX_AUTHORS = 10;

    public const TITLE_MIN = 10;

    public const TITLE_MAX = 300;

    public const ABSTRACT_MIN = 100;

    public const ABSTRACT_MAX = 3000;

    public const REFERENCES_MAX = 20000;

    public const KEYWORDS_MIN = 3;

    public const KEYWORDS_MAX = 10;

    public const KEYWORD_MAX_LENGTH = 60;

    public const SUPPLEMENTARY_MAX = 10;

    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly ArticleFileService $files,
    ) {}

    /**
     * 1-bosqich: yangi qoralama. Yuboruvchi birinchi muallif (aloqa uchun mas'ul) sifatida qo'shiladi.
     *
     * @param  array{article_type_id: int, subject_id: int, language: string, title: string, udc?: string|null}  $data
     */
    public function createDraft(User $user, array $data): Article
    {
        return DB::transaction(function () use ($user, $data): Article {
            $article = new Article([
                'article_type_id' => $data['article_type_id'],
                'subject_id' => $data['subject_id'],
                'language' => $data['language'],
                'udc' => $data['udc'] ?? null,
            ]);
            $article->setTranslation('title', $data['language'], $data['title']);
            $article->forceFill([
                'submitter_id' => $user->id,
                'status' => ArticleStatus::Draft,
                'payment_status' => ArticlePaymentStatus::Unpaid,
            ])->save();

            $article->authors()->create([
                ...$this->profileAuthor($user),
                'user_id' => $user->id,
                'is_corresponding' => true,
                'sort_order' => 0,
            ]);

            $this->workflow->recordInitial($article, $user);

            return $article;
        });
    }

    /**
     * 1-bosqich (tahrirlash): tur, yo'nalish, til, asosiy tildagi sarlavha, UDK.
     *
     * @param  array{article_type_id: int, subject_id: int, language: string, title: string, udc?: string|null}  $data
     */
    public function updateDetails(Article $article, array $data): void
    {
        $article->fill([
            'article_type_id' => $data['article_type_id'],
            'subject_id' => $data['subject_id'],
            'language' => $data['language'],
            'udc' => $data['udc'] ?? null,
        ]);
        $article->setTranslation('title', $data['language'], $data['title']);
        $article->save();
    }

    /**
     * 2-bosqich: mualliflar ro'yxati (tartib — ro'yxatdagi o'rni bo'yicha).
     * "Men" deb belgilangan muallif yuboruvchiga, qolganlari email bo'yicha tizimdagi
     * foydalanuvchiga bog'lanadi (hammuallif ham maqolani o'z kabinetida ko'radi).
     *
     * @param  array<int, array<string, mixed>>  $authors
     */
    public function syncAuthors(Article $article, User $submitter, array $authors, int $corresponding): void
    {
        DB::transaction(function () use ($article, $submitter, $authors, $corresponding): void {
            $article->authors()->delete();

            foreach (array_values($authors) as $index => $author) {
                $email = $this->nullableString($author['email'] ?? null);
                $email = $email !== null ? mb_strtolower($email) : null;
                $isMe = (bool) ($author['is_me'] ?? false);

                $article->authors()->create([
                    'user_id' => $isMe
                        ? $submitter->id
                        : ($email !== null ? User::query()->where('email', $email)->value('id') : null),
                    'last_name' => trim((string) $author['last_name']),
                    'first_name' => trim((string) $author['first_name']),
                    'middle_name' => $this->nullableString($author['middle_name'] ?? null),
                    'email' => $email,
                    'organization' => $this->nullableString($author['organization'] ?? null),
                    'position' => $this->nullableString($author['position'] ?? null),
                    'academic_degree' => $this->nullableString($author['academic_degree'] ?? null),
                    'orcid' => $this->nullableString($author['orcid'] ?? null),
                    'is_corresponding' => $index === $corresponding,
                    'sort_order' => $index,
                ]);
            }

            $article->touch();
        });
    }

    /**
     * 3-bosqich: annotatsiya (uch tilda), boshqa tillardagi sarlavha va adabiyotlar ro'yxati.
     *
     * @param  array<string, string|null>  $titles
     * @param  array<string, string|null>  $abstracts
     */
    public function updateAbstract(Article $article, array $titles, array $abstracts, ?string $references): void
    {
        foreach (Language::cases() as $language) {
            $locale = $language->value;

            // Asosiy tildagi sarlavha 1-bosqichda kiritiladi
            if ($locale !== $article->language) {
                $this->setOrForget($article, 'title', $locale, $titles[$locale] ?? null);
            }

            $this->setOrForget($article, 'abstract', $locale, $abstracts[$locale] ?? null);
        }

        $article->references = $this->nullableString($references);
        $article->save();
    }

    /**
     * 4-bosqich: kalit so'zlar (tillar bo'yicha; takrorlar va bo'sh qiymatlar olib tashlanadi).
     *
     * @param  array<string, array<int, mixed>|null>  $keywords
     */
    public function updateKeywords(Article $article, array $keywords): void
    {
        foreach (Language::cases() as $language) {
            $words = self::normalizeKeywords($keywords[$language->value] ?? []);

            if ($words === []) {
                $article->forgetTranslation('keywords', $language->value);
            } else {
                $article->setTranslation('keywords', $language->value, $words);
            }
        }

        $article->save();
    }

    /**
     * 5-bosqich: fayl yuklash. Asosiy fayl bitta — yangisi eskisining o'rnini egallaydi.
     */
    public function uploadFile(Article $article, UploadedFile $file, ArticleFileType $type, User $user): ArticleFile
    {
        if ($type === ArticleFileType::Supplementary
            && $article->files()->where('type', $type->value)->count() >= self::SUPPLEMENTARY_MAX) {
            throw ValidationException::withMessages([
                'file' => __("Qo'shimcha fayllar soni :max tadan oshmasligi kerak.", ['max' => self::SUPPLEMENTARY_MAX]),
            ]);
        }

        $previous = $type === ArticleFileType::Manuscript
            ? $article->files()->where('type', $type->value)->get()->all()
            : [];

        $stored = $this->files->store($article, $file, $type, $user);

        foreach ($previous as $old) {
            $this->files->delete($old);
        }

        $article->touch();

        return $stored;
    }

    public function deleteFile(Article $article, ArticleFile $file): void
    {
        $this->files->delete($file);
        $article->touch();
    }

    /**
     * Bosqichlar bo'yicha kamchiliklar: [bosqich raqami => [xabar, ...]] (bo'sh — to'liq).
     *
     * @return array<int, array<int, string>>
     */
    public function checklist(Article $article): array
    {
        $article->loadMissing(['authors', 'files']);
        $language = $article->language;
        /** @var array<int, array<int, string>> $issues */
        $issues = [];

        foreach (SubmissionStep::cases() as $step) {
            if ($step->isDataStep()) {
                $issues[$step->value] = [];
            }
        }

        // 1. Ma'lumotlar
        if ($article->subject_id === null) {
            $issues[SubmissionStep::Details->value][] = __("Ilmiy yo'nalishni tanlang.");
        }

        if (mb_strlen($this->translation($article, 'title', $language)) < self::TITLE_MIN) {
            $issues[SubmissionStep::Details->value][] = __('Maqola sarlavhasini kiriting.');
        }

        // 2. Mualliflar
        $authors = $article->authors;
        $corresponding = $authors->firstWhere('is_corresponding', true);

        if ($authors->isEmpty()) {
            $issues[SubmissionStep::Authors->value][] = __("Kamida bitta muallif qo'shing.");
        } elseif ($corresponding === null) {
            $issues[SubmissionStep::Authors->value][] = __("Aloqa uchun mas'ul muallifni belgilang.");
        } elseif ($corresponding->email === null) {
            $issues[SubmissionStep::Authors->value][] = __("Aloqa uchun mas'ul muallifning elektron pochtasini kiriting.");
        }

        if ($authors->contains(fn (ArticleAuthor $author): bool => $author->organization === null)) {
            $issues[SubmissionStep::Authors->value][] = __('Barcha mualliflar uchun tashkilotni kiriting.');
        }

        // 3. Annotatsiya
        if (mb_strlen($this->translation($article, 'abstract', $language)) < self::ABSTRACT_MIN) {
            $issues[SubmissionStep::Abstract->value][] = __('Maqola tilida annotatsiya kiriting (kamida :min belgi).', ['min' => self::ABSTRACT_MIN]);
        }

        // 4. Kalit so'zlar
        $keywords = $article->getTranslation('keywords', $language, false);

        if (! is_array($keywords) || count($keywords) < self::KEYWORDS_MIN) {
            $issues[SubmissionStep::Keywords->value][] = __("Maqola tilida kamida :min ta kalit so'z kiriting.", ['min' => self::KEYWORDS_MIN]);
        }

        // 5. Fayllar
        if (! $article->files->contains('type', ArticleFileType::Manuscript)) {
            $issues[SubmissionStep::Files->value][] = __('Maqolaning asosiy faylini (.docx yoki .pdf) yuklang.');
        }

        return $issues;
    }

    /**
     * @param  array<int, array<int, string>>|null  $checklist
     */
    public function isComplete(Article $article, ?array $checklist = null): bool
    {
        foreach ($checklist ?? $this->checklist($article) as $issues) {
            if ($issues !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * 7-bosqich: yuborish. Pullik turda maqola "To'lov kutilmoqda" holatiga o'tadi
     * (to'lovni hozircha admin qo'lda tasdiqlaydi), bepul turda to'lovdan ozod qilinadi.
     */
    public function submit(Article $article, User $user): void
    {
        if ($article->status !== ArticleStatus::Draft) {
            throw ValidationException::withMessages(['submit' => __('Faqat qoralama holatidagi maqolani yuborish mumkin.')]);
        }

        if (! $this->isComplete($article)) {
            throw ValidationException::withMessages(['submit' => __("Formaning barcha bosqichlarini to'ldiring.")]);
        }

        DB::transaction(function () use ($article, $user): void {
            $type = $article->articleType()->firstOrFail();
            $isPaid = (float) $type->price > 0;

            $article->forceFill([
                'payment_status' => $isPaid ? ArticlePaymentStatus::Unpaid : ArticlePaymentStatus::Waived,
            ]);

            $version = $article->versions()->create([
                'version_number' => 1,
                'type' => ArticleVersionType::Submission,
                'review_round' => 0,
                'language' => $article->language,
                'created_by' => $user->id,
            ]);

            $article->files()->whereNull('article_version_id')->update(['article_version_id' => $version->id]);

            $this->workflow->transition($article, ArticleStatus::Submitted, $user);

            if ($isPaid) {
                $this->workflow->transition(
                    $article,
                    ArticleStatus::AwaitingPayment,
                    null,
                    __("Nashr to'lovi kutilmoqda: :amount so'm. To'lov tasdiqlangach maqola tahririyat navbatiga o'tadi.", [
                        'amount' => number_format((float) $type->price, 0, '.', ' '),
                    ]),
                );
            }
        });
    }

    /**
     * Qoralamani butunlay o'chirish (fayllari bilan).
     */
    public function deleteDraft(Article $article): void
    {
        $article->files()->get()->each(fn (ArticleFile $file) => $this->files->delete($file));
        $article->forceDelete();
    }

    /**
     * Yuboruvchining profilidan muallif ma'lumotlari (formani oldindan to'ldirish uchun).
     *
     * @return array{last_name: string, first_name: string, middle_name: string|null, email: string, organization: string|null, position: string|null, academic_degree: string|null, orcid: string|null}
     */
    public function profileAuthor(User $user): array
    {
        $profile = $user->authorProfile;
        [$lastName, $firstName] = array_pad(explode(' ', $user->name, 2), 2, '');

        return [
            'last_name' => $profile !== null ? $profile->last_name : $lastName,
            'first_name' => $profile !== null ? $profile->first_name : $firstName,
            'middle_name' => $profile?->middle_name,
            'email' => $user->email,
            'organization' => $profile?->organization,
            'position' => $profile?->position,
            'academic_degree' => $profile?->academic_degree,
            'orcid' => $profile?->orcid,
        ];
    }

    /**
     * Kalit so'zlarni tozalash: trim, bo'sh va takroriy (katta-kichik harfdan qat'i nazar) qiymatlarsiz.
     *
     * @param  array<int, mixed>|null  $words
     * @return array<int, string>
     */
    public static function normalizeKeywords(?array $words): array
    {
        $result = [];

        foreach ($words ?? [] as $word) {
            if (! is_string($word)) {
                continue;
            }

            $word = trim(preg_replace('/\s+/u', ' ', $word) ?? '');

            if ($word !== '' && ! array_key_exists(mb_strtolower($word), $result)) {
                $result[mb_strtolower($word)] = $word;
            }
        }

        return array_values($result);
    }

    private function translation(Article $article, string $key, string $locale): string
    {
        $value = $article->getTranslation($key, $locale, false);

        return is_string($value) ? trim($value) : '';
    }

    private function setOrForget(Article $article, string $key, string $locale, ?string $value): void
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            $article->forgetTranslation($key, $locale);
        } else {
            $article->setTranslation($key, $locale, $value);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

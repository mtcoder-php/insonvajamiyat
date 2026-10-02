<?php

namespace App\Services\Issues;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\IssueStatus;
use App\Models\Article;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Jurnal sonlarini shakllantirish (TZ 4.2.3):
 *   son yaratish (yil, jild, raqam, DOI, muqova) → maqolalarni joylashtirish
 *   (tartib, rukn, sahifalar) → mundarija → butun son PDF.
 *
 * Fayllar `public` diskida: issues/{slug}/cover.*, issues/{slug}/*.pdf —
 * son chop etilgach saytda ochiq yuklab olinadi. Chop etish — 7-bosqich.
 */
class IssueService
{
    public const DISK = MediaUrl::DISK;

    /** Songa joylashtirish mumkin bo'lgan maqola holatlari */
    public const PLACEABLE = [ArticleStatus::Accepted, ArticleStatus::InProduction];

    public function __construct(private readonly AuditLogger $audit) {}

    /** Fayl turlari → journal_issues ustuni */
    public const FILES = [
        'cover' => 'cover_image_path',
        'pdf' => 'full_pdf_path',
        'toc' => 'toc_file_path',
    ];

    /**
     * @param  array{year: int, volume: int|null, number: int, doi: string|null, title: string|null, description: string|null}  $data
     */
    public function create(array $data, User $user): JournalIssue
    {
        $issue = new JournalIssue;
        $this->fill($issue, $data);
        $issue->forceFill(['status' => IssueStatus::Draft, 'created_by' => $user->id])->save();

        $this->audit->log(AuditEvent::IssueCreated, $issue, actor: $user);

        return $issue;
    }

    /**
     * @param  array{year: int, volume: int|null, number: int, doi: string|null, title: string|null, description: string|null}  $data
     */
    public function update(JournalIssue $issue, array $data): void
    {
        $oldSlug = $issue->slug;
        $this->fill($issue, $data);

        DB::transaction(function () use ($issue, $oldSlug): void {
            // Slug o'zgarsa fayllar yangi papkaga ko'chiriladi
            if ($oldSlug !== $issue->slug) {
                foreach (self::FILES as $column) {
                    $path = $issue->getAttribute($column);

                    if (is_string($path) && Storage::disk(self::DISK)->exists($path)) {
                        $newPath = 'issues/'.$issue->slug.'/'.basename($path);
                        Storage::disk(self::DISK)->move($path, $newPath);
                        $issue->setAttribute($column, $newPath);
                    }
                }
            }

            $issue->save();
        });
    }

    /** Faqat qoralama va bo'sh son o'chiriladi */
    public function delete(JournalIssue $issue): void
    {
        if ($issue->status !== IssueStatus::Draft) {
            throw ValidationException::withMessages(['issue' => __("Chop etilgan sonni o'chirib bo'lmaydi.")]);
        }

        if (IssueArticle::query()->where('journal_issue_id', $issue->id)->exists()) {
            throw ValidationException::withMessages(['issue' => __('Sonda maqolalar bor. Avval ularni sondan chiqaring.')]);
        }

        Storage::disk(self::DISK)->deleteDirectory('issues/'.$issue->slug);
        $issue->delete();

        $this->audit->log(AuditEvent::IssueDeleted, $issue);
    }

    /** Muqova, butun son PDF yoki mundarija PDF yuklash */
    public function uploadFile(JournalIssue $issue, string $type, UploadedFile $file): void
    {
        $column = self::FILES[$type] ?? throw new RuntimeException("Unknown issue file type [{$type}].");
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $name = match ($type) {
            'cover' => 'cover',
            'pdf' => 'inson-va-jamiyat-'.$issue->slug,
            default => 'mundarija-'.$issue->slug,
        };

        $this->removeFile($issue, $type);
        $path = $file->storeAs('issues/'.$issue->slug, $name.'-'.Str::lower(Str::random(6)).'.'.$extension, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Issue file could not be stored.');
        }

        $issue->setAttribute($column, $path);
        $issue->save();
    }

    public function removeFile(JournalIssue $issue, string $type): void
    {
        $column = self::FILES[$type] ?? throw new RuntimeException("Unknown issue file type [{$type}].");
        $path = $issue->getAttribute($column);

        if (is_string($path) && $path !== '' && ! str_starts_with($path, 'http')) {
            Storage::disk(self::DISK)->delete($path);
        }

        $issue->setAttribute($column, null);
        $issue->save();
    }

    /**
     * Maqolalarni songa qo'shish (oxiriga). Boshqa sondagi maqola shu songa o'tkaziladi.
     *
     * @param  array<int, int>  $articleIds
     */
    public function attach(JournalIssue $issue, array $articleIds): int
    {
        $articles = Article::query()
            ->whereIn('id', $articleIds)
            ->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, self::PLACEABLE))
            ->get();

        if ($articles->count() !== count(array_unique($articleIds))) {
            throw ValidationException::withMessages([
                'article_ids' => __("Faqat qabul qilingan yoki nashrga tayyorlanayotgan maqolalarni qo'shish mumkin."),
            ]);
        }

        $count = DB::transaction(function () use ($issue, $articles): int {
            $position = (int) IssueArticle::query()->where('journal_issue_id', $issue->id)->max('position');

            foreach ($articles as $article) {
                $placement = IssueArticle::query()->firstOrNew(['article_id' => $article->id]);

                if ($placement->exists && $placement->journal_issue_id === $issue->id) {
                    continue;
                }

                $placement->fill([
                    'journal_issue_id' => $issue->id,
                    'position' => ++$position,
                    'page_from' => null,
                    'page_to' => null,
                ])->save();

                $this->resetApproval($article);
            }

            return $articles->count();
        });

        $this->repaginateIfComplete($issue);

        return $count;
    }

    public function detach(JournalIssue $issue, Article $article): void
    {
        $this->ensureEditable($article);

        DB::transaction(function () use ($issue, $article): void {
            IssueArticle::query()
                ->where('journal_issue_id', $issue->id)
                ->where('article_id', $article->id)
                ->delete();

            $this->renumber($issue);
            $this->resetApproval($article);
        });

        $this->repaginateIfComplete($issue);
    }

    /**
     * Rukn va sahifalar.
     */
    public function updatePlacement(JournalIssue $issue, Article $article, ?string $section, ?int $pageFrom, ?int $pageTo): void
    {
        $this->ensureEditable($article);

        $placement = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->where('article_id', $article->id)
            ->firstOrFail();

        DB::transaction(function () use ($placement, $article, $section, $pageFrom, $pageTo): void {
            $placement->fill([
                'section' => $section !== null && $section !== '' ? [app()->getLocale() => $section] : null,
                'page_from' => $pageFrom,
                'page_to' => $pageTo,
            ])->save();

            if ($pageFrom !== null && $pageTo !== null) {
                $article->forceFill(['pages_count' => $pageTo - $pageFrom + 1]);
            }

            $this->resetApproval($article);
        });
    }

    /**
     * Tartibni saqlash: maqola id lari yangi tartibda.
     *
     * @param  array<int, int>  $articleIds
     */
    public function reorder(JournalIssue $issue, array $articleIds): void
    {
        $placements = IssueArticle::query()->where('journal_issue_id', $issue->id)->get()->keyBy('article_id');

        if ($placements->count() !== count($articleIds) || array_diff($articleIds, $placements->keys()->all()) !== []) {
            throw ValidationException::withMessages(['order' => __("Tartib ro'yxati sondagi maqolalarga mos emas.")]);
        }

        DB::transaction(function () use ($placements, $articleIds): void {
            foreach (array_values($articleIds) as $index => $articleId) {
                $placement = $placements->get($articleId);
                $placement?->forceFill(['position' => $index + 1])->save();
            }
        });

        $this->repaginateIfComplete($issue);
    }

    /**
     * Sondagi barcha maqolalarning hajmi (PDF dagi betlar soni) ma'lum bo'lsa — sahifalarni
     * avtomatik qayta hisoblaydi (tartib o'zgarganda, maqola qo'shilganda/chiqarilganda,
     * yangi yakuniy PDF yuklanganda). Boshlang'ich bet — hozirgi eng kichik bet yoki 1.
     * Chop etilgan maqolasi bor sonda hech narsa o'zgarmaydi.
     *
     * @return bool sahifalar qayta hisoblandimi
     */
    public function repaginateIfComplete(JournalIssue $issue): bool
    {
        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with('article')
            ->get();

        if ($placements->isEmpty()) {
            return false;
        }

        foreach ($placements as $placement) {
            if (! $this->editable($placement->article) || ($placement->article->pages_count ?? 0) < 1) {
                return false;
            }
        }

        $start = max(1, (int) ($placements->min('page_from') ?? 1));

        return $this->paginate($issue, $start)['updated'] > 0;
    }

    /**
     * Sahifalarni ketma-ket hisoblash: har maqola hajmi (pages_count) bo'yicha,
     * $startPage dan boshlab tartib bo'yicha. Hajmi noma'lum maqolada to'xtaydi.
     *
     * @return array{updated: int, missing: array<int, string>}
     */
    public function paginate(JournalIssue $issue, int $startPage): array
    {
        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with('article')
            ->orderBy('position')
            ->get();

        if ($placements->contains(fn (IssueArticle $p): bool => ! $this->editable($p->article))) {
            throw ValidationException::withMessages([
                'start_page' => __("Sonda nashr etilgan maqolalar bor — sahifalarni avtomatik hisoblab bo'lmaydi."),
            ]);
        }

        $missing = $placements
            ->filter(fn (IssueArticle $p): bool => ($p->article->pages_count ?? 0) < 1)
            ->map(fn (IssueArticle $p): string => $p->article->title)
            ->values()
            ->all();

        if ($missing !== []) {
            return ['updated' => 0, 'missing' => $missing];
        }

        DB::transaction(function () use ($placements, $startPage): void {
            $page = $startPage;

            foreach ($placements as $placement) {
                $count = (int) $placement->article->pages_count;
                $from = $page;
                $to = $page + $count - 1;

                if ($placement->page_from !== $from || $placement->page_to !== $to) {
                    $placement->forceFill(['page_from' => $from, 'page_to' => $to])->save();
                    $this->resetApproval($placement->article);
                }

                $page = $to + 1;
            }
        });

        return ['updated' => $placements->count(), 'missing' => []];
    }

    /** Pozitsiyalarni 1..n qilib qayta raqamlash */
    private function renumber(JournalIssue $issue): void
    {
        $placements = IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach ($placements->values() as $index => $placement) {
            $placement->forceFill(['position' => $index + 1])->save();
        }
    }

    /**
     * @param  array{year: int, volume: int|null, number: int, doi: string|null, title: string|null, description: string|null}  $data
     */
    private function fill(JournalIssue $issue, array $data): void
    {
        $locale = app()->getLocale();

        $issue->fill([
            'year' => $data['year'],
            'volume' => $data['volume'],
            'number' => $data['number'],
            'slug' => $data['year'].'-'.$data['number'],
            'doi' => $data['doi'],
        ]);

        foreach (['title', 'description'] as $field) {
            $value = $data[$field];

            if ($value === null || $value === '') {
                $issue->forgetTranslation($field, $locale);
            } else {
                $issue->setTranslation($field, $locale, $value);
            }
        }
    }

    /** Nashr etilgan maqolaning o'rni o'zgarmaydi */
    private function ensureEditable(Article $article): void
    {
        if (! $this->editable($article)) {
            throw ValidationException::withMessages([
                'article' => __("Nashr etilgan maqolaning sondagi o'rnini o'zgartirib bo'lmaydi."),
            ]);
        }
    }

    private function editable(Article $article): bool
    {
        return in_array($article->status, self::PLACEABLE, true);
    }

    /** Joylashuv o'zgarsa bosh muharrir tasdig'i qayta olinadi */
    private function resetApproval(Article $article): void
    {
        $article->forceFill(['chief_editor_approved_by' => null, 'chief_editor_approved_at' => null])->save();
    }
}

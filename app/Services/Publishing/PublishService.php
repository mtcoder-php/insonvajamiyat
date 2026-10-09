<?php

namespace App\Services\Publishing;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\IssueStatus;
use App\Models\Article;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Audit\AuditLogger;
use App\Services\Newsletter\NewsletterCampaignService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saytda chop etish (TZ 4.2.3 — "Sonni chop etish"):
 *
 *   son (qoralama) → tekshiruv: har bir maqola bosh muharrir tasdiqlagan, yakuniy PDF va
 *   sahifalari bor → maqolalar InProduction → Published (slug, published_at) →
 *   son Published (published_at, published_by) → mualliflarga bildirishnoma.
 *
 * Allaqachon chop etilgan songa keyin qo'shilgan maqola alohida chop etiladi (publishArticle).
 */
class PublishService
{
    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly AuditLogger $audit,
        private readonly NewsletterCampaignService $newsletter,
    ) {}

    /**
     * Chop etishga to'sqinlik qiladigan muammolar (bo'sh — tayyor).
     *
     * @return array<int, string>
     */
    public function issueProblems(JournalIssue $issue): array
    {
        if ($issue->status === IssueStatus::Published) {
            return [self::t('Son allaqachon chop etilgan.')];
        }

        $placements = $this->placements($issue);

        if ($placements->isEmpty()) {
            return [self::t('Sonda maqola yo\'q.')];
        }

        $problems = [];

        foreach ($placements as $placement) {
            if ($placement->article->status !== ArticleStatus::Published) {
                $problem = $this->articleProblem($placement);

                if ($problem !== null) {
                    $problems[] = '«'.Str::limit($placement->article->title, 60).'» — '.$problem;
                }
            }
        }

        return $problems;
    }

    /**
     * @return Collection<int, Article> chop etilgan maqolalar
     */
    public function publishIssue(JournalIssue $issue, User $user): Collection
    {
        $problems = $this->issueProblems($issue);

        if ($problems !== []) {
            throw ValidationException::withMessages(['publish' => $problems]);
        }

        $published = DB::transaction(function () use ($issue, $user): Collection {
            $articles = $this->placements($issue)
                ->map(fn (IssueArticle $p): Article => $p->article)
                ->filter(fn (Article $a): bool => $a->status !== ArticleStatus::Published)
                ->values();

            foreach ($articles as $article) {
                $this->markPublished($article, $user);
            }

            $issue->forceFill([
                'status' => IssueStatus::Published,
                'published_at' => now(),
                'published_by' => $user->id,
            ])->save();

            $this->audit->log(AuditEvent::IssuePublished, $issue, ['articles' => $articles->count()], actor: $user);

            return $articles;
        });

        foreach ($published as $article) {
            $this->notify($article);
        }

        // Obunachilarga "Yangi son" xati (sozlama yoqiq bo'lsa; xatolik nashrni buzmaydi)
        $this->newsletter->announceIssue($issue, $user);

        return $published;
    }

    /** Chop etilgan songa keyin qo'shilgan maqolani chop etish */
    public function publishArticle(Article $article, User $user): void
    {
        $placement = $article->placement()->with('issue')->first();

        if ($article->status !== ArticleStatus::InProduction || $placement === null || $placement->issue->status !== IssueStatus::Published) {
            throw ValidationException::withMessages([
                'publish' => self::t('Maqolani alohida chop etish uchun u chop etilgan songa biriktirilgan bo\'lishi kerak. Aks holda butun son chop etiladi.'),
            ]);
        }

        $problem = $this->articleProblem($placement);

        if ($problem !== null) {
            throw ValidationException::withMessages(['publish' => $problem]);
        }

        DB::transaction(function () use ($article, $user): void {
            $this->markPublished($article, $user);
        });

        $this->notify($article);
    }

    /** Maqolani alohida chop etish mumkinmi (chop etilgan son + tayyor maqola) */
    public function canPublishArticle(Article $article): bool
    {
        $placement = $article->placement()->with('issue')->first();

        return $article->status === ArticleStatus::InProduction
            && $placement !== null
            && $placement->issue->status === IssueStatus::Published
            && $this->articleProblem($placement) === null;
    }

    /** URL uchun takrorlanmas slug: sarlavha + id */
    public static function slugFor(Article $article): string
    {
        $base = Str::slug(Str::limit(Str::ascii((string) $article->getTranslation('title', $article->language, true)), 80, ''));

        return ($base !== '' ? $base : 'maqola').'-'.$article->id;
    }

    private function articleProblem(IssueArticle $placement): ?string
    {
        $article = $placement->article;

        return match (true) {
            $article->status !== ArticleStatus::InProduction => self::t('holati: :status', ['status' => $article->status->label()]),
            $article->chief_editor_approved_at === null => self::t('bosh muharrir tasdiqlamagan'),
            ! $article->files()->where('type', ArticleFileType::FinalPdf->value)->exists() => self::t('yakuniy PDF yo\'q'),
            $placement->pages() === null => self::t('sahifalar belgilanmagan'),
            default => null,
        };
    }

    private function markPublished(Article $article, User $user): void
    {
        if ($article->slug === null) {
            $article->slug = self::slugFor($article);
        }

        $this->workflow->transition(
            $article,
            ArticleStatus::Published,
            $user,
            self::t('Maqolangiz jurnalda chop etildi va saytda e\'lon qilindi. Tabriklaymiz!'),
        );
    }

    private function notify(Article $article): void
    {
        $issue = $article->placement()->with('issue')->first()?->issue;

        $article->submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::DECISION,
            self::t('Maqolangiz chop etildi'),
            self::t('«:title» maqolangiz :issue sonida chop etildi. Maqola sahifasi: :url', [
                'title' => $article->title,
                'issue' => $issue !== null ? $issue->label : '',
                'url' => route('articles.show', (string) $article->slug),
            ]),
        ));
    }

    /**
     * @return Collection<int, IssueArticle>
     */
    private function placements(JournalIssue $issue): Collection
    {
        return IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with('article.submitter')
            ->orderBy('position')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $value = __($key, $replace);

        return is_string($value) ? $value : $key;
    }
}

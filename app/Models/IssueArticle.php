<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maqolaning jurnal sonidagi o'rni (issue_articles): rukn, tartib, sahifalar.
 * Bitta maqola faqat bitta songa biriktiriladi (article_id unique).
 *
 * @property int $id
 * @property int $journal_issue_id
 * @property int $article_id
 * @property mixed $section
 * @property int $position
 * @property int|null $page_from
 * @property int|null $page_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JournalIssue $issue
 * @property-read Article $article
 */
#[Fillable(['journal_issue_id', 'article_id', 'section', 'position', 'page_from', 'page_to'])]
class IssueArticle extends Model
{
    protected $table = 'issue_articles';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => 'array',
            'position' => 'integer',
            'page_from' => 'integer',
            'page_to' => 'integer',
        ];
    }

    /** @return BelongsTo<JournalIssue, $this> */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(JournalIssue::class, 'journal_issue_id');
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** "45–52" yoki null */
    public function pages(): ?string
    {
        return $this->page_from !== null && $this->page_to !== null
            ? "{$this->page_from}–{$this->page_to}"
            : null;
    }
}

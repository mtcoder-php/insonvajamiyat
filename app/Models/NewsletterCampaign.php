<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Obunachilarga yuborilgan xat (admin → Obuna).
 *
 * @property int $id
 * @property string $uuid
 * @property string $kind manual | issue
 * @property string $subject
 * @property string $body
 * @property string|null $button_label
 * @property string|null $button_url
 * @property string|null $locale null — barcha tillar
 * @property int|null $journal_issue_id
 * @property string $status queued | sending | sent | failed
 * @property int $recipients_count
 * @property int $sent_count
 * @property int|null $sender_id
 * @property Carbon|null $sent_at
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $sender
 * @property-read JournalIssue|null $issue
 */
#[Fillable(['kind', 'subject', 'body', 'button_label', 'button_url', 'locale', 'journal_issue_id', 'sender_id'])]
class NewsletterCampaign extends Model
{
    use HasUuids;

    public const MANUAL = 'manual';

    public const ISSUE = 'issue';

    public const QUEUED = 'queued';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return BelongsTo<JournalIssue, $this> */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(JournalIssue::class, 'journal_issue_id');
    }

    /**
     * Shu xat yuboriladigan obunachilar: tasdiqlangan + (tanlangan bo'lsa) til.
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function recipients(): Builder
    {
        return NewsletterSubscriber::query()
            ->confirmed()
            ->when($this->locale !== null, fn (Builder $q) => $q->where('locale', $this->locale));
    }
}

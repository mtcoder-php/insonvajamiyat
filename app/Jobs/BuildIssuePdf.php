<?php

namespace App\Jobs;

use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Issues\IssuePdfBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * To'liq son PDF ni navbatda yig'ish (muqova + mundarija + maqolalar).
 */
class BuildIssuePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public readonly int $issueId,
        public readonly ?int $userId = null,
    ) {}

    public function handle(IssuePdfBuilder $builder): void
    {
        $issue = JournalIssue::query()->find($this->issueId);

        if ($issue !== null) {
            $builder->build($issue, $this->userId !== null ? User::query()->find($this->userId) : null);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $issue = JournalIssue::query()->find($this->issueId);

        if ($issue !== null && in_array($issue->pdf_status, [IssuePdfBuilder::QUEUED, IssuePdfBuilder::PROCESSING], true)) {
            app(IssuePdfBuilder::class)->fail($issue, "Son PDF ini yig'ish to'xtadi (vaqt tugadi yoki ichki xato).");
        }
    }
}

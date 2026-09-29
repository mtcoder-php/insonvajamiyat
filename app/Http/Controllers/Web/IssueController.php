<?php

namespace App\Http\Controllers\Web;

use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\ArticleCardResource;
use App\Http\Resources\Web\IssueCardResource;
use App\Models\JournalIssue;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jurnal soni sahifasi (to'liq dizayn — "web jurnal sonlari.png" bosqichida).
 */
class IssueController extends Controller
{
    public function show(JournalIssue $issue): Response
    {
        abort_unless($issue->status === IssueStatus::Published, 404);

        $issue->loadCount('articles')
            ->load(['articles' => fn ($q) => $q->published()->with(['authors', 'subject'])]);

        return Inertia::render('web/issues/Show', [
            'issue' => IssueCardResource::make($issue)->resolve(),
            'articles' => ArticleCardResource::collection($issue->articles)->resolve(),
        ]);
    }
}

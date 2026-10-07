<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Articles\ArticleNoteRequest;
use App\Http\Requests\Admin\Articles\AssignEditorRequest;
use App\Http\Requests\Admin\Articles\EditorialDecisionRequest;
use App\Models\Article;
use App\Models\User;
use App\Services\Editorial\EditorialService;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muharrir ish joyi (admin muharir.png): navbatlar, maqolalar ro'yxati, maqola kartasi
 * va qarorlar. ?queue= — navbat, ?article= — tanlangan maqola (uuid).
 */
class EditorialController extends Controller
{
    public function __construct(
        private readonly EditorialWorkspace $workspace,
        private readonly EditorialService $editorial,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $counts = $this->workspace->counts($user);
        $requested = $request->string('queue')->toString();
        $queue = array_key_exists($requested, EditorialWorkspace::QUEUES)
            ? $requested
            : ($counts['new'] > 0 ? 'new' : 'all');
        $search = $request->string('search')->trim()->limit(100, '')->toString() ?: null;

        $articles = $this->workspace->list($queue, $search, $user);

        // Tanlangan maqola: ?article= (istalgan navbatdan) yoki ro'yxatdagi birinchisi
        $selected = $request->filled('article')
            ? $this->workspace->queueQuery('all', $user)->where('uuid', $request->string('article')->toString())->first()
            : $articles->getCollection()->first();

        return Inertia::render('admin/articles/Index', [
            'filters' => ['queue' => $queue, 'search' => $search],
            'counts' => $counts,
            'stats' => fn () => $this->workspace->stats($user),
            'articles' => [
                'data' => $articles->getCollection()->map(fn (Article $a): array => $this->workspace->listItem($a))->all(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'links' => $articles->linkCollection()->all(),
                ],
            ],
            'selected' => fn () => $selected instanceof Article ? $this->workspace->detail($selected, $user) : null,
            'editors' => fn () => $this->workspace->editors(),
            'reviewers' => fn () => $this->workspace->reviewers($selected instanceof Article ? $selected : null),
        ]);
    }

    public function startReview(Request $request, Article $article): RedirectResponse
    {
        Gate::authorize('decide', $article);

        $this->editorial->startReview($article, $this->user($request));

        return $this->done(__("Maqola ko'rib chiqishga olindi."));
    }

    public function decide(EditorialDecisionRequest $request, Article $article): RedirectResponse
    {
        $comment = $request->input('comment_to_author');
        $note = $request->input('internal_note');

        $decision = $this->editorial->decide(
            $article,
            $this->user($request),
            $request->decision(),
            is_string($comment) && trim($comment) !== '' ? trim($comment) : null,
            is_string($note) && trim($note) !== '' ? trim($note) : null,
        );

        return $this->done(__('Qaror saqlandi: :decision.', ['decision' => $decision->decision->label()]));
    }

    public function assignEditor(AssignEditorRequest $request, Article $article): RedirectResponse
    {
        $editor = $request->filled('editor_id') ? User::query()->find($request->integer('editor_id')) : null;

        $this->editorial->assignEditor($article, $editor);

        return $this->done($editor !== null
            ? __("Mas'ul muharrir: :name.", ['name' => $editor->name])
            : __("Mas'ul muharrir olib tashlandi."));
    }

    public function addNote(ArticleNoteRequest $request, Article $article): RedirectResponse
    {
        $this->editorial->addNote($article, $this->user($request), $request->string('body')->trim()->toString());

        return back();
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}

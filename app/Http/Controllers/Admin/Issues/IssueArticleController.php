<?php

namespace App\Http\Controllers\Admin\Issues;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Services\Issues\IssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Son tarkibi: maqolalarni qo'shish / chiqarish, tartib, rukn va sahifalar.
 */
class IssueArticleController extends Controller
{
    public function __construct(private readonly IssueService $issues) {}

    public function store(Request $request, JournalIssue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'article_ids' => ['required', 'array', 'min:1', 'max:50'],
            'article_ids.*' => ['integer', 'distinct', 'exists:articles,id'],
        ], [], ['article_ids' => __('Maqolalar')]);

        /** @var array<int, int|string> $ids */
        $ids = $validated['article_ids'];
        $count = $this->issues->attach($issue, array_map('intval', $ids));

        return $this->done(__(':count ta maqola songa qo\'shildi.', ['count' => $count]));
    }

    public function update(Request $request, JournalIssue $issue, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'section' => ['nullable', 'string', 'max:120'],
            'page_from' => ['nullable', 'required_with:page_to', 'integer', 'min:1', 'max:5000'],
            'page_to' => ['nullable', 'required_with:page_from', 'integer', 'gte:page_from', 'max:5000'],
        ], [], [
            'section' => __('Rukn'),
            'page_from' => __("Boshlang'ich sahifa"),
            'page_to' => __('Oxirgi sahifa'),
        ]);

        $section = $validated['section'] ?? null;

        $this->issues->updatePlacement(
            $issue,
            $article,
            is_string($section) ? trim($section) : null,
            $request->filled('page_from') ? $request->integer('page_from') : null,
            $request->filled('page_to') ? $request->integer('page_to') : null,
        );

        return $this->done(__('Saqlandi.'));
    }

    public function destroy(JournalIssue $issue, Article $article): RedirectResponse
    {
        $this->issues->detach($issue, $article);

        return $this->done(__('Maqola sondan chiqarildi.'));
    }

    public function reorder(Request $request, JournalIssue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct'],
        ]);

        /** @var array<int, int|string> $order */
        $order = $validated['order'];
        $this->issues->reorder($issue, array_map('intval', $order));

        return $this->done(__('Tartib saqlandi.'));
    }

    public function paginate(Request $request, JournalIssue $issue): RedirectResponse
    {
        $request->validate(['start_page' => ['required', 'integer', 'min:1', 'max:5000']]);

        $result = $this->issues->paginate($issue, $request->integer('start_page'));

        if ($result['missing'] !== []) {
            throw ValidationException::withMessages([
                'start_page' => __('Hajmi (sahifalar soni) noma\'lum maqolalar: :titles', [
                    'titles' => implode('; ', array_slice($result['missing'], 0, 3)),
                ]),
            ]);
        }

        return $this->done(__('Sahifalar hisoblandi.'));
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}

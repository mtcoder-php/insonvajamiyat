<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Articles\InviteReviewersRequest;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Muharrir: maqolaga taqrizchilarni taklif qilish va taklifni bekor qilish.
 */
class ReviewerAssignmentController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function store(InviteReviewersRequest $request, Article $article): RedirectResponse
    {
        /** @var User $editor */
        $editor = $request->user();

        $created = $this->reviews->invite($article, $request->reviewerIds(), $request->integer('due_days'), $editor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count ta taqrizchiga taklif yuborildi.', ['count' => $created->count()]),
        ]);

        return back();
    }

    public function destroy(Request $request, Article $article, Review $review): RedirectResponse
    {
        Gate::authorize('manageReviews', $article);
        abort_unless($review->article_id === $article->id, 404);

        $this->reviews->cancel($review);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Taqriz taklifi bekor qilindi.')]);

        return back();
    }
}

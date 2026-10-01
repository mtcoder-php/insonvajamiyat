<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Enums\PermissionName;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reviews\DeclineReviewRequest;
use App\Http\Requests\Admin\Reviews\SaveReviewRequest;
use App\Models\ArticleFile;
use App\Models\Review;
use App\Models\User;
use App\Services\Reviews\ReviewerWorkspace;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Taqrizchi ish joyi — "Taqrizlarim" (admin reviewer.png).
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviews,
        private readonly ReviewerWorkspace $workspace,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $counts = $this->workspace->counts($user);
        $requested = $request->string('tab')->toString();
        $tab = array_key_exists($requested, ReviewerWorkspace::TABS)
            ? $requested
            : ($counts['invited'] > 0 ? 'invited' : ($counts['active'] > 0 ? 'active' : 'all'));
        $reviews = $this->workspace->list($user, $tab);

        return Inertia::render('admin/reviews/Index', [
            'filters' => ['tab' => $tab],
            'counts' => $counts,
            'reviews' => [
                'data' => $reviews->getCollection()->map(fn (Review $r): array => $this->workspace->listItem($r))->all(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'from' => $reviews->firstItem(),
                    'to' => $reviews->lastItem(),
                    'links' => $reviews->linkCollection()->all(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Review $review): Response
    {
        $this->ensureOwner($request, $review);

        return Inertia::render('admin/reviews/Show', [
            'review' => $this->workspace->detail($review),
            'options' => ReviewerWorkspace::options(),
        ]);
    }

    public function accept(Request $request, Review $review): RedirectResponse
    {
        $this->reviews->accept($review, $this->user($request));

        return $this->done(__('Taklif qabul qilindi. Maqola fayllari ochildi.'));
    }

    public function decline(DeclineReviewRequest $request, Review $review): RedirectResponse
    {
        $reason = $request->input('reason');
        $this->reviews->decline($review, $this->user($request), is_string($reason) && trim($reason) !== '' ? trim($reason) : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Taklif rad etildi.')]);

        return to_route('admin.reviews.index');
    }

    public function update(SaveReviewRequest $request, Review $review): RedirectResponse
    {
        $submit = $request->boolean('submit');
        $attachment = $request->file('attachment');

        $this->reviews->save(
            $review,
            $this->user($request),
            $request->review(),
            $submit,
            $attachment instanceof UploadedFile ? $attachment : null,
        );

        return $this->done($submit ? __('Taqriz topshirildi. Rahmat!') : __('Qoralama saqlandi.'));
    }

    /**
     * Maqola fayli taqrizchi uchun (faqat qabul qilingan / yakunlangan taqrizda).
     */
    public function file(Request $request, Review $review, ArticleFile $file): StreamedResponse
    {
        $this->ensureOwner($request, $review);
        abort_unless($file->article_id === $review->article_id, 404);
        abort_unless(in_array($review->status, [ReviewStatus::Accepted, ReviewStatus::Completed], true), 403);

        return $this->reviews->articleFile($file, ! $request->boolean('download'));
    }

    /**
     * Taqriz fayli: taqrizchining o'zi yoki maqolalarni ko'ra oladigan xodim.
     */
    public function attachment(Request $request, Review $review): StreamedResponse
    {
        $user = $this->user($request);
        abort_unless($review->reviewer_id === $user->id || $user->can(PermissionName::ArticlesViewAny->value), 403);

        return $this->reviews->attachment($review);
    }

    private function ensureOwner(Request $request, Review $review): void
    {
        abort_unless($review->reviewer_id === $this->user($request)->id, 403);
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

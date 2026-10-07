<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\AddReviewerRequest;
use App\Http\Requests\Admin\People\ReviewerSubjectsRequest;
use App\Models\User;
use App\Services\People\ReviewerDirectory;
use App\Services\Reviews\ReviewerManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Taqrizchilar: bazasi, yuklama va tezlik, taqrizchi qo'shish / chiqarish,
 * vaqtincha to'xtatish va yo'nalishlarini belgilash (articles.assign_reviewer).
 */
class ReviewerController extends Controller
{
    public function __construct(
        private readonly ReviewerDirectory $reviewers,
        private readonly ReviewerManager $manager,
    ) {}

    public function index(Request $request): Response
    {
        $filters = ReviewerDirectory::filters($request->query->all());

        return Inertia::render('admin/reviewers/Index', [
            'filters' => $filters,
            'reviewers' => fn (): array => $this->reviewers->list($filters, max(1, $request->integer('page', 1))),
            'counts' => fn (): array => $this->reviewers->counts(),
            'subjects' => fn (): array => AuthorController::subjectOptions(),
            'busyFrom' => ReviewerDirectory::BUSY_FROM,
            'urls' => [
                'index' => route('admin.reviewers.index'),
                'store' => route('admin.reviewers.store'),
                'candidates' => route('admin.reviewers.candidates'),
                'createUser' => $this->user($request)->can(PermissionName::UsersManage->value) ? route('admin.users.create') : null,
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($this->reviewers->isReviewer($user), 404);

        $actor = $this->user($request);

        return Inertia::render('admin/reviewers/Show', [
            ...$this->reviewers->detail($user),
            'subjectOptions' => AuthorController::subjectOptions(),
            'busyFrom' => ReviewerDirectory::BUSY_FROM,
            'urls' => [
                'index' => route('admin.reviewers.index'),
                'status' => route('admin.reviewers.status', $user->id),
                'subjects' => route('admin.reviewers.subjects', $user->id),
                'destroy' => route('admin.reviewers.destroy', $user->id),
                'store' => route('admin.reviewers.store'),
                'user' => $actor->can(PermissionName::UsersManage->value) ? route('admin.users.show', $user->id) : null,
                'author' => $actor->can(PermissionName::ArticlesViewAny->value) && $user->hasRole(RoleName::Author)
                    ? route('admin.authors.show', $user->id)
                    : null,
            ],
        ]);
    }

    /** "Taqrizchi qo'shish" oynasi uchun foydalanuvchi qidiruvi (JSON) */
    public function candidates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reviewers->candidates($request->string('q')->limit(100, '')->toString()),
        ]);
    }

    public function store(AddReviewerRequest $request): RedirectResponse
    {
        $user = User::query()->findOrFail($request->integer('user_id'));
        $this->manager->add($user, $request->subjectIds(), $this->user($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(":name taqrizchilar bazasiga qo'shildi.", ['name' => $user->name])]);

        return to_route('admin.reviewers.show', $user->id);
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(RoleName::Reviewer), 404);

        $paused = $request->validate(['paused' => ['required', 'boolean']])['paused'];
        $this->manager->setPaused($user, (bool) $paused, $this->user($request));

        return $this->done($paused
            ? __("Taqrizchi vaqtincha to'xtatildi: yangi takliflar yuborilmaydi.")
            : __('Taqrizchi qayta faollashtirildi.'));
    }

    public function subjects(ReviewerSubjectsRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(RoleName::Reviewer), 404);

        $this->manager->syncSubjects($user, $request->subjectIds(), $this->user($request));

        return $this->done(__("Yo'nalishlar saqlandi."));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(RoleName::Reviewer), 404);

        $this->manager->remove($user, $this->user($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name taqrizchilar bazasidan chiqarildi.', ['name' => $user->name])]);

        return to_route('admin.reviewers.index');
    }

    private function done(mixed $message): RedirectResponse
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

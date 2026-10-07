<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use App\Services\People\AuthorDirectory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Mualliflar: ro'yxat (qidiruv, yo'nalish, tartib) va muallif sahifasi.
 * To'lovlar ustuni va bo'limi — faqat payments.view ruxsati bilan.
 */
class AuthorController extends Controller
{
    public function __construct(private readonly AuthorDirectory $authors) {}

    public function index(Request $request): Response
    {
        $filters = AuthorDirectory::filters($request->query->all());
        $canPayments = $this->user($request)->can(PermissionName::PaymentsView->value);

        return Inertia::render('admin/authors/Index', [
            'filters' => $filters,
            'authors' => fn (): array => $this->authors->list($filters, max(1, $request->integer('page', 1)), $canPayments),
            'counts' => fn (): array => $this->authors->counts(),
            'subjects' => fn (): array => self::subjectOptions(),
            'canPayments' => $canPayments,
            'urls' => ['index' => route('admin.authors.index')],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($this->authors->isAuthor($user), 404);

        $actor = $this->user($request);
        $canPayments = $actor->can(PermissionName::PaymentsView->value);

        return Inertia::render('admin/authors/Show', [
            ...$this->authors->detail($user, $canPayments),
            'canPayments' => $canPayments,
            'urls' => [
                'index' => route('admin.authors.index'),
                'user' => $actor->can(PermissionName::UsersManage->value) ? route('admin.users.show', $user->id) : null,
                'reviewer' => $actor->can(PermissionName::ArticlesAssignReviewer->value) && $user->reviews()->exists()
                    ? route('admin.reviewers.show', $user->id)
                    : null,
            ],
        ]);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function subjectOptions(): array
    {
        return Subject::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Subject $s): array => ['value' => $s->id, 'label' => $s->name])
            ->all();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}

<?php

namespace App\Http\Controllers\Admin\Users;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Http\Resources\Admin\UserDetailResource;
use App\Http\Resources\Admin\UserListResource;
use App\Models\Article;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin panel — Foydalanuvchilar (TZ 4.2.4): ro'yxat, qo'shish, profil, tahrirlash,
 * o'chirish va tiklash. Bloklash, parol va rasm — alohida controller'larda.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = [
            'search' => $request->string('search')->trim()->limit(100, '')->toString() ?: null,
            'role' => $request->query('role') === 'staff'
                ? 'staff'
                : RoleName::tryFrom($request->string('role')->toString())?->value,
            'status' => in_array($request->query('status'), UserService::STATUSES, true) ? $request->string('status')->toString() : null,
            'sort' => in_array($request->query('sort'), UserService::SORTS, true) ? $request->string('sort')->toString() : 'latest',
        ];

        return Inertia::render('admin/users/Index', [
            'users' => UserListResource::collection($this->users->paginate($filters)),
            'filters' => $filters,
            'counts' => fn () => $this->users->counts(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/Form', [
            'user' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create(
            $request->safe()->except('avatar'),
            $this->actor($request),
            $this->avatarFrom($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Foydalanuvchi qo'shildi.")]);

        return to_route('admin.users.show', $user);
    }

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load(['roles', 'authorProfile']);

        return Inertia::render('admin/users/Show', [
            'user' => UserDetailResource::make($user)->resolve(),
            'activity' => fn () => $this->users->activity($user),
            'articles' => fn () => $user->submittedArticles()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Article $article): array => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'status' => $article->status->value,
                    'statusGroup' => DashboardService::groupOf($article->status),
                    'statusLabel' => $article->status->label(),
                    'createdAt' => $article->created_at?->toIso8601String(),
                ])
                ->all(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load(['roles', 'authorProfile']);

        return Inertia::render('admin/users/Form', [
            'user' => UserDetailResource::make($user)->resolve(),
            ...$this->formOptions($request),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update(
            $user,
            $request->safe()->except('avatar'),
            $this->actor($request),
            $this->avatarFrom($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __("O'zgarishlar saqlandi.")]);

        return to_route('admin.users.show', $user);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->users->delete($user, $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Foydalanuvchi o'chirildi. Uni qayta tiklash mumkin.")]);

        return to_route('admin.users.index');
    }

    public function restore(User $user): RedirectResponse
    {
        Gate::authorize('restore', $user);

        $this->users->restore($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Foydalanuvchi tiklandi.')]);

        return to_route('admin.users.show', $user);
    }

    private function avatarFrom(Request $request): ?UploadedFile
    {
        $file = $request->file('avatar');

        return $file instanceof UploadedFile ? $file : null;
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'roleOptions' => $this->roleOptions(),
            'canGrantSuperAdmin' => $this->actor($request)->isSuperAdmin(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, staff: bool}>
     */
    private function roleOptions(): array
    {
        return array_map(fn (RoleName $role): array => [
            'value' => $role->value,
            'label' => $role->label(),
            'staff' => $role->isStaff(),
        ], RoleName::cases());
    }
}

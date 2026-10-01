<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\BlockUserRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Foydalanuvchini bloklash / blokdan chiqarish (TZ 4.2.4).
 * Bloklangan foydalanuvchi tizimdan chiqariladi (EnsureAccountIsActive) va kira olmaydi.
 */
class UserStatusController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function store(BlockUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $reason = $request->validated('reason');

        $this->users->block($user, $actor, is_string($reason) ? $reason : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Foydalanuvchi bloklandi.')]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('block', $user);

        $this->users->unblock($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Foydalanuvchi blokdan chiqarildi.')]);

        return back();
    }
}

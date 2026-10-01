<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Models\User;
use App\Services\Users\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Admin foydalanuvchining profil rasmini almashtiradi yoki o'chiradi.
 */
class UserAvatarController extends Controller
{
    public function __construct(private readonly AvatarService $avatars) {}

    public function store(AvatarRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $file = $request->file('avatar');
        abort_unless($file instanceof UploadedFile, 422);

        $this->avatars->store($user, $file);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rasm yangilandi.')]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->avatars->remove($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Rasm o'chirildi.")]);

        return back();
    }
}

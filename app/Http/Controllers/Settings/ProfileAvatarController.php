<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Models\User;
use App\Services\Users\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;

/**
 * Shaxsiy profil rasmini yuklash / o'chirish.
 */
class ProfileAvatarController extends Controller
{
    public function __construct(private readonly AvatarService $avatars) {}

    public function store(AvatarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $request->file('avatar');
        abort_unless($file instanceof UploadedFile, 422);

        $this->avatars->store($user, $file);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rasm yangilandi.')]);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->avatars->remove($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Rasm o'chirildi.")]);

        return back();
    }
}

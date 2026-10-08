<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Resources\Admin\UserDetailResource;
use App\Models\User;
use App\Services\Users\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shaxsiy profil (barcha foydalanuvchilar): ma'lumotlar, rasm va akkauntni o'chirish.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['roles', 'authorProfile']);

        return Inertia::render('settings/Profile', [
            'profile' => UserDetailResource::make($user)->resolve(),
            // User MustVerifyEmail'ni amalga oshiradi — tasdiqlash doim talab qilinadi
            'mustVerifyEmail' => true,
            // Google / ORCID orqali ro'yxatdan o'tganlarda parol bo'lmasligi mumkin (o'chirish parol talab qiladi)
            'hasPassword' => $user->hasPassword(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $emailChanged = $this->profiles->updatePersonal($user, $data);
        $this->profiles->updateAcademic($user, $data);

        // Yangi manzilga tasdiqlash havolasi darhol yuboriladi
        if ($emailChanged && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil saqlandi. Yangi manzilga tasdiqlash havolasi yuborildi.')]);

            return to_route('profile.edit');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil saqlandi.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

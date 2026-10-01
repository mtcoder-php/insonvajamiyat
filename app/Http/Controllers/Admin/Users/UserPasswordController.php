<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\UpdateUserPasswordRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

/**
 * Admin tomonidan parolni boshqarish: yangi parol o'rnatish yoki
 * foydalanuvchiga parolni tiklash havolasini emailga yuborish.
 */
class UserPasswordController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->users->setPassword($user, $request->string('password')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Yangi parol o'rnatildi.")]);

        return back();
    }

    public function reset(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        Inertia::flash('toast', $status === Password::RESET_LINK_SENT
            ? ['type' => 'success', 'message' => __('Parolni tiklash havolasi :email manziliga yuborildi.', ['email' => $user->email])]
            : ['type' => 'error', 'message' => __("Havola yuborilmadi. Birozdan so'ng qayta urinib ko'ring.")]);

        return back();
    }
}

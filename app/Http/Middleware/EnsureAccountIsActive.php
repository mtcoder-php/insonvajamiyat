<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sessiya davomida bloklangan foydalanuvchini darhol tizimdan chiqaradi (TZ 4.2.4).
 * Login paytidagi tekshiruv FortifyServiceProvider::authenticateUsing'da.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->is_blocked) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Hisobingiz bloklangan. Tahririyat bilan bog\'laning.'),
            ]);
        }

        return $next($request);
    }
}

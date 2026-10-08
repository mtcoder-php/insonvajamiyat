<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hisob formalariga urinishlar chegarasi (IP bo'yicha): ro'yxatdan o'tish, parolni tiklash,
 * profil (email o'zgarsa tasdiqlash xati ketadi). Fortify bu marshrutlarni o'zi cheklamaydi.
 *
 * Chegara oshsa — 429 sahifa emas, formadagi xato (Inertia uchun qulay).
 */
class ThrottleAccountForms
{
    /**
     * Marshrut nomi => [urinishlar, soniya, xato chiqadigan maydon]
     *
     * @var array<string, array{0: int, 1: int, 2: string}>
     */
    private const LIMITS = [
        'register.store' => [5, 600, 'email'],
        'social.register.store' => [5, 600, 'email'],
        'password.email' => [5, 600, 'email'],
        'password.update' => [10, 600, 'email'],
        'profile.update' => [10, 600, 'email'],
        'verification.send' => [6, 600, 'email'],
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if ($request->isMethodSafe() || $name === null || ! isset(self::LIMITS[$name])) {
            return $next($request);
        }

        [$attempts, $decay, $field] = self::LIMITS[$name];
        $key = 'account-form:'.$name.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            throw ValidationException::withMessages([
                $field => __('Juda ko\'p urinish. :minutes daqiqadan keyin qayta urinib ko\'ring.', [
                    'minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60)),
                ]),
            ]);
        }

        RateLimiter::hit($key, $decay);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\Language;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sayt tilini aniqlaydi: sessiya → foydalanuvchi sozlamasi → config('app.locale').
 * Til tanlash: POST /locale (Web\LocaleController).
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $candidates = [
            $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null,
            $user instanceof User ? $user->locale : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && Language::tryFrom($candidate) !== null) {
                App::setLocale($candidate);

                break;
            }
        }

        return $next($request);
    }
}

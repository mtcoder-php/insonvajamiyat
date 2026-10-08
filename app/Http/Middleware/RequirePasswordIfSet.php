<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parolni qayta tasdiqlash — faqat parol o'rnatilgan foydalanuvchilar uchun.
 * Google / ORCID orqali ro'yxatdan o'tgan (parolsiz) foydalanuvchi Xavfsizlik sahifasida
 * avval parol o'rnatadi; parol talab qiladigan amallar (2FA, akkauntni o'chirish) shundan keyin ochiladi.
 */
class RequirePasswordIfSet extends RequirePassword
{
    /**
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @param  string|null  $redirectToRoute
     * @param  string|int|null  $passwordTimeoutSeconds
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->hasPassword()) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute, $passwordTimeoutSeconds);
    }
}

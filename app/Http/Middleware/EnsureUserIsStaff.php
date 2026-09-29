<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin panel (/admin/*) faqat xodimlar uchun (TZ 3.1: "faqat login/parol va rolga ega xodimlar").
 * Muallif kirsa — 403. Aniq bo'limlar alohida `can:` / `permission:` middleware bilan himoyalanadi.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->can(PermissionName::AdminAccess->value),
            Response::HTTP_FORBIDDEN,
            __("Bu bo'lim faqat tahririyat xodimlari uchun."),
        );

        return $next($request);
    }
}

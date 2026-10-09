<?php

namespace App\Support\Http;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Brendlangan xato sahifalari (403, 404, 429, 500, 503) — Inertia `errors/Error`.
 *
 *  - JSON so'rovlar, to'lov webhook'lari va OAI-PMH — o'zgarishsiz (mashinalar uchun).
 *  - 419 (CSRF / sessiya muddati) — sahifaga qaytariladi va xabar ko'rsatiladi.
 *  - APP_DEBUG=true da 500 — Laravel'ning tafsilotli sahifasi (dasturchi uchun).
 *  - Inertia sahifasini chizib bo'lmasa (masalan, build vaqtida assetlar yo'q) —
 *    resources/views/errors/{status}.blade.php statik sahifasi qoladi.
 */
final class ErrorPage
{
    public const STATUSES = [403, 404, 429, 500, 503];

    public static function render(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($request->expectsJson() || $request->is('payments/*', 'oai', 'up')) {
            return $response;
        }

        if ($status === 419) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __("Sahifa muddati tugadi. Iltimos, qayta urinib ko'ring."),
            ]);

            return back();
        }

        if (! in_array($status, self::STATUSES, true) || ($status >= 500 && config('app.debug'))) {
            return $response;
        }

        try {
            $page = Inertia::render('errors/Error', [
                'status' => $status,
                'locale' => app()->getLocale(),
                'retryAfter' => self::retryAfter($response),
            ])->toResponse($request);
        } catch (Throwable $e) {
            report($e);

            return $response;
        }

        $page->setStatusCode($status);

        foreach (['Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'] as $header) {
            if ($response->headers->has($header)) {
                $page->headers->set($header, (string) $response->headers->get($header));
            }
        }

        return $page;
    }

    private static function retryAfter(Response $response): ?int
    {
        $value = $response->headers->get('Retry-After');

        return is_numeric($value) ? max(1, (int) $value) : null;
    }
}

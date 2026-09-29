<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /dashboard — Fortify'ning "home" manzili (login, 2FA, email tasdiqlashdan keyin).
 * Foydalanuvchini roliga qarab tegishli qismga yo'naltiradi:
 *   xodim  → /admin
 *   muallif → /cabinet
 */
class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->homeRouteName());
    }
}

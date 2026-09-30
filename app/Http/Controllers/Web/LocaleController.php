<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\Web\UpdateLocaleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Header'dagi til tanlagich (UZ / RU / EN).
 * Tanlov sessiyada saqlanadi; kirgan foydalanuvchida profilga ham yoziladi.
 */
class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $language = $request->language();

        $request->session()->put(SetLocale::SESSION_KEY, $language->value);

        $user = $request->user();

        if ($user instanceof User && $user->locale !== $language->value) {
            $user->forceFill(['locale' => $language->value])->save();
        }

        return back();
    }
}

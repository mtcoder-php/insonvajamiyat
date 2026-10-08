<?php

namespace App\Http\Controllers\Settings;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OAuth\OAuthException;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Sozlamalar → Xavfsizlik: bog'langan Google / ORCID akkauntini uzish.
 * (Bog'lash — SocialAuthController::redirect orqali, kirgan holda.)
 */
class SocialAccountController extends Controller
{
    public function __construct(private readonly SocialAuthService $social) {}

    public function destroy(Request $request, SocialProvider $provider): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->social->unlink($user, $provider);
        } catch (OAuthException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':provider akkaunti uzildi.', ['provider' => $provider->label()]),
        ]);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OAuth\OAuthException;
use App\Services\Auth\OAuth\OAuthProviders;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Google / ORCID orqali kirish va akkauntni bog'lash.
 *
 * Mehmon → kirish (yoki yangi muallif uchun ro'yxatdan o'tishni yakunlash sahifasi).
 * Kirgan foydalanuvchi → akkauntni o'z hisobiga bog'lash (Sozlamalar → Xavfsizlik).
 */
class SocialAuthController extends Controller
{
    /** OAuth oqimi (state, PKCE) shu vaqt ichida yakunlanishi kerak */
    private const FLOW_TTL_SECONDS = 600;

    public const FLOW_SESSION_KEY = 'social.flow';

    public const PENDING_SESSION_KEY = 'social.pending';

    public function __construct(
        private readonly OAuthProviders $providers,
        private readonly SocialAuthService $social,
    ) {}

    public function redirect(Request $request, SocialProvider $provider): RedirectResponse
    {
        $client = $this->providers->for($provider);
        abort_unless($client->isConfigured(), 404);

        $state = Str::random(40);
        $verifier = $client->makeCodeVerifier();

        $request->session()->put(self::FLOW_SESSION_KEY, [
            'provider' => $provider->value,
            'state' => $state,
            'verifier' => $verifier,
            'at' => now()->getTimestamp(),
        ]);

        return redirect()->away($client->redirectUrl($state, $verifier));
    }

    public function callback(Request $request, SocialProvider $provider): RedirectResponse
    {
        $client = $this->providers->for($provider);
        abort_unless($client->isConfigured(), 404);

        /** @var array<string, mixed> $flow */
        $flow = (array) $request->session()->pull(self::FLOW_SESSION_KEY, []);
        $user = $request->user();
        $linking = $user !== null;

        if ($request->filled('error')) {
            return $this->fail($linking, OAuthException::translated('Kirish bekor qilindi.')->getMessage());
        }

        $state = $flow['state'] ?? null;
        $startedAt = (int) ($flow['at'] ?? 0);

        if (($flow['provider'] ?? null) !== $provider->value
            || ! is_string($state)
            || ! hash_equals($state, (string) $request->query('state', ''))
            || now()->getTimestamp() - $startedAt > self::FLOW_TTL_SECONDS
            || ! $request->filled('code')) {
            return $this->fail($linking, OAuthException::translated('Sessiya muddati tugadi. Qayta urinib ko\'ring.')->getMessage());
        }

        $verifier = is_string($flow['verifier'] ?? null) ? $flow['verifier'] : null;

        try {
            $oauth = $client->user((string) $request->query('code'), $verifier);

            if ($user !== null) {
                $this->social->link($user, $provider, $oauth);

                Inertia::flash('toast', [
                    'type' => 'success',
                    'message' => __(':provider akkaunti bog\'landi.', ['provider' => $provider->label()]),
                ]);

                return redirect()->route('security.edit');
            }

            $found = $this->social->findUserForLogin($provider, $oauth);
        } catch (OAuthException $e) {
            return $this->fail($linking, $e->getMessage());
        }

        if ($found === null) {
            // Yangi muallif: telefon va ism-familiyani tasdiqlab ro'yxatdan o'tadi
            $request->session()->put(self::PENDING_SESSION_KEY, [
                'provider' => $provider->value,
                'user' => $oauth->toArray(),
                'at' => now()->getTimestamp(),
            ]);

            return redirect()->route('social.register');
        }

        return $this->signIn($request, $found);
    }

    /**
     * Kirish. Ikki bosqichli himoya yoqilgan bo'lsa — Fortify'ning 2FA sahifasi orqali.
     */
    public static function signIn(Request $request, User $user): RedirectResponse
    {
        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => false,
            ]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function fail(bool $linking, string $message): RedirectResponse
    {
        if ($linking) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

            return redirect()->route('security.edit');
        }

        return redirect()->route('login')->withErrors(['social' => $message]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\ProfileValidationRules;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Services\Auth\OAuth\OAuthProviders;
use App\Services\Auth\OAuth\OAuthUser;
use App\Services\Auth\SocialAuthService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Google / ORCID orqali birinchi marta kirgan muallif: ism-familiya, telefon
 * (va provayder bermagan bo'lsa — email) tasdiqlanib, hisob yaratiladi.
 */
class SocialRegisterController extends Controller
{
    use ProfileValidationRules;

    /** Provayderdan qaytgandan keyin shu vaqt ichida yakunlash kerak */
    private const PENDING_TTL_SECONDS = 1800;

    public function __construct(
        private readonly SocialAuthService $social,
        private readonly OAuthProviders $providers,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending === null) {
            return redirect()->route('login');
        }

        [$provider, $oauth] = $pending;

        return Inertia::render('auth/SocialRegister', [
            'provider' => ['key' => $provider->value, 'label' => $provider->label(), 'icon' => $this->providers->icon($provider)],
            'lastName' => $oauth->lastName ?? '',
            'firstName' => $oauth->firstName ?? '',
            'email' => $oauth->email ?? '',
            // Provayder tasdiqlagan email o'zgartirilmaydi (tasdiqlash xati kerak bo'lmaydi)
            'emailLocked' => $oauth->emailVerified && $oauth->email !== null,
            'orcid' => $oauth->orcid,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending === null) {
            return redirect()->route('login')->withErrors([
                'social' => __('Sessiya muddati tugadi. Qayta urinib ko\'ring.'),
            ]);
        }

        [$provider, $oauth] = $pending;
        $emailLocked = $oauth->emailVerified && $oauth->email !== null;

        $data = [
            'last_name' => trim((string) $request->input('last_name')),
            'first_name' => trim((string) $request->input('first_name')),
            'phone' => PhoneNumber::normalize($request->string('phone')->toString()),
            'email' => $emailLocked ? $oauth->email : mb_strtolower(trim((string) $request->input('email'))),
        ];

        /** @var array{last_name: string, first_name: string, phone: string, email: string} $validated */
        $validated = Validator::make($data, [
            'last_name' => $this->personNameRules(),
            'first_name' => $this->personNameRules(),
            'phone' => $this->phoneRules(),
            'email' => $this->emailRules(),
        ], [
            'phone.regex' => __("Telefon raqamini to'g'ri kiriting, masalan: +998 90 123 45 67"),
            'email.unique' => __('Bu email bilan hisob mavjud. Parol bilan kiring va :provider ni «Sozlamalar → Xavfsizlik» bo\'limida bog\'lang.', [
                'provider' => $provider->label(),
            ]),
        ], [
            'last_name' => __('Familiya'),
            'first_name' => __('Ism'),
            'email' => __('Elektron pochta'),
            'phone' => __('Telefon raqam'),
        ])->validate();

        $user = $this->social->register($provider, $oauth, $validated);

        $request->session()->forget(SocialAuthController::PENDING_SESSION_KEY);

        return SocialAuthController::signIn($request, $user);
    }

    /**
     * @return array{0: SocialProvider, 1: OAuthUser}|null
     */
    private function pending(Request $request): ?array
    {
        /** @var array<string, mixed> $pending */
        $pending = (array) $request->session()->get(SocialAuthController::PENDING_SESSION_KEY, []);
        $provider = SocialProvider::tryFrom((string) ($pending['provider'] ?? ''));
        $startedAt = (int) ($pending['at'] ?? 0);

        if ($provider === null || ! is_array($pending['user'] ?? null) || now()->getTimestamp() - $startedAt > self::PENDING_TTL_SECONDS) {
            $request->session()->forget(SocialAuthController::PENDING_SESSION_KEY);

            return null;
        }

        /** @var array<string, mixed> $user */
        $user = $pending['user'];
        $oauth = OAuthUser::fromArray($user);

        return $oauth->id === '' ? null : [$provider, $oauth];
    }
}

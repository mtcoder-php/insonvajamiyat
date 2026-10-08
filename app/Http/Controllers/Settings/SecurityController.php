<?php

namespace App\Http\Controllers\Settings;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\OAuth\OAuthProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    public function __construct(private readonly OAuthProviders $providers) {}

    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $linked = $user->socialAccounts()->get()->keyBy(fn (SocialAccount $account) => $account->provider->value);

        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'hasPassword' => $user->hasPassword(),
            // Google / ORCID: faqat sozlangan provayderlar va allaqachon bog'langanlar
            'socialAccounts' => collect(SocialProvider::cases())
                ->filter(fn (SocialProvider $provider) => $linked->has($provider->value) || $this->providers->isEnabled($provider))
                ->map(fn (SocialProvider $provider) => [
                    'key' => $provider->value,
                    'label' => $provider->label(),
                    'icon' => $this->providers->icon($provider),
                    'enabled' => $this->providers->isEnabled($provider),
                    'linked' => $linked->has($provider->value),
                    'email' => $linked->get($provider->value)?->email,
                    'identifier' => $linked->get($provider->value)?->provider_user_id,
                    'linkedAt' => $linked->get($provider->value)?->created_at?->toIso8601String(),
                ])
                ->values(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('settings/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $first = ! $user->hasPassword();

        $user->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $first ? __('Parol o\'rnatildi.') : __('Parol yangilandi.')]);

        return back();
    }
}

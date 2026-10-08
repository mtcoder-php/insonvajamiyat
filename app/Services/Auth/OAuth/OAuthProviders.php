<?php

namespace App\Services\Auth\OAuth;

use App\Enums\SocialProvider;

/**
 * Provayderlar ro'yxati: config/services.php dagi kalitlar bo'yicha yoqilganlari.
 */
class OAuthProviders
{
    public function for(SocialProvider $provider): OAuthProvider
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('services.'.$provider->value, []);

        return match ($provider) {
            SocialProvider::Google => new GoogleProvider($provider, $config),
            SocialProvider::Orcid => new OrcidProvider($provider, $config),
        };
    }

    public function isEnabled(SocialProvider $provider): bool
    {
        return $this->for($provider)->isConfigured();
    }

    /**
     * Login / ro'yxatdan o'tish sahifalaridagi tugmalar uchun.
     *
     * @return list<array{key: string, label: string, icon: string|null}>
     */
    public function enabled(): array
    {
        $providers = [];

        foreach (SocialProvider::cases() as $provider) {
            if ($this->isEnabled($provider)) {
                $providers[] = [
                    'key' => $provider->value,
                    'label' => $provider->label(),
                    'icon' => $this->icon($provider),
                ];
            }
        }

        return $providers;
    }

    /**
     * Provayderning rasmiy belgisi: public/images/social/{google,orcid}.svg
     * (brend qoidalariga ko'ra ularning sahifasidan yuklab olinadi). Fayl bo'lmasa — umumiy ikonka.
     */
    public function icon(SocialProvider $provider): ?string
    {
        $path = 'images/social/'.$provider->value.'.svg';

        return is_file(public_path($path)) ? asset($path) : null;
    }
}

<?php

namespace App\Services\Auth\OAuth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Google OAuth 2.0 / OpenID Connect.
 * Foydalanuvchi ma'lumoti userinfo endpoint'idan olinadi (sub, email, email_verified, ism).
 */
class GoogleProvider extends OAuthProvider
{
    protected function authorizeEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function userinfoEndpoint(): string
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    protected function scopes(): array
    {
        return ['openid', 'email', 'profile'];
    }

    protected function usesPkce(): bool
    {
        return true;
    }

    protected function extraAuthorizeParameters(): array
    {
        // Bir nechta Google akkaunti bo'lsa — tanlash oynasi
        return ['prompt' => 'select_account'];
    }

    protected function fetchUser(array $token): OAuthUser
    {
        try {
            $response = Http::withToken((string) $token['access_token'])
                ->acceptJson()
                ->timeout(15)
                ->get($this->userinfoEndpoint())
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            report($e);

            throw OAuthException::translated(':provider bilan bog\'lanib bo\'lmadi. Birozdan keyin qayta urinib ko\'ring.', [
                'provider' => $this->provider->label(),
            ], previous: $e);
        }

        /** @var array<string, mixed> $data */
        $data = (array) $response->json();
        $email = self::text($data['email'] ?? null);

        return new OAuthUser(
            id: (string) (self::text($data['sub'] ?? null) ?? ''),
            email: $email !== null ? mb_strtolower($email) : null,
            emailVerified: $email !== null && filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            lastName: self::text($data['family_name'] ?? null),
            firstName: self::text($data['given_name'] ?? null),
            name: self::text($data['name'] ?? null),
        );
    }
}

<?php

namespace App\Services\Auth\OAuth;

use App\Enums\SocialProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OAuth 2.0 "authorization code" oqimi (tashqi paketlarsiz, Laravel Http klienti bilan).
 *
 * 1) redirectUrl(): foydalanuvchi provayder sahifasiga yuboriladi (state + ixtiyoriy PKCE)
 * 2) user(): callback'dagi code → access token → foydalanuvchi ma'lumotlari
 *
 * Konfiguratsiya: config/services.php → google, orcid.
 */
abstract class OAuthProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly SocialProvider $provider,
        protected readonly array $config,
    ) {}

    abstract protected function authorizeEndpoint(): string;

    abstract protected function tokenEndpoint(): string;

    /** @return list<string> */
    abstract protected function scopes(): array;

    /**
     * @param  array<string, mixed>  $token  Token endpoint javobi
     */
    abstract protected function fetchUser(array $token): OAuthUser;

    /** PKCE (S256) qo'llab-quvvatlanadimi */
    protected function usesPkce(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    protected function extraAuthorizeParameters(): array
    {
        return [];
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    /**
     * Provayderning ruxsat sahifasi manzili.
     *
     * @param  string|null  $codeVerifier  PKCE uchun (usesPkce() bo'lsa majburiy)
     */
    public function redirectUrl(string $state, ?string $codeVerifier = null): string
    {
        $parameters = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            ...$this->extraAuthorizeParameters(),
        ];

        if ($this->usesPkce() && $codeVerifier !== null) {
            $parameters['code_challenge'] = self::codeChallenge($codeVerifier);
            $parameters['code_challenge_method'] = 'S256';
        }

        return $this->authorizeEndpoint().'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /** PKCE uchun tasodifiy code_verifier (43–128 belgi) */
    public function makeCodeVerifier(): ?string
    {
        return $this->usesPkce() ? Str::random(64) : null;
    }

    /**
     * Callback'dagi code orqali foydalanuvchini oladi.
     *
     * @throws OAuthException
     */
    public function user(string $code, ?string $codeVerifier = null): OAuthUser
    {
        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->callbackUrl(),
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
        ];

        if ($this->usesPkce() && $codeVerifier !== null) {
            $parameters['code_verifier'] = $codeVerifier;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(15)
                ->post($this->tokenEndpoint(), $parameters)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            report($e);

            throw OAuthException::translated(':provider bilan bog\'lanib bo\'lmadi. Birozdan keyin qayta urinib ko\'ring.', [
                'provider' => $this->provider->label(),
            ], previous: $e);
        }

        /** @var array<string, mixed> $token */
        $token = (array) $response->json();

        if (! is_string($token['access_token'] ?? null) || $token['access_token'] === '') {
            throw OAuthException::translated(':provider bilan bog\'lanib bo\'lmadi. Birozdan keyin qayta urinib ko\'ring.', [
                'provider' => $this->provider->label(),
            ]);
        }

        $user = $this->fetchUser($token);

        if ($user->id === '') {
            throw OAuthException::translated(':provider bilan bog\'lanib bo\'lmadi. Birozdan keyin qayta urinib ko\'ring.', [
                'provider' => $this->provider->label(),
            ]);
        }

        return $user;
    }

    /** To'liq callback manzili ({APP_URL}/auth/{provider}/callback) */
    public function callbackUrl(): string
    {
        $redirect = (string) ($this->config['redirect'] ?? '');

        if ($redirect === '') {
            return route('social.callback', $this->provider->value);
        }

        return Str::startsWith($redirect, ['http://', 'https://']) ? $redirect : url($redirect);
    }

    protected function clientId(): string
    {
        return trim((string) ($this->config['client_id'] ?? ''));
    }

    protected function clientSecret(): string
    {
        return trim((string) ($this->config['client_secret'] ?? ''));
    }

    protected static function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /** Bo'sh bo'lmagan satr yoki null */
    protected static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

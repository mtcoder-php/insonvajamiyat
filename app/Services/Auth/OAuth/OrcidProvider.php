<?php

namespace App\Services\Auth\OAuth;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ORCID OAuth 2.0 ("/authenticate" scope).
 *
 * Token javobining o'zida tasdiqlangan ORCID iD va ism keladi. Ism-familiya va
 * (foydalanuvchi ochiq qilgan bo'lsa) tasdiqlangan email Public API'dan olinadi —
 * u ishlamasa ham kirish davom etadi.
 *
 * Sinov muhiti: ORCID_SANDBOX=true → sandbox.orcid.org
 */
class OrcidProvider extends OAuthProvider
{
    private function sandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? false);
    }

    protected function baseUrl(): string
    {
        return $this->sandbox() ? 'https://sandbox.orcid.org' : 'https://orcid.org';
    }

    protected function apiUrl(): string
    {
        return $this->sandbox() ? 'https://pub.sandbox.orcid.org/v3.0' : 'https://pub.orcid.org/v3.0';
    }

    protected function authorizeEndpoint(): string
    {
        return $this->baseUrl().'/oauth/authorize';
    }

    protected function tokenEndpoint(): string
    {
        return $this->baseUrl().'/oauth/token';
    }

    protected function scopes(): array
    {
        return ['/authenticate'];
    }

    protected function fetchUser(array $token): OAuthUser
    {
        $orcid = self::text($token['orcid'] ?? null) ?? '';

        // Faqat to'g'ri formatdagi iD (0000-0000-0000-000X)
        if (preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid) !== 1) {
            return new OAuthUser(id: '', email: null, emailVerified: false, lastName: null, firstName: null, name: null);
        }

        $person = $this->person($orcid, (string) $token['access_token']);

        $email = null;

        foreach ((array) data_get($person, 'emails.email', []) as $item) {
            if (! is_array($item) || ! ($item['verified'] ?? false) || ! is_string($item['email'] ?? null)) {
                continue;
            }

            // Birlamchi email afzal
            if ($email === null || ($item['primary'] ?? false)) {
                $email = mb_strtolower(trim($item['email']));
            }
        }

        return new OAuthUser(
            id: $orcid,
            email: $email,
            emailVerified: $email !== null,
            lastName: self::text(data_get($person, 'name.family-name.value')),
            firstName: self::text(data_get($person, 'name.given-names.value')),
            name: self::text($token['name'] ?? null),
            orcid: $orcid,
        );
    }

    /**
     * Public API: /{orcid}/person (ism, ochiq emaillar).
     *
     * @return array<string, mixed>
     */
    protected function person(string $orcid, string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get($this->apiUrl().'/'.$orcid.'/person');

            return $response->successful() ? (array) $response->json() : [];
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}

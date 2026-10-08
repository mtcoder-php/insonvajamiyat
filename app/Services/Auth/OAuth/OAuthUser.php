<?php

namespace App\Services\Auth\OAuth;

/**
 * Provayderdan (Google / ORCID) olingan foydalanuvchi ma'lumotlari.
 * Kirish tokenlari saqlanmaydi — ular faqat shu ma'lumotni olish uchun ishlatiladi.
 */
final readonly class OAuthUser
{
    public function __construct(
        public string $id,
        public ?string $email,
        public bool $emailVerified,
        public ?string $lastName,
        public ?string $firstName,
        public ?string $name,
        /** ORCID iD (0000-0000-0000-0000) — faqat ORCID provayderida */
        public ?string $orcid = null,
    ) {}

    /**
     * Sessiyada saqlash uchun (ro'yxatdan o'tishni yakunlash sahifasi).
     *
     * @return array{id: string, email: ?string, emailVerified: bool, lastName: ?string, firstName: ?string, name: ?string, orcid: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'emailVerified' => $this->emailVerified,
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'name' => $this->name,
            'orcid' => $this->orcid,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $string = static function (string $key) use ($data): ?string {
            $value = $data[$key] ?? null;

            return is_string($value) && $value !== '' ? $value : null;
        };

        return new self(
            id: $string('id') ?? '',
            email: $string('email'),
            emailVerified: (bool) ($data['emailVerified'] ?? false),
            lastName: $string('lastName'),
            firstName: $string('firstName'),
            name: $string('name'),
            orcid: $string('orcid'),
        );
    }
}

<?php

namespace App\Services\Indexing\OaiPmh;

use JsonException;

/**
 * Davom ettirish tokeni (resumptionToken): holatni o'zida saqlaydi (serverda hech narsa yozilmaydi).
 * HMAC bilan imzolangan — qo'lda o'zgartirilgan token badResumptionToken bo'ladi.
 *
 * Sahifalash id bo'yicha (keyset): yangi yozuvlar qo'shilsa ham takror yoki tushib qolish bo'lmaydi.
 */
final readonly class ResumptionToken
{
    public function __construct(
        public string $metadataPrefix,
        public ?string $from,
        public ?string $until,
        public ?string $set,
        /** Oxirgi berilgan yozuv id'si */
        public int $afterId,
        /** Shu paytgacha berilgan yozuvlar soni (cursor) */
        public int $cursor,
    ) {}

    public function encode(): string
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode([
            'p' => $this->metadataPrefix,
            'f' => $this->from,
            'u' => $this->until,
            's' => $this->set,
            'a' => $this->afterId,
            'c' => $this->cursor,
        ])), '+/', '-_'), '=');

        return $payload.'.'.self::signature($payload);
    }

    /**
     * @throws OaiException
     */
    public static function decode(string $token): self
    {
        $invalid = new OaiException('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired.');
        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals(self::signature($parts[0]), $parts[1])) {
            throw $invalid;
        }

        try {
            $data = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/'), true), true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $invalid;
        }

        if (! is_array($data) || ! is_string($data['p'] ?? null) || ! is_int($data['a'] ?? null) || ! is_int($data['c'] ?? null)) {
            throw $invalid;
        }

        $string = fn (string $key): ?string => is_string($data[$key] ?? null) ? $data[$key] : null;

        return new self($data['p'], $string('f'), $string('u'), $string('s'), $data['a'], $data['c']);
    }

    private static function signature(string $payload): string
    {
        return substr(hash_hmac('sha256', 'oai|'.$payload, (string) config('app.key')), 0, 16);
    }
}

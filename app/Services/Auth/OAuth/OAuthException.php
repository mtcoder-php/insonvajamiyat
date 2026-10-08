<?php

namespace App\Services\Auth\OAuth;

use RuntimeException;
use Throwable;

/**
 * OAuth jarayonidagi xato. Xabar foydalanuvchiga ko'rsatiladi (joriy tilga tarjima qilingan).
 */
class OAuthException extends RuntimeException
{
    /**
     * @param  array<string, string|int>  $replace
     */
    public static function translated(string $key, array $replace = [], ?Throwable $previous = null): self
    {
        $message = __($key, $replace);

        return new self(is_string($message) ? $message : $key, previous: $previous);
    }
}

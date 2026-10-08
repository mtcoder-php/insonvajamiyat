<?php

namespace App\Services\Indexing\OaiPmh;

use RuntimeException;

/**
 * OAI-PMH protokol xatosi (<error code="...">). HTTP holati baribir 200 bo'ladi.
 *
 * @see https://www.openarchives.org/OAI/openarchivesprotocol.html#ErrorConditions
 */
class OaiException extends RuntimeException
{
    public function __construct(public readonly string $oaiCode, string $message)
    {
        parent::__construct($message);
    }

    public static function badArgument(string $message): self
    {
        return new self('badArgument', $message);
    }
}
